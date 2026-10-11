# 04 — UI/UX Design

**But :** traduire les App Flows en expériences compréhensibles, cohérentes et accessibles.\
**Source visuelle actuelle :** le design system du wiki et les composants réellement utilisés dans les vues.

## Principes adaptés à Oneduc

1. **Une action principale par écran stagiaire.** La prochaine étape doit être visible sans comprendre l'organisation interne du LMS.
2. **Le vocabulaire dépend du rôle.** Côté stagiaire : « Ma formation » ; côté formateur : « Catalogue », « Créations » et « Parcours ».
3. **La simplicité ne supprime pas l'information.** Les erreurs, délais, états de sauvegarde et conséquences d'une action restent explicites.
4. **La couleur n'est jamais le seul signal.** Texte, icône et structure accompagnent les statuts.
5. **Le mobile et le clavier sont des cas nominaux.** Ils ne sont pas vérifiés seulement en fin de projet.
6. **Les interfaces d'administration peuvent être denses ; les interfaces d'apprentissage doivent rester guidantes.**

## Livrables par flow

Pour chaque App Flow prioritaire, produire :

- schéma basse fidélité de tous les écrans et embranchements ;
- prototype cliquable des étapes critiques ;
- textes réels des titres, aides, confirmations et erreurs ;
- états chargement, vide, succès, erreur, hors ligne et accès refusé ;
- comportement mobile, tablette et bureau ;
- ordre de tabulation, focus, annonces et alternative non visuelle ;
- compte rendu de test auprès d'utilisateurs représentatifs.

## Contrat de composant

Chaque composant partagé documente : usage, variantes, contenu obligatoire, états, comportement clavier, nom accessible et exemples à éviter. Les composants existants doivent être réutilisés avant de créer une nouvelle variante.

| Famille | Règle de conception |
|---|---|
| Navigation | Position et libellé stables dans un même espace ; page courante annoncée |
| Formulaire | Libellé visible, aide avant l'erreur, erreur liée au champ, saisie conservée |
| Action destructive | Conséquence nommée, confirmation proportionnée, annulation ou restauration si possible |
| Tableau | En-têtes sémantiques, filtres partageables, pagination serveur si nécessaire |
| Modal | Focus initial pertinent, boucle clavier, Échap, restitution du focus |
| Progression | Valeur textuelle en plus du visuel et origine du calcul compréhensible |
| Média pédagogique | Texte alternatif, transcription ou solution équivalente selon le média |

## Accessibilité et validation

La conformité ne se déduit pas de la présence de classes CSS. La validation combine : revue sémantique, navigation clavier, zoom et reflow, contraste, lecteur d'écran, réduction des animations et tests avec les publics concernés. Les contenus SCORM importés doivent être évalués séparément de l'enveloppe Oneduc.

## Critère de sortie UI/UX

Un flow est prêt pour développement lorsque tous ses états sont conçus, que les libellés sont validés, que les contraintes d'accessibilité sont explicites et que le prototype a été testé sur les tâches critiques. Une maquette du seul « happy path » n'est pas terminée.
