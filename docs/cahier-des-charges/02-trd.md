# 02 — TRD (Technical Requirements Document)

**But :** expliquer comment la solution technique respecte les exigences du PRD.\
**Public :** équipe de développement, sécurité et exploitation.\
**Statut :** synthèse de l'existant et décisions à confirmer.

## Architecture retenue

Oneduc est un monolithe Laravel organisé par rôles et progressivement par domaines métier. Les pages sont majoritairement rendues avec Blade ; Alpine.js gère les interactions légères et React les éditeurs riches. MySQL/MariaDB porte les données. Les outils live utilisent actuellement du polling HTTP plutôt que des WebSockets.

Le choix directeur est de renforcer le **monolithe modulaire**, pas de migrer vers des microservices sans besoin démontré. Les contrôleurs orchestrent les requêtes ; les règles réutilisables doivent rejoindre des actions, services ou policies testables.

## Contraintes techniques constatées

- runtime déclaré : PHP `^8.3` et Laravel `^13.0` dans `composer.json` ;
- frontend : Vite, Tailwind CSS, Alpine.js et React pour certaines surfaces ;
- base de référence MySQL, complétée par les migrations postérieures ;
- contenus pédagogiques SCORM 1.2/2004 et quiz natifs ;
- stockage de médias via Laravel Media Library ;
- certaines fonctions de génération utilisent des services externes configurables ;
- les dépendances et versions documentées dans le wiki peuvent être antérieures au code et doivent être réconciliées.

## Exigences techniques

| ID | Exigence | Preuve attendue |
|---|---|---|
| `TECH-001` | Toute ressource métier sensible applique authentification, rôle et appartenance côté serveur | Tests Feature positifs et négatifs |
| `TECH-002` | Toute modification de schéma est versionnée, compatible avec le plan de déploiement et réversible ou compensable | Migration relue + essai en préproduction |
| `TECH-003` | Les traitements multi-écritures critiques utilisent une transaction | Test de rollback sur erreur |
| `TECH-004` | Les entrées, fichiers et archives sont validés avant traitement | Tests de formats, tailles, traversal et contenu invalide |
| `TECH-005` | Les services externes ont timeout, gestion d'erreur, limite d'usage et journalisation sans secret | Tests avec doublures + métriques d'échec |
| `TECH-006` | Les parcours prioritaires ont une couverture de non-régression | Matrice exigence → test dans le lot concerné |
| `TECH-007` | Les changements frontend passent le build et ne dégradent pas l'accessibilité ciblée | Build, tests et contrôle clavier |

## Sécurité et confidentialité

- Refuser par défaut : ne jamais compter uniquement sur l'absence d'un bouton dans l'interface.
- Centraliser progressivement les décisions d'accès dans des policies ou gardes métier cohérentes.
- Protéger les routes publiques et de connexion contre les abus par limitation de débit.
- Ne pas journaliser mots de passe, codes d'accès, jetons, contenus sensibles ou données personnelles inutiles.
- Définir conservation, export et suppression par catégorie de données dans le Backend Schema.
- Traiter les contenus importés et les iframes comme des frontières de confiance.

## Performance et exploitation à chiffrer

Le TRD doit fixer, après mesure du pilote : nombre d'utilisateurs simultanés, temps de réponse par parcours, taille maximale des imports, fréquence du polling, objectifs de disponibilité, RPO/RTO, volume de stockage et politique d'archivage. Sans valeurs cibles, « rapide », « scalable » ou « haute disponibilité » ne sont pas des exigences testables.

## Décisions d'architecture à consigner

Chaque décision structurante doit avoir une fiche courte : contexte, options étudiées, décision, conséquences, date et responsable. Priorités actuelles :

1. stratégie d'autorisation centralisée ;
2. source canonique du calcul de progression ;
3. contrat et fréquence des outils temps réel ;
4. stratégie de version des formations et médias associés ;
5. politique de stockage, sauvegarde et purge ;
6. frontières entre contrôleurs historiques et domaines métier.

## Critère de sortie du TRD

Le document est prêt pour un lot lorsque chaque exigence produit de ce lot possède une solution, des risques identifiés, une stratégie de test, une stratégie de données et un mode de déploiement. Le TRD global peut rester vivant ; les décisions nécessaires au prochain lot, elles, ne doivent pas rester implicites.
