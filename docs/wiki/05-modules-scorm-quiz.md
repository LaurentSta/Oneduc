# 05 — Modules, SCORM & Quiz

*Public : développeurs et administrateurs de contenu. Pour l'usage des builders et des quiz selon le rôle, voir [Profils utilisateurs](04-profils-utilisateurs.md).*

## Hiérarchie du contenu pédagogique

```
Module
 └── ModuleSection (chapitre)
      └── ModuleLecture (leçon)
           ├── Contenu SCORM (package + version)
           ├── Contenu slides
           ├── Quiz natif (QuizQuestion + options)
           ├── Blocs éditoriaux formateur (texte, image, vidéo, citation, séparateur, SCORM)
           ├── Contenu vidéo / URL
           ├── LectureObjective (objectifs pédagogiques)
           └── LessonResource (ressources téléchargeables)
```

---

## Modules

### Modèle `Module`

Fichier : `app/Models/Module.php`

Champs principaux :
- `module_title` / `module_name` / `module_name_slug` / `description` / `objectifs`
- `category_id` / `subcategory_id`
- `formateur_id` (formateur référent facultatif pour une formation officielle, propriétaire pour une création personnelle)
- `created_by` (auteur technique de la création ou de la version)
- `is_trainer_authored` (module créé par un formateur, exclu du catalogue public)
- `catalogue_key` / `version_number` (identité stable d'une formation officielle et numéro de version)
- `publication_state` (`draft`, `published`, `archived`) / `published_at`
- `source_module_id` (version ou création à l'origine de la copie)
- `status` (actif/inactif — modifiable par le formateur depuis le panneau Options du builder)
- `certificat`, `label`, `duree`, `resources`, `prerequi`, `module_video`, `module_image`, `header_image` (modifiables par le formateur depuis le panneau Options du builder)
- `bestseller`, `vedette`, `surevalue` (flags marketing catalogue admin uniquement, non exposés au formateur)
- `evaluation_id` (évaluation SCORM associée)

### Durée estimée intelligente

Le modèle calcule une durée pédagogique via :
- `getTotalSecondsAttribute()` — durée brute de toutes les leçons
- `getEstimatedSecondsForUser(int $userId)` — ajuste selon le nombre de tentatives passées du stagiaire

### Création des modules et visibilité

Trois flux coexistent pendant l'intégration du nouveau constructeur :

| Flux | Contrôleur | Usage |
|------|------------|-------|
| Catalogue admin historique | `Backend/ModuleController` | Éditeur admin actuellement conservé pour la continuité, notamment les flux SCORM/slides historiques |
| Catalogue admin moderne | `Backend/ConstructeurFormationController` | Plan continu, blocs, médias, audio, SCORM, quiz, IA, aperçu et cycle de versions des formations officielles |
| Modules formateur | `Formateur/ModuleBuilderController` | Création de modules personnels (avec structure d'exemple pré-remplie), plan continu chapitres/leçons, options du module, duplication d'un module catalogue, assignation aux groupes |

Les modules personnels ont `is_trainer_authored = true`. Ils sont visibles dans l'espace du formateur propriétaire et des groupes auxquels ils sont affectés, mais exclus de la liste publique (`Module::publiclyListable()`).

À la création (`CreerModule`), le module est pré-rempli avec une structure d'exemple : 2 chapitres, le premier avec 2 leçons, le second avec 1 leçon — pour éviter la page blanche et montrer le fonctionnement du plan continu. Le formateur est libre de renommer ou supprimer ces éléments.

Le flux manuel du constructeur admin est différent : `CreerModule::creerFormationCatalogueVide()` crée un brouillon officiel, puis ajoute seulement un « Chapitre 1 », sans leçon. L'administrateur construit donc explicitement la structure destinée au catalogue. Il choisit la catégorie ; la sous-catégorie technique « Catalogue Oneduc » est créée ou retrouvée automatiquement dans cette catégorie, puis réalignée si la catégorie change.

### Génération de formation complète par IA

Un module peut aussi être créé automatiquement (chapitres, leçons, contenu) à partir d'un thème et/ou d'un document, via Mistral. Voir [Génération de contenu par IA](15-generation-ia.md).

---

## Builder formateur continu

Le builder formateur est séparé en deux niveaux :

| Niveau | Vue | Rôle |
|--------|-----|------|
| Plan du module | `resources/views/formateur/modules-builder/edit.blade.php` | Titre, description, chapitres, leçons ; panneau replié « Options du module » (média, paramètres, contenu) et « Groupes assignés » |
| Contenu de leçon | `resources/views/formateur/modules-builder/lecture-edit.blade.php` | Édition des blocs de contenu d'une leçon |

Le panneau Options + Groupes assignés est replié par défaut (un seul bouton d'ouverture) pour garder l'écran de création concentré sur le titre, la description et le plan. Les options du module (`ModuleBuilderController::updateOptions()` / `ModifierOptionsModule`) couvrent label, durée, temps estimé par question, certificat, actif, ressources, prérequis, objectifs pédagogiques (un par ligne, stocké en tableau — voir [Génération de contenu par IA](15-generation-ia.md)), vidéo, image d'en-tête et image principale — tous facultatifs. Les champs catalogue (catégorie, sous-catégorie, formateur, bestseller, vedette, valeur ajoutée) restent administrés par l'admin et n'apparaissent pas dans le builder. La catégorie est néanmoins visible sous le titre de chaque formation dans l'onglet **Catalogue** de `/formateur/formations`, et le panneau repliable **Filtrer** permet de limiter la liste à une catégorie sans alourdir la vue par défaut. Les créations personnelles utilisent la catégorie affichée **Formations formateurs** ; l'ancien libellé « Modules formateurs » est renommé automatiquement.

Le plan est monté via `[data-outline-editor]` et `resources/js/outline-editor/OutlineEditor.jsx`. Il utilise Tiptap/ProseMirror avec deux noeuds métier :
- `chapterHeading` pour les chapitres ;
- `lessonItem` pour les leçons.

Comportements implémentés :
- `Entrée` ajoute une nouvelle ligne de leçon ;
- `Maj+Entrée` transforme une leçon vide en chapitre ;
- `Alt+↑` / `Alt+↓` réordonnent les lignes (clavier) ;
- une leçon peut aussi être réordonnée **à la souris** via sa poignée ≡, uniquement à l'intérieur de son chapitre (glisser vers un autre chapitre est refusé) ;
- les boutons "+ Ajouter une leçon" / "+ Ajouter un chapitre" sous le plan ajoutent une ligne vide en fin de document (équivalent souris de `Entrée`) ;
- le renommage est sauvegardé avec debounce ;
- une leçon peut être déplacée entre chapitres ;
- la suppression d'un chapitre ou d'une leçon passe par une confirmation, et **un chapitre contenant des leçons ne peut pas être supprimé** (`destroySection()` renvoie une erreur 422 plutôt que de supprimer en cascade) ;
- une leçon avec contenu existant ne peut pas être promue en chapitre, pour éviter une perte de contenu ;
- chaque leçon/chapitre a un menu "•••" (au lieu d'icônes séparées) avec ses actions : Dupliquer (leçon uniquement) et Supprimer.

Icônes et logique de menu partagées entre `chapter-heading-node.js` et `lesson-item-node.js` via `row-controls.js` (un seul menu "•••" ouvert à la fois, quel que soit l'endroit). Les node views utilisent `ignoreMutation()` pour empêcher ProseMirror d'annuler les mutations DOM (classe `hidden` du menu, etc.) faites en dehors de `contentDOM`.

Endpoints JSON principaux :

| Action | Route nommée |
|--------|--------------|
| Créer / renommer / supprimer un chapitre | `formateur.modules.builder.sections.*` |
| Réordonner les chapitres | `formateur.modules.builder.sections.reorder` |
| Créer / renommer / supprimer une leçon | `formateur.modules.builder.lectures.*` |
| Dupliquer une leçon | `formateur.modules.builder.lectures.duplicate` |
| Réordonner les leçons d'un chapitre | `formateur.modules.builder.lectures.reorder` |
| Déplacer une leçon vers un autre chapitre | `formateur.modules.builder.lectures.move` |
| Transformer une leçon vide en chapitre | `formateur.modules.builder.lectures.promote` |

Le renommage d'une leçon depuis le plan ne doit pas écraser `content_blocks`. `ModuleBuilderController::updateLecture()` ne modifie donc le contenu que si la clé `content_blocks` est réellement envoyée.

`DupliquerLecon` clone une leçon (`Model::replicate()`) avec tous ses champs — titre suffixé par « (copie) », insérée juste après l'originale (positions recalculées pour tout le chapitre). Fonctionne aussi pour les leçons SCORM/slides verrouillées, qui pointent alors vers le même paquet importé que l'original.

Les leçons SCORM ou slides issues d'une duplication catalogue restent présentes dans la copie formateur. Leur contenu importé est affiché comme verrouillé sur la page d'édition de leçon ; le formateur peut seulement renommer la leçon.

### Génération de leçon par IA

Depuis l'écran du plan de module, un bouton « + Générer une leçon (IA) » permet d'importer un document pour pré-remplir une leçon dans un chapitre donné. Voir [Génération de contenu par IA](15-generation-ia.md) pour le détail (flux, garde-fous, configuration).

---

## Constructeur admin moderne — phase d'intégration

Le constructeur sous `/admin/formations/constructeur` réutilise les mêmes composants d'édition du plan et des blocs que le builder formateur, avec des routes et des contrôles d'accès propres à l'administration. Il ajoute les responsabilités du catalogue : choix de la catégorie, classification technique automatique, formateur référent facultatif, aperçu, publication, archivage et bascule de groupes.

> Ce constructeur ne remplace pas encore l'éditeur admin historique. Les deux flux coexistent pendant les tests de parité ; aucun cutover n'est documenté à ce stade.

### Cycle de vie d'une formation officielle

```text
Brouillon (`draft`)
  ├── édition, aperçu et suppression autorisés
  └── publication explicite
        ↓
Publiée (`published`)
  ├── immuable
  ├── assignable et visible au catalogue
  └── nouvelle version → copie brouillon avec même `catalogue_key`
        ↓
Archivée (`archived`)
  ├── retirée des nouvelles affectations
  └── encore lisible par les groupes déjà épinglés dessus
```

La publication vérifie au minimum le titre, la catégorie et sa sous-catégorie technique, l'existence d'un chapitre, la présence d'au moins une leçon dans chaque chapitre et un contenu publiable dans chaque leçon. La sous-catégorie est gérée automatiquement par le constructeur : elle n'est pas demandée à l'administrateur. Lors de la publication d'une nouvelle version, l'ancienne version publiée est archivée mais reste active pour ses groupes existants.

Les groupes ne suivent jamais automatiquement la dernière version. `BasculerGroupesVersionFormation` remplace uniquement, à la demande de l'administrateur, la version utilisée par les groupes sélectionnés et conserve la position du module. Si un parcours est partagé par plusieurs groupes, une copie du parcours est créée pour éviter de modifier le programme des autres groupes.

### Copies et intégrité des contenus

`CreerVersionFormationCatalogue` réalise une copie profonde des chapitres, leçons, objectifs et compétences, quiz et options, ressources de leçon et médias Spatie. Les images de couverture et d'en-tête, les médias de quiz et les fichiers de ressources sont copiés dans des emplacements propres à la nouvelle formation. Les répertoires et sources de slides sont également dupliqués.

Pour une leçon SCORM complète, la copie est épinglée sur la version de package résolue au moment de la duplication. À la publication, toute leçon encore configurée pour suivre la version SCORM active est figée sur une `ScormPackageVersion` précise. Les blocs SCORM issus d'une même lignée de catalogue restent autorisés via `AccesScormVersionne`, sans ouvrir l'accès à un package arbitraire.

L'administration peut consulter une création personnelle de formateur, mais pas modifier son original. L'action de duplication produit un nouveau brouillon officiel, avec une nouvelle `catalogue_key`, dont les changements futurs sont indépendants. Réciproquement, un formateur qui adapte une formation officielle travaille dans une copie personnelle indépendante ; un éventuel formateur référent ne reçoit pas de droit d'écriture sur le master.

### Quiz et médias

Les routes `admin.formations.constructeur.lectures.quiz.questions.*` exposent dans le constructeur la création, la modification, la suppression, l'import CSV et la génération IA des questions. L'éditeur de leçon conserve les blocs texte, image, audio, vidéo et SCORM ; l'aperçu sait aussi ouvrir les leçons SCORM ou slides historiques dans leur rendu natif. Ce maintien natif évite une conversion destructive des contenus existants.

---

## Sections et Leçons

### Lecture formateur allégée

Les vues formateur de chapitre et de leçon (`resources/views/formateur/formations/chapitre.blade.php` et `resources/views/formateur/formations/lecon.blade.php`) privilégient désormais une lecture calme par défaut :
- le contenu ou le point d'entrée du chapitre occupe toute la largeur utile ;
- la vue stagiaire s'ouvre dans la prévisualisation anonyme (`anonymous=1`) au lieu d'être mélangée à la vue formateur ;
- les objectifs, quiz/corrigés, ressources et outils numériques sont rangés dans un panneau latéral ouvert à la demande ;
- la liste des ressources reste consultable rapidement, tandis que l'ajout, la visibilité stagiaire et la suppression sont repliés derrière l'action "Gérer" ;
- les actions d'animation utilisent le contexte courant (`mode`, `group_id`, module, chapitre, leçon) pour éviter au formateur de ressaisir des choix déjà connus.

Objectif UX : réduire la charge cognitive du formateur en séance sans retirer les contrôles pédagogiques existants.

### Types de contenu d'une leçon

| Type | Champ | Description |
|------|-------|-------------|
| SCORM | `scorm_path`, `ScormPackageVersion` | Package SCORM importé avec versioning |
| Slides | `slides_path`, `slides_status` | Présentation convertie et hébergée |
| Quiz natif | `quiz_enabled`, `quiz_questions_per_attempt` | Questions et réponses gérées nativement |
| Quiz live | `live_quiz_entry_enabled` | Entrée vers une session live depuis la leçon |
| Vidéo / URL | `url`, `module_video`, chemins via `config/learning_assets.php` | Lien externe ou vidéo intégrée |
| Blocs formateur | `content_type = blocks`, `content_blocks` | Contenu éditorial structuré en JSON |

### Blocs éditoriaux formateur

Le builder formateur sauvegarde les leçons en `content_blocks`. Les blocs acceptés sont :
- `text` avec HTML limité, édité via TipTap (`resources/js/formateur-module-builder-editor.jsx`) : gras, italique, souligné, barré, code en ligne, titres H1-H4, liste à puces/numérotée, lien, undo/redo. Le rendu (édition et lecture) utilise la classe CSS `.rich-text-content` (`resources/css/app.css`) pour styler ces balises en l'absence du plugin `@tailwindcss/typography` ;
- `image` référencée par `media_id` (voir ci-dessous) ;
- `video` : URL YouTube/Vimeo/fichier direct (voir ci-dessous) ;
- `quote` ;
- `divider` ;
- `scorm` : package SCORM importé, isolé du reste de la leçon (voir « SCORM en tant que bloc de leçon » plus bas).

Il n'y a pas de bloc `list` séparé — les listes se font uniquement via le bloc `text` (boutons dédiés dans la barre d'outils TipTap). Ce bloc a existé un temps comme type à part entière avant d'être retiré au profit de la liste intégrée au texte.

`NettoyeurBlocsModule::sanitizeBlocks()` limite les blocs à 100 éléments, supprime les balises/scripts non autorisés (liste blanche : `p br strong b em i u s ul ol li h1-h4 blockquote a code pre`) et vérifie que chaque `media_id` d'un bloc image correspond bien à un media Spatie rattaché au module courant.

#### Images des blocs (Spatie Media Library)

Les images des blocs de leçon sont gérées par `spatie/laravel-medialibrary` (table `media`), rattachées au modèle `Module` (collection `lesson-images`) et non à la leçon individuelle — une image envoyée depuis une leçon reste réutilisable dans les autres leçons du même module.

- Upload : `TeleverserImageModule::execute()` fait `$module->addMedia($file)->toMediaCollection('lesson-images')`, appelé depuis `ModuleBuilderController::uploadImage()`.
- Conversion `display` (définie dans `Module::registerMediaConversions()`) : redimensionnement max 1600×1600 (`Fit::Max`), générée de façon synchrone (`nonQueued()`).
- URLs : un générateur d'URL custom (`App\Domains\ModulesFormateur\Support\MediaStorageUrlGenerator`, configuré dans `config/media-library.php`) fait passer les médias par la route existante `/media/storage/{path}` plutôt que par le lien symbolique `storage:link` — cohérent avec le reste du projet, qui n'utilise pas ce lien de façon fiable.
- Seul `media_id` est persisté dans `content_blocks` (jamais l'URL, qui est régénérée à chaque affichage via `DonneesModule::resolvedContentBlocks()` côté édition et directement dans `lecture_blocks.blade.php` côté lecture).
- Portée volontairement limitée à ce bloc : les autres uploads d'image de l'app (avatars, badges, groupes, catégories, questions de quiz, couverture de module) continuent à utiliser leur `Storage::storeAs()` respectif, non migrés.

#### Vidéo des blocs (URL ou upload de fichier)

Le bloc `video` stocke une URL brute dans `content_blocks` (pas de `media_id`) : `App\Domains\ModulesFormateur\Support\ClassifieurUrlVideo::classify()` reconnaît les liens YouTube, Vimeo et fichiers directs (`.mp4`/`.webm`/`.ogg`), et calcule l'URL d'embed. Toute URL non reconnue est rejetée par le sanitizer (pas d'iframe vers un domaine arbitraire).

Deux façons d'obtenir cette URL côté formateur :
- coller un lien YouTube/Vimeo/fichier existant ;
- téléverser un fichier vidéo directement (`TeleverserVideoModule`, collection Spatie `lesson-videos` sur `Module`, mêmes principes que les images). L'upload retourne l'URL servie via `/media/storage/{path}` et la place automatiquement dans le champ URL — elle est ensuite traitée exactement comme un lien de fichier direct par le classifieur (aucune distinction stockée entre vidéo hébergée et lien externe).
- Limite : 100 Mo par fichier, appliquée à deux niveaux qui doivent rester cohérents entre eux :
  - Laravel : `ModuleBuilderController::uploadVideo` valide `max:102400` (Ko) ;
  - Spatie Media Library : `config/media-library.php` → `max_file_size` (relevé à 100 Mo ; la valeur par défaut du package est 10 Mo et rejette silencieusement tout fichier plus gros avec une `FileIsTooBig` exception, à surveiller si la limite est remontée un jour).
  - Cote PHP/Apache, `public/.htaccess` fixe déjà `upload_max_filesize`/`post_max_size` à 512/520 Mo (`AllowOverride All` sur le vhost) — largement suffisant, aucune modification serveur nécessaire pour rester sous 100 Mo.
- Pas de transcodage/miniature : `ffmpeg` n'est pas installé sur le serveur, le fichier est servi tel quel.

Le rendu se fait en iframe responsive (`aspect-video`) pour YouTube/Vimeo, ou en balise `<video>` native pour un fichier direct (uploadé ou lien externe).

#### SCORM en tant que bloc de leçon

Contrairement au SCORM « pleine leçon » (`content_type = scorm`, voir plus bas), le bloc `scorm` permet de mélanger un ou plusieurs packages SCORM avec du texte/image/vidéo dans une même leçon. Le runtime SCORM (`public/scorm_core/js/API.js`) suppose historiquement qu'une page = un seul package ; ce mode est rendu possible sans toucher au comportement existant grâce à trois choix d'isolation :

- **Suivi séparé** : les tables `content_block_scorm_scores` / `content_block_scorm_results` (miroirs de `scorm_scores`/`scorm_results`, mais uniques sur `(user_id, lecture_id, content_block_key)`) évitent de casser les 5+ endroits du code qui supposent une seule ligne `scorm_scores` par `(user_id, lecture_id)`.
- **Dossier d'import scopé par module** : chaque bloc importe son ZIP dans `modules/00_Lecons_blocks/module_{moduleId}/block_{content_block_key}/` (`LearningAssetPath::lessonBlockScormFolder()`), jamais dans `modules/00_Lecons/lecture_{id}/` utilisé par l'import SCORM admin classique — donc aucun risque qu'un import écrase l'autre.
- **Contexte isolé via une page wrapper** : le bloc s'affiche dans une iframe pointant vers `lecture.scorm-block` (`Frontend\LectureController::showScormBlock`), qui rend `resources/views/shared/scorm_block_wrapper.blade.php`. Ce document définit `window.SCORM_CONTEXT = {embedded: true, lecture_id, content_block_key, is_already_done}` sur **sa propre fenêtre**, puis imbrique le vrai package SCORM dans une iframe interne. Comme `API.js` lit `window.parent.SCORM_CONTEXT`, le package voit le contexte du wrapper et non celui, déjà défini, de la page de leçon englobante.

Dans `API.js`, `envoyerProgression()` poste vers `/scorm/save-block-progress` (`ContentBlockScormController::saveProgress`) dès que `content_block_key` est présent dans le contexte, et `afficherBoutonSuivantDepuisIframe()` ne fait rien si `context.embedded === true` — un bloc ne pilote pas le bouton « leçon suivante » de la leçon englobante (ambigu s'il y a plusieurs blocs). Ces deux branches ne s'activent que si les nouveaux champs sont présents : le SCORM pleine-page existant ne les envoie jamais, donc son comportement est inchangé.

Sécurité : `content_block_key` est généré côté client (`crypto.randomUUID()`, avec repli si non disponible) et validé côté serveur (`^[A-Za-z0-9_-]{8,64}$`) avant de servir de nom de dossier. `NettoyeurBlocsModule` revérifie, à chaque sauvegarde, que le `ScormPackageVersion` référencé par le bloc a bien été importé dans le dossier attendu pour ce module+clé (`ScormPackageVersion.folder`), ce qui sert de preuve de propriété sans table de correspondance supplémentaire.

Duplication : `DupliquerLecon` régénère une nouvelle `content_block_key` et vide `scorm_package_version_id` pour chaque bloc `scorm` de la copie — sans ça, l'originale et la copie partageraient le même dossier/package, et un réimport sur l'une écraserait l'autre. La leçon dupliquée affiche donc des blocs SCORM « vides » à réimporter.

### Personnalisation par groupe

La table `group_module_lectures` permet à chaque formateur de :
- **Masquer** une leçon pour son groupe
- **Réordonner** les leçons d'un module pour un groupe

Géré par `Formateur/GroupeModuleLessonController`.

---

## SCORM

### Import et versioning

Fichier : `app/Services/Scorm/ScormImporter.php`

Le processus d'import :
1. Validation du ZIP (taille max 500 Mo, format valide)
2. Extraction sécurisée dans `release_YYYYMMDD_HHMMSS_{random}` (protection path traversal via `safeExtract()`)
3. Détection du point d'entrée (`imsmanifest.xml` ou `index.html` fallback)
4. **Injection automatique** de `/scorm_core/js/API.js` dans la page d'entrée
5. Création/mise à jour d'un `ScormPackage` (slug stable) et d'une `ScormPackageVersion`

Le `ScormPackageVersion` expose un `getScormCacheTokenAttribute()` basé sur `imported_at` — cela force le navigateur à recharger l'API.js après chaque réimport, évitant les problèmes de cache.

### Runtime SCORM côté navigateur

| Fichier | Standard | Comportement |
|---------|----------|--------------|
| `public/scorm_core/js/API.js` | SCORM 1.2 | Expose `window.API`, envoie toutes les paires clé/valeur via `fetch()` vers `/scorm/save-progress` |
| `public/scorm_core/js/api_Scorm2004.js` | SCORM 2004 | Expose `window.API_1484_11`, stocke en mémoire, **ne poste pas** les interactions au backend |

### Routes SCORM

| Route | Méthode | CSRF | Authentification |
|-------|---------|------|-----------------|
| `/scorm/save-progress` | POST | Activé | `auth` middleware |
| `/scorm/progress` | POST | Activé | `auth` middleware |
| `/scorm/save-block-progress` | POST | Activé | `auth` middleware — bloc SCORM de leçon mixte |
| `/lecture/{id}/scorm` | GET | — | `auth` middleware |
| `/lecture/{id}/scorm-block/{key}` | GET | — | `auth` middleware — page wrapper d'un bloc SCORM |
| `/scorm/evaluation-progress` | POST | Activé | `auth` middleware |

Les pages parentes transmettent leur jeton aux wrappers de même origine, y compris celui des blocs SCORM imbriqués. Voir [Sécurité et préparation du déploiement](19-securite-deploiement.md) pour les contrôles et la validation des anciens packages avant livraison.

### Données persistées

**Pour les leçons :**

| Table | Contenu |
|-------|---------|
| `scorm_results` | Log brut de toutes les paires clé/valeur envoyées |
| `scorm_scores` | `first_score`, `best_score`, `last_score`, `lesson_status`, `is_completed`, `session_time`, `attempts_count` |
| `scorm_interactions` | Questions/réponses SCORM — **non alimenté pour les leçons** (bug connu) |
| `content_block_scorm_results` / `content_block_scorm_scores` | Mêmes colonnes que ci-dessus + `content_block_key`, uniques sur `(user_id, lecture_id, content_block_key)` — utilisées uniquement par les blocs `scorm` d'une leçon mixte (voir plus haut) |

**Pour les évaluations :**

| Table | Contenu |
|-------|---------|
| `scorm_evaluation_results` | Log brut évaluation |
| `scorm_evaluation_scores` | Scores évaluation |
| `scorm_evaluation_interactions` | Questions/réponses évaluation — **alimenté** via `EvaluationSCORMController` |

### Règles de complétion SCORM

| Contexte | Seuil de réussite | Configurable |
|----------|------------------|--------------|
| Leçons (`SCORMController`) | Score >= 50% | Non |
| Évaluations (`EvaluationSCORMController`) | Score >= 75% | Non |

Le statut est **monotone** : une leçon une fois marquée `completed` ou `passed` ne peut pas rétrograder.

### Limites SCORM connues

- `scorm_interactions` vide pour les leçons → les métriques de questions/réponses dans les dashboards sont structurellement vides pour le contenu SCORM
- `last_session_time` utilisé dans `SCORMController` mais pas migré → le cumul du temps de session est défaillant (corrigé dans `content_block_scorm_scores`, qui a une vraie colonne `last_session_time` — bug non reproduit pour les blocs SCORM)
- `attempts_count` initialisé à 1 et jamais incrémenté → toujours affiché à 1
- SCORM 2004 : les interactions ne sont pas postées au backend

---

## Quiz natifs

### Architecture

Les quiz natifs sont construits sur quatre modèles :

| Modèle | Table | Rôle |
|--------|-------|------|
| `QuizQuestion` | `quiz_questions` | Question avec type, médias, objectifs |
| `QuizOption` | `quiz_options` | Options de réponse |
| `QuizAttempt` | `quiz_attempts` | Tentative d'un utilisateur sur un quiz |
| `QuizAttemptQuestion` | `quiz_attempt_questions` | Réponse par question avec temps et résultat |

### Types de questions supportés

- Choix unique
- Choix multiple
- Vrai/Faux
- Texte libre
- Cloze (texte à trous)
- Médias (image, audio) sur la question et les options
- `image_alt` et `audio_transcript` pour l'accessibilité

### Import par CSV

La banque technique est présentée au formateur sous le libellé plus direct **Questions de la formation**. Elle appartient à la préparation du contenu et non aux outils d'animation : le formateur y accède depuis **Formations → Mes créations**, depuis le bouton **Questions de la formation** d'une création, ou depuis l'onglet Quiz d'une leçon. La vue transversale `formateur.outils.quiz-questions.index` est conservée comme point d'entrée vers les questions de toutes ses formations, mais elle n'est plus affichée comme une tuile dans **Outils numériques**.

Une question préparée dans une leçon peut avoir deux usages distincts :

- le **quiz de validation**, réalisé en autonomie par le stagiaire après la leçon ;
- le **quiz en direct**, animé et piloté par le formateur pendant une session.

Les deux contrôleurs admin/formateur délèguent la validation, la construction des options/payload cloze et l'import CSV au service partagé `App\Services\QuizQuestionBuilder`, pour éviter la duplication. Les questions créées par un formateur sont tracées via `quiz_questions.created_by`.

### Scoring

Géré par `QuizService` :
- Score par question et score global
- Temps par question (`quiz_attempt_questions.time_spent_seconds`)
- Historique des tentatives avec approche "meilleure tentative" valorisée
- Seuil de réussite configurable par module

### Forces des quiz natifs par rapport au SCORM

Contrairement au SCORM, les quiz natifs alimentent complètement les tableaux de bord :
- Réponses détaillées par question
- Temps de réflexion moyen
- Identification des questions les plus échouées
- Distinction première tentative / meilleure tentative

---

## Sources de progression

La progression d'un stagiaire est multi-source, chaque format ayant sa propre table :

```
Quiz natif   → quiz_attempts + quiz_attempt_questions   (fiable, complet)
SCORM 1.2    → scorm_scores (scores, statut, temps)      (partiel — interactions vides)
SCORM 2004   → scorm_scores (scores, statut)             (très partiel — temps et interactions vides)
Vidéo        → video_segment_trackings                   (segmenté)
Manuel       → progressions                              (validation manuelle, sans preuve pédagogique)
```

Le service `LearningAnalyticsService` agrège toutes ces sources en un snapshot unifié par paire `(user_id, lecture_id)`.

---

[Retour au wiki](README.md)
