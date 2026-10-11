# Dossier de cadrage produit et technique Oneduc

Ce dossier transforme le « cahier des charges » en six documents complémentaires. Il ne s'agit pas de six façons de décrire la même chose : chaque livrable répond à une question différente et sert de contrat à une étape du projet.

> **Correction de vocabulaire :** les expressions « Lab Flow » et « Lubix design » sont comprises ici comme **App Flow** (parcours dans l'application) et **UI/UX Design** (conception de l'expérience et de l'interface).

## La formulation claire du besoin

> Nous voulons consolider le cahier des charges d'Oneduc à partir de l'existant. Il faut d'abord comparer le code et la documentation déjà disponibles avec les six livrables attendus — PRD, TRD, App Flow, UI/UX Design, Backend Schema et Implementation Plan — puis compléter chaque livrable sans recopier les autres. Le PRD décrit le besoin et les résultats attendus ; le TRD explique les choix techniques ; l'App Flow représente les parcours utilisateurs ; le document UI/UX fixe les règles d'expérience et d'interface ; le Backend Schema formalise les données et les contrats serveur ; enfin, l'Implementation Plan transforme les écarts validés en lots réalisables et vérifiables.

## Comparatif des six livrables

| Document | Question à laquelle il répond | Contenu attendu | Ce qu'il ne doit pas devenir | Références existantes | État initial |
|---|---|---|---|---|---|
| [PRD](01-prd.md) | Pourquoi et pour qui construit-on ? | Problème, utilisateurs, périmètre, exigences, indicateurs, critères d'acceptation | Une liste de tables ou de tâches techniques | Présentation, profils, roadmap, audits | Partiel et dispersé |
| [TRD](02-trd.md) | Comment le système satisfait-il le PRD ? | Architecture, contraintes, sécurité, intégrations, qualité, décisions techniques | Une répétition des fonctionnalités du PRD | Architecture, installation, sécurité | Détaillé, à réaligner avec le code actuel |
| [App Flow](03-app-flow.md) | Par quelles étapes chaque rôle atteint-il son objectif ? | Points d'entrée, écrans, décisions, erreurs, sorties | Une maquette graphique ou une liste de routes | Profils, groupes, modules, parcours formateur | Partiel, surtout narratif |
| [UI/UX Design](04-ui-ux-design.md) | Comment rendre ces parcours compréhensibles et accessibles ? | Principes, navigation, états, composants, responsive, accessibilité | Un simple nuancier | Design system, accessibilité, glossaire | Base solide, validation utilisateur à ajouter |
| [Backend Schema](05-backend-schema.md) | Quelles données et quels contrats portent le produit ? | Entités, relations, invariants, autorisations, API, rétention | Un export SQL illisible ou une architecture complète | Architecture, baseline SQL, migrations, modèles | Réel dans le code, vue métier incomplète |
| [Implementation Plan](06-implementation-plan.md) | Dans quel ordre livre-t-on les écarts ? | Lots, dépendances, responsables, preuves, déploiement et retour arrière | Une roadmap sans critères de sortie | Roadmap, audits et checklist de déploiement | Roadmap présente, découpage exécutable à formaliser |

## Ce qui existe déjà

Le dépôt contient beaucoup de matière, mais elle répond à des usages différents : documentation utilisateur, description de l'architecture, audits datés et feuille de route. Elle ne constitue donc pas encore, à elle seule, un cahier des charges traçable.

| Source | Apport au dossier | Précaution |
|---|---|---|
| [`01-presentation.md`](../wiki/01-presentation.md) | Vision, publics, proposition de valeur, fonctionnalités | Certains volumes et états sont datés |
| [`04-profils-utilisateurs.md`](../wiki/04-profils-utilisateurs.md) | Rôles, droits et capacités | Distinguer le comportement voulu du comportement historique |
| [`03-architecture.md`](../wiki/03-architecture.md) | Architecture Laravel, domaines, modèles et middleware | Vérifier chaque chiffre ou version avant d'en faire une exigence |
| [`09-design-system.md`](../wiki/09-design-system.md) | Règles visuelles et composants existants | Compléter avec recherche utilisateur et critères par parcours |
| [`10-securite-rgpd.md`](../wiki/10-securite-rgpd.md) et [`19-securite-deploiement.md`](../wiki/19-securite-deploiement.md) | Sécurité, données personnelles et exploitation | Les exigences légales doivent être validées par les responsables compétents |
| [`11-roadmap.md`](../wiki/11-roadmap.md) | Dette, priorités et trajectoire | Une intention n'est pas un engagement de livraison |
| `database/schema/mysql-schema.sql` et `database/migrations/` | Schéma réellement versionné | La baseline ne suffit pas : appliquer aussi les migrations postérieures |

Une mesure réalisée le **11 octobre 2026** relève 115 contrôleurs PHP, 82 modèles Eloquent, 365 vues Blade, 34 migrations et 115 fichiers de test. Le contrôle `php artisan route:list --json` déclare 667 routes dans l'environnement local disposant des dépendances Composer. Ces nombres sont des repères d'audit, pas des objectifs produit.

## Publication dans le wiki GitHub

Ce dossier constitue la source versionnée des documents de cadrage. Sa copie de lecture dans le wiki GitHub comprend une [page d'entrée](https://github.com/LaurentSta/Oneduc/wiki/20-cahier-des-charges) et six pages dédiées :

- [PRD — besoins produit](https://github.com/LaurentSta/Oneduc/wiki/20-01-besoins-produit) ;
- [TRD — exigences techniques](https://github.com/LaurentSta/Oneduc/wiki/20-02-exigences-techniques) ;
- [App Flow — parcours dans l'application](https://github.com/LaurentSta/Oneduc/wiki/20-03-parcours-application) ;
- [UI/UX — conception de l'interface](https://github.com/LaurentSta/Oneduc/wiki/20-04-conception-interface) ;
- [Backend Schema — schéma des données](https://github.com/LaurentSta/Oneduc/wiki/20-05-schema-donnees) ;
- [Implementation Plan — plan de réalisation](https://github.com/LaurentSta/Oneduc/wiki/20-06-plan-realisation).

Les pages du wiki renvoient au commit source et adaptent les liens à la navigation du wiki. Une modification commence dans ce dossier, puis est synchronisée vers le wiki après autorisation explicite. La publication ne vaut pas validation des hypothèses, des exigences ou du calendrier proposés.

## Ordre de construction recommandé

1. **Valider le PRD** : objectifs, publics, périmètre et résultats mesurables.
2. **Tracer l'App Flow** : vérifier que chaque exigence importante possède un parcours nominal et des cas d'échec.
3. **Valider l'UI/UX** : produire et tester les écrans nécessaires aux parcours, sans inventer de fonctionnalité hors PRD.
4. **Stabiliser le TRD et le Backend Schema** : choisir les solutions et formaliser données, droits et contrats.
5. **Découper l'Implementation Plan** : ne planifier que les écarts validés dans les documents précédents.

Les documents sont vivants, mais une décision ne doit être modifiée silencieusement. Toute évolution significative indique sa date, son auteur, son statut et les documents impactés.

## Règle de traçabilité

Utiliser des identifiants stables :

- `OBJ-xxx` pour un objectif produit ;
- `FR-xxx` pour une exigence fonctionnelle ;
- `NFR-xxx` pour une exigence non fonctionnelle ;
- `FLOW-xxx` pour un parcours ;
- `DATA-xxx` pour une règle de données ;
- `LOT-xxx` pour un lot de réalisation.

Chaque lot doit pointer vers au moins une exigence. Chaque exigence prioritaire doit pointer vers un parcours, une règle technique ou de données, et une preuve de validation. Cette chaîne évite de développer une solution qui ne répond à aucun besoin validé.

## Définition de « validé »

Un document n'est pas validé parce qu'il est rédigé. Il est validé lorsque :

- les décideurs et utilisateurs concernés l'ont relu ;
- ses hypothèses ouvertes sont visibles ;
- les désaccords sont arbitrés ou consignés ;
- ses critères sont mesurables ;
- son propriétaire et sa date de prochaine revue sont renseignés.
