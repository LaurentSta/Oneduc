# Audit sécurité et mise en production — 22 septembre 2026

> Ce document conserve les constats de l'audit initial. Les corrections réalisées ensuite et les résultats des vérifications locales sont consignés dans [Sécurité et préparation du déploiement](wiki/19-securite-deploiement.md). Les limitations d'environnement décrites ci-dessous concernent la session d'audit initiale.

## 1. Objet et périmètre

Cet audit prépare la reprise d'Oneduc et son passage habituel de GitHub vers la production. Il porte sur le dépôt courant, les contrôles d'accès visibles, la gestion des secrets, les dépendances, l'intégration continue et l'absence ou la présence d'un processus de déploiement reproductible.

Ce document est un audit statique : l'environnement ne contient pas `vendor/`, et l'accès aux registres Composer et npm a répondu HTTP 403. La suite PHPUnit et les bases de vulnérabilités distantes n'ont donc pas pu être exécutées. Un audit dynamique de la machine de production, du serveur HTTP, de TLS, des permissions et de la base reste indispensable.

## 2. Verdict

**État recommandé : ne pas automatiser encore un déploiement direct en production.**

Le dépôt dispose d'une CI Laravel et de protections applicatives déjà sérieuses (middleware de rôles, limitation de plusieurs routes sensibles, validation des téléversements, contrôle d'accès aux progressions SCORM). En revanche, il manque encore les garde-fous qui transforment un `git push` en livraison fiable : construction frontend en CI, audits de dépendances, environnement de préproduction, sauvegarde vérifiée, migrations avec stratégie de retour arrière, déploiement atomique et contrôle post-déploiement.

La priorité n'est pas de réécrire le site. Elle est de construire une **chaîne de livraison sûre**, puis de traiter quelques décisions et risques applicatifs précis.

## 3. Points positifs constatés

- `.env`, `.env.*`, `auth.json`, les clés de stockage, `vendor/`, `node_modules/`, les journaux et les contenus importés sont ignorés par Git.
- Aucun secret manifeste ni fichier de clé privée n'est suivi parmi les noms de fichiers contrôlés. Les fichiers d'environnement suivis sont uniquement les exemples.
- La CI actuelle installe les dépendances PHP depuis le lockfile, génère une clé éphémère, exécute les migrations MySQL puis les tests.
- Les espaces admin, formateur, observateur et stagiaire sont regroupés derrière des middlewares d'authentification et de rôle.
- La connexion par code, le contact, l'émargement et plusieurs outils interactifs disposent de limites de débit.
- Les trois contrôleurs de progression SCORM vérifient l'authentification et l'accès de l'utilisateur au module avant d'écrire ses résultats.
- Le HTTPS est forcé par l'application quand `APP_ENV=production`.
- Le dépôt contient 111 fichiers de tests, dont des tests ciblant les accès, les imports SCORM, la visibilité des modules et le throttling.

Ces éléments réduisent le risque, mais ne remplacent pas une validation complète et un déploiement maîtrisé.

## 4. Risques et corrections prioritaires

### P0 — Bloquants avant toute automatisation de production

#### P0.1 — Le déploiement GitHub → production n'est pas défini

La seule GitHub Action exécute les tests Laravel. Aucun workflow ou script ne décrit une livraison de l'application principale, un verrou de concurrence, un environnement GitHub protégé, une approbation manuelle, une sauvegarde, un health check ou un rollback.

**Risque :** une procédure manuelle non documentée peut publier une révision non testée, oublier le build Vite, exécuter une migration irréversible ou laisser l'application partiellement mise à jour.

**Correction attendue :** documenter la topologie réelle puis créer un déploiement atomique par releases (`releases/<sha>` + lien `current`), déclenché uniquement après CI verte et approbation de l'environnement GitHub `production`. Ne jamais faire un simple `git pull` dans le répertoire servi par Apache/Nginx.

#### P0.2 — Sauvegarde et restauration non démontrées

Le dépôt ne contient pas de procédure pour sauvegarder et surtout restaurer la base Oneduc et les fichiers persistants (`storage/app`, médias, contenus SCORM). La documentation HedgeDoc ne couvre pas l'application principale.

**Risque :** perte de comptes, progressions, émargements et contenus lors d'une migration, d'une erreur humaine ou d'une compromission.

**Correction attendue :** définir le RPO/RTO, chiffrer les sauvegardes, les stocker hors serveur, automatiser leur rotation et réaliser un test de restauration sur une machine isolée avant le premier déploiement automatisé.

#### P0.3 — La CI ne valide pas l'application livrée complète

Le workflow n'installe pas les dépendances Node, ne lance ni `npm run build`, ni PHPStan, ni Pint, ni audit de dépendances. Il n'archive pas non plus un artefact unique destiné à la production.

**Risque :** tests PHP verts mais assets absents ou cassés, erreur statique ignorée, ou différence entre le commit testé et les fichiers effectivement copiés en production.

**Correction attendue :** ajouter `npm ci`, `npm run build`, `composer analyse`, un contrôle de format, `composer audit` et `npm audit`; produire ensuite un artefact immuable associé au SHA Git. Les audits réseau doivent être configurés pour échouer sur une vulnérabilité élevée ou critique, avec une procédure explicite d'exception temporaire.

#### P0.4 — Aucun contrôle post-déploiement ni retour arrière

Laravel expose `/up`, mais aucun déploiement ne l'interroge et aucun scénario ne restaure automatiquement la release précédente.

**Correction attendue :** tester `/up`, la page d'accueil et une route authentifiée de diagnostic après bascule; conserver au moins deux releases; rebascule immédiate du lien `current` en cas d'échec. Les migrations destructives doivent être séparées selon une stratégie « expand/contract », car un rollback du code ne restaure pas automatiquement le schéma ni les données.

### P1 — Sécurité applicative et exploitation avant ouverture large

#### P1.1 — Routes SCORM sans protection CSRF

Trois routes POST SCORM désactivent explicitement `PreventRequestForgery`. L'authentification et le contrôle d'accès compensent partiellement le risque, mais ne prouvent pas l'origine de la requête. Le cookie `SameSite=lax` limite certains scénarios sans constituer un contrat suffisant, notamment si l'architecture ou les domaines changent.

**Correction proposée :** privilégier la route `/scorm/progress` protégée par CSRF et transmettre le jeton à l'iframe same-origin. Si une exception est réellement nécessaire, utiliser un jeton SCORM court, signé, lié à l'utilisateur, à la leçon et à une expiration; vérifier aussi une liste d'origines autorisées. Ajouter des tests de requête sans jeton, jeton expiré et leçon non autorisée.

#### P1.2 — Auto-inscription stagiaire publique à trancher

Les routes Breeze `/register` restent publiques et créent un compte stagiaire actif, indépendamment du parcours documenté de connexion par code. L'inscription formateur est également publique.

**Risque :** création de comptes non désirés, spam et contournement du modèle d'accès métier.

**Décision attendue :** soit assumer l'auto-inscription et ajouter vérification d'e-mail, CAPTCHA, throttling et règles d'activation; soit désactiver `/register` en production et passer par invitation/code. Appliquer une politique distincte et explicite aux comptes formateur.

#### P1.3 — En-têtes HTTP de défense non gérés dans le dépôt

Aucun middleware ou fichier de configuration suivi ne pose explicitement CSP, HSTS, `X-Content-Type-Options`, `Referrer-Policy` ou `Permissions-Policy`. Ils peuvent exister sur le serveur actuel, mais ce n'est ni visible ni testable depuis le dépôt.

**Correction proposée :** définir ces en-têtes au reverse proxy ou dans un middleware testé. Commencer CSP en `Report-Only`, inventorier les scripts/styles inline et les besoins des lecteurs SCORM, puis passer en enforcement. N'activer HSTS qu'après validation HTTPS de tous les sous-domaines concernés.

#### P1.4 — Configuration de production non spécifiée

`.env.example` décrit un poste local (`APP_DEBUG=true`, HTTP, logs debug, mail log, SQLite) et ne fournit pas de modèle de variables de production sans secrets.

**Correction proposée :** ajouter une checklist ou un `.env.production.example` non secret avec au minimum `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, cookies sécurisés, session chiffrée, base et cache dédiés, file d'attente réelle, mail transactionnel, niveau de log raisonnable et variables des services externes. Les valeurs secrètes doivent rester dans le gestionnaire de secrets GitHub et/ou sur l'hôte, jamais dans Git ni dans un artefact public.

#### P1.5 — Supervision des workers et du scheduler absente

L'application utilise une file `database` par défaut et comporte des jobs, mais aucun service Supervisor/systemd ni cron `schedule:run` pour l'application principale n'est versionné.

**Risque :** travaux asynchrones bloqués, notifications ou conversions jamais exécutées, accumulation silencieuse des jobs échoués.

**Correction proposée :** superviser `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`, redémarrer les workers après release, exécuter le scheduler chaque minute, monitorer les échecs et tester ce fonctionnement en préproduction.

#### P1.6 — Journaux, alertes et détection d'incident à formaliser

La présence de logs applicatifs ne suffit pas à détecter une hausse de 500, une saturation disque, une file bloquée, un certificat proche de l'expiration ou des tentatives de connexion anormales.

**Correction proposée :** centraliser les erreurs, exclure mots de passe, cookies, tokens et contenus pédagogiques sensibles des logs; ajouter alertes uptime, taux de 5xx, espace disque, base, queue, sauvegardes et expiration TLS. Documenter qui reçoit l'alerte et comment réagir.

### P2 — Durcissement et conformité à programmer

#### P2.1 — Rétention RGPD non automatisée

Les scores, réponses, temps d'activité, émargements et journaux sont des données personnelles. La documentation identifie des durées, mais aucune tâche générale de purge/anonymisation n'est démontrée.

**Action :** valider les durées et bases légales avec le responsable de traitement, puis implémenter une commande idempotente, un mode simulation, un journal d'exécution et des tests. Toute purge destructive exige sauvegarde et validation métier.

#### P2.2 — Médias du disque public servis par une route générique

`/media/storage/{path}` renvoie tout fichier présent sur le disque Laravel `public` si son chemin existe. Le blocage de `..` protège contre la traversée simple, mais la confidentialité repose entièrement sur la décision de placer ou non le fichier sur ce disque.

**Action :** inventorier les familles de fichiers. Garder uniquement les ressources réellement publiques sur ce disque; servir les ressources de cours, productions et pièces jointes privées via une route autorisée ou une URL temporaire. Ajouter un test garantissant qu'un visiteur anonyme ne télécharge pas un fichier pédagogique privé.

#### P2.3 — Actions GitHub référencées par tags flottants

`actions/checkout@v4` et `shivammathur/setup-php@v2` sont pratiques mais non figées sur un SHA.

**Action :** épingler les actions tierces à un SHA vérifié et utiliser Dependabot/Renovate pour proposer les mises à jour. Limiter les permissions du `GITHUB_TOKEN` à `contents: read` dans la CI; accorder les droits de déploiement uniquement au job protégé.

#### P2.4 — Audit de dépendances non validé dans cet environnement

`composer.json` est valide, mais `composer audit --locked` et `npm audit --package-lock-only` n'ont pas pu joindre leurs registres (HTTP 403). Cela ne signifie ni qu'une vulnérabilité existe, ni qu'il n'y en a aucune.

**Action :** exécuter les audits dans GitHub Actions avec accès réseau, activer Dependabot pour Composer, npm et GitHub Actions, et traiter en priorité les alertes critiques/élevées.

## 5. Chaîne de livraison cible

### Étape A — Pull request

1. Branche protégée `main`, pull request obligatoire, pas de push direct.
2. CI obligatoire : validation Composer, installation reproductible, tests MySQL, analyse statique, format, build Vite et audits.
3. Revue humaine obligatoire pour les migrations, l'authentification, les autorisations, les uploads et le déploiement.
4. Secret scanning et Dependabot activés dans GitHub.

### Étape B — Préproduction

1. Déployer automatiquement le même artefact que celui destiné à la production.
2. Utiliser une base et un stockage séparés; aucune copie de données personnelles réelles sans anonymisation.
3. Lancer les migrations, le health check, les smoke tests et un test des workers.
4. Valider manuellement les parcours admin, formateur et stagiaire essentiels.

### Étape C — Production avec approbation

1. Approbation manuelle dans l'environnement GitHub `production`.
2. Verrou de concurrence : un seul déploiement à la fois.
3. Sauvegarde contrôlée et vérification de sa fraîcheur.
4. Téléversement d'une release dans un nouveau dossier; installation avec `composer install --no-dev --classmap-authoritative`; assets déjà construits.
5. `php artisan down` seulement si nécessaire, migrations `--force`, caches Laravel, droits minimaux, bascule atomique de `current`.
6. Redémarrage de la queue, sortie de maintenance, health checks et smoke tests.
7. En cas d'échec : retour à la release précédente et procédure spécifique pour la base.

### Étape D — Après livraison

1. Surveiller erreurs, latence, jobs et disque pendant au moins 30 minutes.
2. Conserver le SHA déployé et un journal horodaté de l'opération.
3. Vérifier la sauvegarde suivante et documenter tout incident.

## 6. Variables et réglages minimaux de production

Valeurs indicatives, sans aucun secret :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://oneduc.fr
LOG_LEVEL=warning

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

À confirmer selon l'hébergement : proxy de confiance, terminaison TLS, Redis, SMTP, limites PHP/Apache, taille des imports SCORM, antivirus des fichiers entrants, chemins LibreOffice/Piper, tâches planifiées et permissions de `storage`/`bootstrap/cache`.

## 7. Plan de travail conseillé

### Lot 1 — Qualification locale et CI (1 à 2 jours)

- Installer `vendor/` avec la version PHP cible de production.
- Exécuter toute la suite de tests sur MySQL/MariaDB identique à la production.
- Ajouter build frontend, PHPStan et audits au workflow.
- Décider du comportement de `/register` et des inscriptions formateur.

**Critère de sortie :** tous les contrôles obligatoires sont verts sur une pull request.

### Lot 2 — Socle production (2 à 4 jours)

- Inventorier serveur, DNS, TLS, PHP, base, stockage et volumes.
- Écrire et tester sauvegarde/restauration.
- Configurer préproduction, workers, scheduler, logs et alertes.
- Établir le modèle de configuration de production et les secrets.

**Critère de sortie :** restauration réussie et parcours critiques validés en préproduction.

### Lot 3 — Déploiement contrôlé (2 à 3 jours)

- Construire le workflow par artefact et releases atomiques.
- Ajouter approbation, verrou, health checks et rollback.
- Effectuer une répétition complète en préproduction, y compris un échec simulé.

**Critère de sortie :** une release et son rollback sont reproductibles sans intervention improvisée.

### Lot 4 — Durcissement applicatif (progressif)

- Résoudre l'exception CSRF SCORM.
- Ajouter les en-têtes de sécurité.
- Revoir la confidentialité des médias publics.
- Mettre en œuvre la politique RGPD après validation métier.

## 8. Checklist « go / no-go »

La production est **NO-GO** si une seule réponse ci-dessous est « non » :

- [ ] La CI complète est verte sur le SHA exact à déployer.
- [ ] Les audits de dépendances ont été exécutés et les alertes critiques/élevées sont traitées.
- [ ] `APP_DEBUG=false`, HTTPS et cookies sécurisés sont confirmés.
- [ ] Une sauvegarde récente existe hors serveur et une restauration a été testée.
- [ ] Les migrations ont été relues et leur compatibilité avec un rollback applicatif est connue.
- [ ] Le même artefact a passé les smoke tests en préproduction.
- [ ] Workers, scheduler, espace disque, erreurs et TLS sont supervisés.
- [ ] La release précédente est disponible et la personne qui déploie connaît la procédure de retour arrière.
- [ ] Les secrets ne figurent ni dans Git, ni dans les logs, ni dans l'artefact.
- [ ] Les décisions sur l'auto-inscription et la confidentialité des médias sont validées.

## 9. Contrôles réalisés pendant l'audit

- Inspection de l'état Git, des workflows et des fichiers de déploiement.
- Vérification des fichiers potentiellement sensibles suivis par Git.
- Lecture des routes, middlewares, flux d'authentification et contrôleurs SCORM.
- Recherche des en-têtes de sécurité, de la supervision et des procédures de production.
- Inventaire de la suite de tests.
- `composer validate --strict` : réussi.
- `composer audit --locked` : non conclu, accès Packagist refusé par l'environnement.
- `npm audit --package-lock-only --audit-level=moderate` : non conclu, accès npm refusé par l'environnement.
- `php artisan test` : non exécutable sans `vendor/autoload.php`.

## 10. Prochaine action recommandée

Commencer par le **lot 1** dans une pull request dédiée. C'est le meilleur rapport risque/effort : il donnera une photographie fiable du code actuel et empêchera qu'une future automatisation publie une version non construite ou non testée. Ensuite seulement, documenter l'hébergement réel pour choisir entre un déploiement SSH par GitHub Actions, un outil de déploiement dédié ou une plateforme managée.
