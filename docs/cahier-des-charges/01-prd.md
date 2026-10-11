# 01 — PRD (Product Requirements Document)

**But :** définir le produit à construire avant de choisir la solution technique.\
**Propriétaire proposé :** responsable produit Oneduc.\
**Statut :** trame préremplie à valider avec les parties prenantes.

## Vision et problème

Oneduc est un LMS de formation accompagnée centré sur le groupe et le formateur. Il doit permettre à des publics parfois peu à l'aise avec le numérique d'accéder simplement à leur formation, tout en donnant aux formateurs les moyens de créer, animer et suivre un parcours sans multiplier les outils externes.

Le problème à résoudre n'est donc pas seulement « diffuser des cours ». Il faut relier dans une expérience cohérente : contenu pédagogique, groupe, accompagnement humain, activités en séance et preuves de progression.

## Utilisateurs principaux

| Profil | Besoin essentiel | Résultat attendu |
|---|---|---|
| Stagiaire | Rejoindre et poursuivre sa formation sans obstacle technique | Comprendre immédiatement quoi faire et constater sa progression |
| Formateur | Préparer, affecter, animer et suivre les formations | Accompagner un groupe depuis un espace unique |
| Administrateur | Gouverner le catalogue, les comptes et la qualité | Maintenir une offre fiable, publiée et traçable |
| Observateur | Consulter un périmètre autorisé sans le modifier | Suivre l'avancement avec un accès en lecture seule |

## Objectifs produit proposés

- `OBJ-001` — Réduire les obstacles d'accès pour les stagiaires.
- `OBJ-002` — Donner au formateur une vue continue de la préparation au suivi.
- `OBJ-003` — Produire des données de progression compréhensibles et fiables.
- `OBJ-004` — Garantir que chaque rôle ne voit et ne modifie que son périmètre.
- `OBJ-005` — Rendre l'expérience utilisable sur mobile et accessible aux publics visés.

Les valeurs cibles restent à décider. Exemples d'indicateurs : taux de première connexion réussie sans aide, temps médian avant l'ouverture de la première leçon, taux de parcours terminés, nombre d'erreurs d'autorisation, réussite des tâches lors de tests utilisateurs.

## Périmètre fonctionnel actuel à confirmer

### Inclus

- authentification standard et accès stagiaire simplifié ;
- gestion des quatre rôles et de leurs espaces ;
- groupes, formateurs principaux, co-formateurs et affectations ;
- catalogue, créations formateur, chapitres, leçons et parcours ;
- contenus en blocs, quiz natifs et SCORM ;
- progression et tableaux de bord ;
- outils d'animation pédagogique ;
- administration, journalisation et cycle de publication ;
- accessibilité, sécurité, protection des données et exploitation.

### Hors périmètre tant qu'aucune décision n'est prise

- multi-organisation complet ;
- vente en ligne et facturation ;
- application mobile native ;
- remplacement du monolithe par des microservices ;
- promesse de conformité RGAA tant qu'un audit représentatif n'est pas achevé.

## Exigences initiales

| ID | Exigence | Priorité | Critère d'acceptation synthétique |
|---|---|---:|---|
| `FR-001` | Un stagiaire autorisé rejoint son espace avec le mode d'accès prévu | Must | Un scénario automatisé et un test utilisateur couvrent succès, code invalide et compte inactif |
| `FR-002` | Un formateur gère uniquement ses groupes et contenus autorisés | Must | Les contrôles serveur refusent les accès croisés, même avec une URL forgée |
| `FR-003` | Un formateur affecte un contenu ou un parcours à un groupe | Must | L'affectation est visible côté formateur et côté stagiaire concerné |
| `FR-004` | Le stagiaire reprend une leçon et sa progression est conservée | Must | La reprise fonctionne pour les formats supportés et l'état est expliqué à l'utilisateur |
| `FR-005` | L'administrateur publie une version sans modifier les groupes restés sur une version antérieure | Must | Un test prouve l'immuabilité et la bascule explicite |
| `FR-006` | L'observateur consulte seulement son périmètre | Should | Les vues et téléchargements hors périmètre sont refusés côté serveur |
| `NFR-001` | Les autorisations sont appliquées côté serveur | Must | Couverture par tests d'accès positifs et négatifs pour chaque ressource sensible |
| `NFR-002` | Les parcours prioritaires sont accessibles au clavier et compréhensibles | Must | Audit RGAA ciblé et tests avec utilisateurs représentatifs |
| `NFR-003` | Les opérations critiques sont traçables et restaurables | Must | Journal utile, sauvegarde testée et procédure de retour arrière documentée |

## Décisions encore nécessaires

1. Quels sont les trois parcours à optimiser en priorité ?
2. Quel public précis compose le pilote et avec quel volume ?
3. Quels indicateurs et seuils rendent le pilote concluant ?
4. Quelles fonctions existantes doivent être maintenues, masquées ou retirées ?
5. Quelles obligations contractuelles, Qualiopi, RGPD et RGAA s'appliquent réellement au contexte de déploiement ?

## Critère de sortie du PRD

Le PRD est prêt lorsque le périmètre, les priorités, les mesures de succès, les exclusions et les critères d'acceptation ont été arbitrés. Une fonctionnalité déjà codée n'est pas automatiquement dans le périmètre : sa valeur doit être confirmée.
