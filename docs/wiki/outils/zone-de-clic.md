# Zone de clic

*Public : formateurs ; partie technique en fin de page pour les développeurs.*

**Statut au 14 juillet 2026** : développé côté code mais pas encore activé en production — voir le statut général sur la page [07 — Outils d'animation](../07-outils-animation.md).

Le formateur importe une image, dessine des zones à retrouver et nomme chaque composant : cette « zone de clic » rejoint sa bibliothèque. Créée une fois, elle sert partout : lancée comme jeu pour un groupe (les stagiaires cliquent au bon endroit ; score et réussite par composant remontent côté formateur) ou insérée dans une leçon comme activité autonome. La modifier dans l'outil la modifie dans toutes les leçons qui l'utilisent.

## En bref

| Modalité | Valeur |
|---|---|
| Rythme | Synchrone (session ouverte par le formateur) |
| Lieu | Présentiel ou distanciel |
| Participation | Individuelle, notée (score par composant) |
| Compte requis | Oui (utilisateur authentifié) |
| Activable/désactivable | `config('outils.composants.enabled')` / `OUTILS_COMPOSANTS_ENABLED` (`true` par défaut) |

## Comment ça marche

La page `/formateur/trouve-le-composant` s'ouvre sur la bibliothèque (« Mes zones de clic »), puis sur les jeux lancés.

1. **Créer** : « + Nouvelle zone de clic » ouvre un assistant en trois étapes — *Image* (titre et fichier), *Zones*, *Enregistrer*. Deux formes sont proposées, carré et rond, tracées régulières. Dès qu'elle est tracée, avant même d'être nommée, une zone se déplace par cliquer-glisser et s'ajuste par ses poignées : celles des côtés l'étirent en largeur ou en hauteur (un carré devient rectangle, un rond devient ovale), celles des coins agissent sur les deux à la fois. Chaque zone reçoit un nom et, si on veut, une description (500 caractères max). Il faut au moins deux zones, quinze au plus.
2. **Modifier** : le bouton « Modifier » d'une carte rouvre le même assistant sur l'image et ses zones. La modification vaut partout où la zone de clic est utilisée dans une leçon ; les jeux déjà lancés gardent la version jouée. Remplacer l'image efface les zones déjà dessinées.
3. **Lancer pour un groupe** : depuis la carte (ou à la dernière étape de la création), autant de fois qu'on veut. Chaque lancement crée un jeu avec son propre code d'accès, qu'on peut fermer ou rouvrir (`toggle`).
4. Les stagiaires rejoignent via `/oneduc/composant/{code}` et cliquent sur l'image pour désigner chaque composant demandé. Après chaque réponse, juste ou fausse, la zone est révélée avec sa description si elle en a une. Le score et la réussite par composant remontent côté formateur.
5. **Supprimer** : impossible tant qu'une leçon utilise la zone de clic. La carte indique « Utilisée dans N leçons » et les nomme.

### Dans une leçon

Dans l'atelier de conception d'une leçon, le bouton « Outil » propose « Trouve le composant » : le formateur choisit une de ses zones de clic, et le bloc ne garde qu'un **lien** vers elle (pas de titre ni de contenu à ressaisir). Le stagiaire y joue seul, sans code d'accès ni score enregistré. L'affichage est réduit à l'essentiel : la question « Trouvez : … », l'avancement, un « ? » qui rappelle la consigne et un bouton « Agrandir » pour jouer en pleine page. La description s'affiche après chaque réponse, et « Je ne sais pas » révèle la zone sans cliquer. Détail dans [05 — Modules, SCORM, quiz](../05-modules-scorm-quiz.md), section « Outil en tant que bloc de leçon ».

Limite connue : la bibliothèque n'existe que côté formateur, donc un administrateur n'a aucune zone de clic à choisir dans l'atelier.

## Dans quel contexte l'utiliser

- Vérifier la reconnaissance visuelle d'éléments sur un schéma, une interface logicielle, un plan ou une photo (repérer les organes d'une machine, les zones d'un formulaire, les parties d'un diagramme réseau...).
- Adapté aux formations à forte composante visuelle ou technique où le repérage précis compte autant que la définition théorique.

## Partie technique

**Routes formateur** (`routes/formateur.php`, préfixe `/formateur/trouve-le-composant`, nom `formateur.composants.`, `OutilsComposantController`) :

- bibliothèque : `index` ; assistant : `nouvelle` (`create`) et `enregistrees/{componentFinderActivity}/modifier` (`activities.edit`), tous deux sur la vue `composants_editor` ;
- `store` (enregistre, et lance un jeu si un groupe est choisi), `enregistrees/{componentFinderActivity}` en PUT (`activities.update`) et en DELETE (`activities.destroy`, refusé si une leçon l'utilise), `enregistrees/{componentFinderActivity}/lancer` (`activities.launch`) ;
- jeux lancés : `{componentFinderSession}` (show), `{componentFinderSession}/toggle`, `destroy`.

**Routes de participation** (`routes/web.php`, préfixe `/oneduc/composant`, sous middleware `auth`) : `home`, `resolveCode`, `join/{code}`, `submit` (throttle 60/min) (`ComponentFinderParticipationController`).

**Modèles / tables** :

- `ComponentFinderActivity` (`component_finder_activities`) : la zone de clic de la bibliothèque, propre à son formateur (`formateur_id`) — titre, image, zones. C'est la seule version du contenu.
- `ComponentFinderSession` (`component_finder_sessions`) : un jeu lancé pour un groupe. Il reçoit une **copie** du titre et des zones au lancement (pas de clé étrangère) : ses résultats restent lisibles même si la zone de clic change ensuite.
- `component_finder_attempts` : scores des stagiaires.
- Les leçons n'ont pas de table : le bloc porte `activite_id`. `OutilsLecon::usages('composants')` retrouve les leçons concernées par une recherche JSON dans `module_lectures.content_blocks`.

Chaque zone du JSON `zones` porte `label`, `description`, `shape`, `x`, `y`, `w`, `h` (position et taille en pourcentage de l'image). `shape` vaut `square` ou `circle` ; `rectangle`, `oval` et `triangle`, retirés du choix le 10 octobre 2026, restent acceptés et affichés.

**Images** : la zone de clic et les jeux lancés à partir d'elle partagent le même fichier (`component_finder/…` sur le disque public) ; il n'est supprimé que lorsque plus aucune ligne des deux tables ne le référence. Les leçons affichent directement l'image de la zone de clic.

---

[Retour au sommaire des outils](../07-outils-animation.md)
