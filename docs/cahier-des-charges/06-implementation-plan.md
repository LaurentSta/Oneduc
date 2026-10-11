# 06 — Implementation Plan (plan de réalisation)

**But :** convertir les écarts validés en lots livrables, testables et déployables.\
**Entrées :** PRD validé, App Flows, UI/UX, TRD et Backend Schema du périmètre.

## Principe de planification

On ne réécrit pas toute l'application pour aligner les documents. Pour chaque flow prioritaire : mesurer l'existant, conserver ce qui satisfait les critères, corriger les écarts puis produire les preuves. Les estimations ne doivent être engagées qu'après ce diagnostic.

## Lots proposés

| Lot | Objectif | Dépendances | Preuves de sortie |
|---|---|---|---|
| `LOT-000` — Arbitrage | Valider publics, périmètre, indicateurs et propriétaires | Aucune | PRD signé, hypothèses et exclusions visibles |
| `LOT-001` — Traçabilité | Relier exigences, flows, données, écrans et tests | `LOT-000` | Matrice sans exigence prioritaire orpheline |
| `LOT-002` — Accès stagiaire | Fiabiliser `FLOW-001` de bout en bout | Maquettes et règles d'accès | Tests accès/erreurs, contrôle clavier, mesure utilisateur |
| `LOT-003` — Affectation | Fiabiliser `FLOW-002` et les autorisations de groupe | Policies/règles validées | Tests croisés, transaction, retour utilisateur clair |
| `LOT-004` — Progression | Unifier les règles de `FLOW-003` | Décision sur la source canonique | Tests quiz/SCORM/blocs, reprise et explication du statut |
| `LOT-005` — Publication | Sécuriser `FLOW-004` et les versions | Schéma versions/médias | Tests d'immuabilité, bascule explicite, rollback éprouvé |
| `LOT-006` — Exploitation | Préparer pilote et production | Lots fonctionnels retenus | sauvegarde restaurée, supervision, runbook et bilan pilote |

Ce découpage est une proposition d'ordre, pas un calendrier approuvé. Les fonctions hors des flows prioritaires restent dans le backlog tant que leur valeur et leur coût ne sont pas arbitrés.

## Fiche obligatoire pour chaque lot

- objectif et exigences liées ;
- comportement actuel mesuré ;
- écart à corriger et éléments explicitement exclus ;
- conception UI/UX approuvée ;
- décisions techniques et de données ;
- tâches petites, ordonnées et attribuées ;
- migrations, compatibilité et reprise de données ;
- tests automatisés, contrôles manuels et test utilisateur ;
- observabilité, déploiement, feature flag éventuel et retour arrière ;
- responsable d'acceptation et preuve finale.

## Portes de qualité

### Avant développement

- critères d'acceptation non ambigus ;
- cas d'erreur et permissions définis ;
- dépendances et risques identifiés ;
- données personnelles minimisées ;
- aucune décision bloquante cachée dans une tâche.

### Avant fusion

- revue de code et de sécurité proportionnée au risque ;
- tests liés aux exigences au vert ;
- formatage, analyse statique et build au vert ;
- documentation et migrations relues ;
- vérification visuelle et accessible pour tout changement perceptible.

### Avant production

- sauvegarde et restauration vérifiées ;
- migrations répétées en préproduction ;
- métriques et alertes disponibles ;
- procédure de retour arrière réaliste ;
- responsable de décision de mise en production identifié.

## Risques initiaux

| Risque | Réduction proposée |
|---|---|
| Documentation datée par rapport au code | Refaire les mesures et dater les validations |
| Autorisations dispersées | Matrice d'accès puis centralisation progressive testée |
| Multiples sources de progression | Définir une règle canonique et conserver la provenance |
| Contrôleurs historiques volumineux | Extraire par flow, sans refonte globale non testée |
| Accessibilité supposée mais non mesurée | Audit ciblé et tests avec les publics concernés |
| Migration irréversible | Stratégie expand/contract et restauration répétée |

## Définition de « terminé »

Un lot est terminé quand le comportement accepté est disponible dans l'environnement cible, observé, documenté et réversible selon le niveau de risque. « Le code est fusionné » n'est qu'une étape, pas le résultat produit.
