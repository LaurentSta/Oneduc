# 19 — Sécurité et préparation du déploiement

*Public : développeurs et administrateurs système. Mise à jour : 22 septembre 2026.*

Les correctifs décrits ici sont préparés sur la branche `fix/securite-preparation-production`. Leur publication sur GitHub et dans ce wiki ne signifie pas qu'ils sont fusionnés dans `main` ou déployés en production.

## Corrections dans le dépôt

- Les quatre routes d'écriture SCORM (`save-progress`, `progress`, `save-block-progress`, `evaluation-progress`) passent par la protection CSRF de Laravel. Les pages parentes admin, évaluations stagiaire et blocs SCORM fournissent leur jeton ; `API_evaluation.js` l'envoie aussi lors de la fermeture. Les contrôles d'authentification et d'appartenance au module restent obligatoires.
- Cela concerne les lecteurs SCORM 1.2 et 2004, classiques et versionnés. Aucune migration ni remise à zéro des scores : `ScormScore`, `ContentBlockScormScore`, `ScormEvaluationScore`, résultats et interactions restent consommés par les tableaux de bord habituels, dont `LearningAnalyticsService`.
- Laravel 13 accepte aussi les requêtes dont le navigateur indique une origine identique (`Sec-Fetch-Site: same-origin`). Sans cette preuve, le jeton de session est nécessaire. Les tests réactivent explicitement le middleware normalement désactivé en environnement de test.
- Les réponses Laravel portent `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` et une CSP limitée à `frame-ancestors 'self'`. L'intégration d'Oneduc dans un site tiers est bloquée ; les iframes internes restent autorisées. Une CSP existante n'est pas remplacée. Une politique complète des scripts nécessite un inventaire distinct des scripts et intégrations.
- HSTS est activable avec `SECURITE_HSTS_MAX_AGE`, uniquement sur une requête HTTPS en production. Sa valeur par défaut est `0` ; ne l'activer qu'après validation du HTTPS. Aucun `includeSubDomains` ou `preload` automatique.
- `/up` teste une lecture SQL et l'accès au cache configuré. Il répond `200` si ces services répondent, `500` sinon. Avec `Accept: application/json`, il renvoie `{"status":"up"}` ou `{"status":"down"}`. Les erreurs des services sont remplacées par un message générique ; `APP_DEBUG=false` reste indispensable.
- `php artisan oneduc:verifier-production` vérifie la configuration, les cookies, les services choisis, les dossiers et la présence du build Vite. Il retourne un code non nul en cas d'anomalie, sans afficher les valeurs ni modifier les données. Ce contrôle ne prouve ni la livraison des emails, ni le fonctionnement des workers ou les permissions du compte PHP-FPM : l'exécuter avec le compte qui exécutera l'application.

Les fichiers servis directement par Apache/Nginx ne traversent pas le middleware Laravel. Vérifier également leurs en-têtes sur le serveur. Les anciennes copies des wrappers JavaScript embarquées dans les packages SCORM doivent être inventoriées et testées en préproduction avant livraison.

## Qualification GitHub

Le workflow `.github/workflows/laravel.yml` dispose de trois jobs bloquants :

1. `laravel-tests` : PHP 8.3, installation Composer reproductible, migrations et tests MySQL 8.
2. `qualite-php` : validation du manifeste, audit Composer, PHPStan et Pint sur les fichiers PHP modifiés.
3. `frontend` : Node 22, `npm ci`, audit npm, test JavaScript SCORM et build Vite.

Les Actions sont épinglées à un SHA, le jeton GitHub est en lecture seule, les identifiants de checkout ne sont pas persistés et les exécutions obsolètes sont annulées. Dependabot propose les mises à jour Composer, npm et Actions chaque semaine, en regroupant Tiptap.

L'artefact `frontend-<sha>` contient uniquement `public/build/` et est conservé sept jours pour vérification. Ce n'est pas une release complète : aucun déploiement automatique de production n'est activé. Configurer les trois contrôles obligatoires dans la protection de `main` sur GitHub ; le fichier YAML seul ne modifie pas cette protection.

Les correctifs Composer ciblent Dompdf, Guzzle et CommonMark. Tiptap est aligné sur `3.31.3`. Trois overrides npm corrigent des dépendances transitives verrouillées par leurs parents : Nano ID 3 pour Excalidraw, Nano ID 5 pour le convertisseur Mermaid (version 4 sans correctif disponible), et lodash-es 4.18.1. Les API de génération d'identifiants et le build sont vérifiés ; tester visuellement le tableau blanc et la conversion Mermaid après ces mises à jour. Retirer ces overrides lorsque les dépendances parentes embarquent les correctifs.

## Configuration de production à adapter

Adapter le `.env` déjà présent sur le serveur, sans l'écraser ni changer `APP_KEY`. Ne jamais copier le `.env` de développement ou commiter les secrets. Exemple partiel, sans identifiants :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine
APP_TIMEZONE=Europe/Paris
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
LOG_LEVEL=warning
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
DB_CONNECTION=mysql
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
SECURITE_HSTS_MAX_AGE=0
```

Renseigner les identifiants SQL, SMTP et services tiers uniquement sur l'hôte ou dans le gestionnaire de secrets. Les pilotes `database` évitent d'imposer Redis ; vérifier les tables sessions/cache/jobs. Si Redis est déjà exploité, conserver les pilotes et la supervision correspondants. PHP CLI et PHP-FPM doivent satisfaire `composer check-platform-reqs --no-dev` (Laravel 13 exige PHP 8.3 minimum).

La racine HTTP doit pointer vers `public/`, jamais la racine du dépôt. En présence d'un reverse proxy, configurer uniquement les adresses réellement fiables ; ne pas faire confiance à tous les proxys par défaut. Vérifier HTTPS, les cookies et la détection de l'origine depuis le navigateur réel.

## Préparer une livraison manuelle reproductible

Cette procédure doit être adaptée à l'hébergement puis répétée en préproduction. Ne pas effectuer de `git pull` dans le dossier servi en production.

1. Choisir un SHA dont les trois jobs CI sont verts et consigner ce SHA. Utiliser une release neuve `releases/<sha>` contenant le code et le build issus du même SHA. Les dossiers persistants (`storage`, uploads et packages SCORM, y compris les chemins legacy) et le `.env` restent externes aux releases. Faire l'inventaire des chemins avant de déplacer des fichiers.
2. Sauvegarder la base et tous les fichiers persistants de façon cohérente, avec chiffrement, copie hors serveur et accès restreint. Pendant cette capture, suspendre les écritures et les workers si aucun mécanisme de snapshot cohérent n'est disponible. Sauvegarder aussi les secrets, notamment `APP_KEY`, dans le coffre prévu à cet effet.
3. Restaurer cette sauvegarde sur un environnement isolé avec envoi des emails/webhooks neutralisé. Vérifier connexion, groupes, médias et progression SCORM. Consigner date, durée, SHA, version de base, fichiers restaurés et résultat. Une sauvegarde jamais restaurée ne valide pas la mise en production.
4. Dans la nouvelle release, installer avec `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, puis `composer check-platform-reqs --no-dev`. Relier le `.env` et les chemins persistants inventoriés ; vérifier les droits du compte d'exécution. Le build doit déjà être présent et `public/hot` absent.
5. Relire les migrations et définir leur compatibilité avec l'ancienne release. Si elles sont incompatibles, arrêter les écritures et les workers avant les migrations, avec une fenêtre de maintenance annoncée. Exécuter `php artisan migrate --force` uniquement après validation de la restauration et de ce plan.
6. Préparer les caches de la nouvelle release avec `php artisan config:cache`, `php artisan route:cache` et `php artisan view:cache`. Exécuter `php artisan oneduc:verifier-production` avec le compte de l'application. Toute erreur interrompt la livraison.
7. Basculer atomiquement le lien `current` vers la release validée, conserver la cible précédente, puis recharger PHP-FPM selon l'hébergement et redémarrer les workers (`php artisan queue:restart`). Sortir de maintenance si elle avait été activée.
8. Exécuter `bash scripts/verifier-deploiement.sh https://votre-domaine`. Le script vérifie TLS, `/up`, l'en-tête `nosniff` et `/login`, avec délais maximums et sans suivre les redirections. Tester ensuite les parcours authentifiés admin/formateur/stagiaire/observateur, le SCORM classique et par blocs, les évaluations, le tableau blanc et les emails.
9. Surveiller erreurs, jobs en échec, disque, TLS et sauvegarde suivante. Conserver le journal de livraison et la release précédente.

### Retour arrière

Si un contrôle échoue, maintenir ou réactiver la maintenance. Si le schéma reste compatible, rebasculez `current` vers la cible précédente, rechargez PHP-FPM, redémarrez les workers et relancez les contrôles. Si le schéma est incompatible, appliquer le plan de restauration validé en préproduction et évaluer les écritures réalisées depuis la sauvegarde. Ne pas lancer un `migrate:rollback` générique en production : le retour du code n'annule pas les changements de données.

### Workers, scheduler et supervision

Configurer le gestionnaire de services réel (systemd ou Supervisor) avec le compte applicatif, le répertoire `current`, un redémarrage automatique et une journalisation restreinte. Commande de départ : `php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600`. Le timeout doit rester inférieur au `retry_after` du pilote et être adapté aux conversions longues. Vérifier les réglages des jobs avant de les mettre en service.

Configurer cron chaque minute pour `php artisan schedule:run` depuis `current`, sans doublon sur plusieurs machines. Déclencher un job de test en préproduction et vérifier son traitement après un redémarrage de release. Une sonde externe doit surveiller `/up`, le disque, le certificat, les échecs de jobs et la fraîcheur des sauvegardes. `/up` ne détecte pas un worker arrêté.

## Points restant à valider

### Résultats locaux du 22 septembre 2026

- `composer validate --strict`, `composer check-platform-reqs --no-dev`, `composer audit --locked --no-interaction` : réussis, aucune alerte Composer après correction (21 avant correction).
- `npm ci`, `npm audit --audit-level=moderate`, `npm run build` : réussis, aucune vulnérabilité npm signalée (42 dépendances signalées avant correction). npm prévient toujours de contraintes React anciennes dans des sous-dépendances Radix ; Vite signale des bundles volumineux.
- `php artisan test` sur la base MySQL de test : **377 réussis, 5 échecs**, contre **358 réussis, 5 échecs** avant les corrections. Les 19 nouveaux cas PHP passent. Les cinq échecs sont identiques : fixture image vide dans `VersionnageFormationCatalogueTest`, libellé PowerPoint dans `PowerPointModuleToolTest`, route `formateur.sondages.store` absente dans `PollSessionTest`, réponses 403 dans `ClozeQuestionTest` et `QuizQuestionMediaPersistenceTest`.
- `composer analyse` : **109 erreurs restantes**, aucune dans les nouveaux fichiers de durcissement. La CI complète reste donc bloquante. Les erreurs touchent notamment le typage des relations du catalogue/parcours et les scopes Eloquent.
- `php artisan pint` n'existe pas dans cette installation ; le binaire `vendor/bin/pint` a été exécuté sur les fichiers PHP du correctif, avec succès.
- `node --test tests/JavaScript/*.test.js` : réussi. Imports et appels Nano ID/lodash-es vérifiés localement. Syntaxes JavaScript, Bash et YAML vérifiées ; le script HTTP a passé cinq simulations (succès, en-tête absent, service indisponible, redirection, connexion indisponible).
- `git diff --check` : réussi. Ces résultats ont été relevés avant publication sur GitHub. Les modifications préexistantes du dépôt sont conservées ; aucun déploiement de production n'a été réalisé.
- Non exécutés : workflow sur GitHub, contrôle HTTP sur la production réelle, validation visuelle des lecteurs/tableau blanc, sauvegarde et restauration sur l'hébergement.

### Décisions et interventions sur l'hébergement

- Topologie réelle, versions PHP-FPM/base, serveur HTTP, chemins persistants et méthode de transfert après GitHub.
- Sauvegarde/restauration réelle, préproduction, bascule et retour arrière, workers, cron et alertes externes.
- Arbitrage des inscriptions publiques, conservées dans cette modification ; politique des médias privés et rétention des données à décider avant migration ou suppression.
- Échecs préexistants de la suite et de PHPStan : ils restent bloquants pour une livraison, sans ajout de règles d'ignorance pour les masquer. Le retrait des cinq entrées de baseline visant `MesFormationsController.php`, fichier supprimé auparavant, permet à l'analyse de s'exécuter.

Références : [protection CSRF Laravel 13](https://laravel.com/framework/docs/13.x/csrf), [déploiement Laravel](https://laravel.com/framework/docs/deployment), [sécurisation des Actions GitHub](https://docs.github.com/en/actions/reference/security/secure-use).

[Retour au wiki](README.md)
