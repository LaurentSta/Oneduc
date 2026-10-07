# 10 — Sécurité & RGPD

*Public : développeurs et administrateurs système.*

## Durcissement du 22 septembre 2026

Les exceptions CSRF des routes SCORM ont été supprimées après ajout des jetons manquants dans les pages parentes et le wrapper d'évaluation. Les réponses Laravel portent des en-têtes de sécurité compatibles avec les iframes internes. `/up` vérifie désormais la base et le cache ; une commande contrôle la configuration avant livraison. Voir [Sécurité et préparation du déploiement](19-securite-deploiement.md) pour les tests, les limites, les dépendances corrigées et la procédure de production. Les inscriptions publiques et la politique de conservation restent à arbitrer.

## Authentification

### Mode standard
Email + mot de passe (bcrypt via Laravel). Le middleware `role` vérifie simultanément :
- que l'utilisateur est authentifié
- que son `role` correspond à l'espace demandé
- que son compte est actif (`users.status`)

`LoginRequest` ajoute également `status = true` aux identifiants transmis à `Auth::attempt()`. Un compte dont `users.status = false` ne peut donc pas ouvrir de session par email, même avec le bon mot de passe. Il reçoit l'erreur générique d'authentification et reste déconnecté.

### Mode code d'accès (stagiaire)
Route : `POST /stagiaire/connexion-code`  
Contrôleur : `UserController::loginByCode()`  
Fonctionnement : le stagiaire saisit uniquement un code alphanumérique 6 caractères (`users.code_acces`). Aucun email ni mot de passe n'est requis.

Throttling : rate limiter nommé `connexion-code` (10 tentatives/minute par IP, 429 au-delà), défini dans `AppServiceProvider` et appliqué à la route depuis le 5 juillet 2026.

### Première connexion
Le middleware `ForcePasswordChange` bloque l'accès à l'espace stagiaire tant que `users.password_changed_at` est nul, sauf pour les routes `/stagiaire/premiere-connexion`. L'utilisateur est redirigé vers un formulaire de changement de mot de passe (validation `min:8`). Au 5 juillet 2026, ce middleware n'est pas appliqué à l'espace formateur.

---

## Politique d'adhésion formateur

Le middleware `EnsureAssociationMembership` (`app/Http/Middleware/EnsureAssociationMembership.php`) vérifie pour chaque requête formateur :

```
SI adhesion_status = 'active' ET adhesion_valid_until > aujourd'hui → Accès autorisé
SI adhesion_status = 'pending' ET created_at + 30 jours > aujourd'hui → Accès autorisé (grâce)
SINON → Redirection vers /adhesion
```

C'est ce mécanisme qui fait tenir le modèle associatif : l'accès formateur reste conditionné à une adhésion à jour.

---

## Middleware de journalisation admin

`RecordAdminActivity` (`app/Http/Middleware/RecordAdminActivity.php`) journalise automatiquement toutes les actions d'écriture (POST/PUT/PATCH/DELETE) des routes `admin.*` dans `activity_journal_entries` :

- Action horodatée avec l'ID de l'admin
- Uniquement les réponses réussies, dont le code HTTP est inférieur à 400
- Données de la requête sanitisées (champs sensibles exclus, textes longs tronqués)
- Consultable dans l'interface de pilotage admin

Pour les nouveaux formulaires de gestion des formateurs et stagiaires, le contexte exclut les champs nominatifs et de contact (`prenom`, `name`, `username`, `email`, téléphone, adresse, société), les mots de passe et le code d'accès. Les champs nécessaires au suivi opérationnel, par exemple `role`, `status`, `formateur_id` ou les identifiants de groupes, peuvent encore être enregistrés.

---

## Gestion des mots de passe et données sensibles

| Donnée | Protection |
|--------|-----------|
| Mots de passe utilisateurs | Hash bcrypt via Laravel (irréversible) |
| Code d'accès temporaire groupe | Chiffrement via cast Laravel `encrypted` (réversible pour affichage) |
| Codes d'accès stagiaires | Générés aléatoirement par `CodeGeneratorService` (`users.code_acces`) |
| Codes d'accès outils live | Générés par les contrôleurs d'outils (`access_code` sur les sessions) |

---

## Risques de sécurité identifiés (analyse mise à jour le 14 juillet 2026)

### Points corrigés depuis l'ancien audit

| Point | État vérifié |
|-------|--------------|
| Route `/admin/stagiaires/{id}/debug-progression` | Aucune route correspondante trouvée dans `php artisan route:list --json` |
| `POST /admin/stagiaires/{user}/reset-progression` | Route présente dans `routes/admin.php`, protégée par `auth`, `role:admin`, `admin.activity` |
| Tests liés au middleware `association.member` | La suite du 5 juillet passe sur ces scénarios |
| `/inscription`, connexion par code, `LessonFeedbackController::store()`, `Module::isVisibleTo()`, SCORM (`save-progress`/`save-block-progress`/`evaluation-progress`), `last_session_time`, contrat d'upload image du builder | Corrigés le 5 juillet 2026 (voir [Checklist de publication](13-publication-github.md), Axe 1, S3 à S9). |
| Connexion email d'un compte inactif | `LoginRequest` exige désormais `status = true` pendant l'authentification |
| Unicité de l'email du profil administrateur | `AdminController::AdminProfilStore()` utilise `Rule::unique(...)->ignore($user->id)` |
| Données personnelles des nouveaux formulaires utilisateurs dans le journal admin | Les champs nominatifs, coordonnées, mots de passe et codes d'accès sont exclus du contexte journalisé |
| Remise à zéro partielle de la progression | L'opération est transactionnelle et couvre désormais quiz, progression, vidéo, SCORM classique, évaluations et blocs SCORM modernes |
| Import mort `ScormInteractionController` | Supprimé de `routes/scorm.php` dès le correctif S7 du 5 juillet 2026 |
| `StoreModuleRequest::authorize()` et `StoreGroupeRequest::authorize()` retournaient `false` | Corrigé le 14 juillet 2026 — `authorize()` retourne désormais `true`, conforme à la convention des autres FormRequests du projet (`ContactRequest`, `LoginRequest`, `ScormImportRequest`) : l'autorisation réelle reste au middleware de route, pas au FormRequest |
| Gap `Module::isVisibleTo()` — `StagiaireController::StagiaireModuleDetail()` et `Frontend\LectureController` | `Frontend\LectureController` appelait déjà `isVisibleTo()` via `assertCanViewLecture()` depuis le 8 juillet 2026 (`show`, `showScorm`, `showScormBlock`, `showSlides`, toutes derrière le middleware `auth`). `StagiaireModuleDetail()` ne l'appelait toujours pas ; corrigé le 14 juillet 2026 avec un `abort_unless($module->isVisibleTo($user), 403)`, couvert par un nouveau test dans `tests/Feature/ModuleVisibilityTest.php` |
| Modèle d'autorisation incohérent entre les 7 outils interactifs (point 12 ci-dessous) | Décision produit tranchée le 14 juillet 2026 : uniformiser vers authentification + appartenance groupe. Nuage de mots et Roue aléatoire suivent désormais le même pattern que Tableau blanc/Minuteur (`$group->students()->where('users.id', auth()->id())->exists()`), routes sous middleware `auth` dans `routes/web.php`. Couvert par 4 nouveaux tests de refus d'accès (`tests/Feature/Stagiaire/WordCloudAccessTest.php`, `tests/Feature/Formateur/RandomWheelNamesTest.php`) |

### Corrections du 7 juillet 2026

| Point | Correctif |
|-------|-----------|
| XSS stocké dans les blocs de contenu texte libre (builder de module) | `NettoyeurBlocsModule` filtre désormais le HTML par allowlist de balises/attributs avant stockage |
| Open redirect sur la validation de leçon | `Frontend\LectureController` valide que la cible de redirection reste un chemin interne |
| Zip slip / manifest hors-répertoire à l'import SCORM | `ScormImporter` rejette les entrées ZIP avec segments `..` et vérifie que le manifest reste dans le dossier extrait |
| IDOR sur `FormateurModuleController::updateQuizCount()` | Vérification que le module appartient bien au formateur avant modification (renforcée depuis via `AccesModule::assertOwner()`) |
| Upload SVG accepté comme photo de profil (XSS stocké potentiel) | `UserController::UserProfilStore()` et `FormateurProfileController::FormateurProfilStore()` restreignent désormais l'upload à `mimes:jpg,jpeg,png` |
| Upload SVG accepté comme image de catégorie/sous-catégorie | `CategoryController` retire `image/svg+xml` des types acceptés |

Suite de tests complète verte : **268 tests** au 14 juillet 2026.

---

## Remise à zéro de la progression stagiaire

La route `POST /admin/stagiaires/{user}/reset-progression` (`admin.stagiaires.reset`) refuse toute cible dont le rôle n'est pas `stagiaire`. L'effacement est exécuté dans une transaction : réponses et tentatives de quiz, progressions, suivi vidéo, notifications de fin de module, temps total de connexion, ainsi que les résultats, scores et interactions SCORM classiques et d'évaluation sont supprimés. Les tables modernes `content_block_scorm_scores` et `content_block_scorm_results` sont également couvertes.

Le contrôleur vérifie l'existence des tables optionnelles avant de les utiliser. Si une exception survient, la transaction est annulée et l'administrateur reçoit un message d'échec ; aucune réussite partielle ne doit être présentée comme une remise à zéro complète.

### Points produit encore ouverts (identifiés le 7 juillet 2026, non traités depuis)

| # | Risque | Localisation | Impact |
|---|--------|--------------|--------|
| 11 | `/register` (scaffold Breeze) reste public et crée des comptes `role => 'stagiaire'` sans code d'accès ni invitation | `routes/auth.php`, `Auth\RegisteredUserController::store` | Contourne le modèle d'accès par code documenté plus haut. **Décision produit à trancher** : désactiver la route ou assumer l'auto-inscription. |

---

## Suppression de compte et données liées

> **Avertissement — opération irréversible :** les comptes `User` utilisent `SoftDeletes`, mais leurs événements de suppression exécutent aussi des purges physiques. Restaurer uniquement la ligne `users` ne restaure donc pas les groupes, progressions, scores ou réponses déjà effacés.

La suppression d'un compte stagiaire déclenche immédiatement `cleanupRelatedStagiaireData()` dans le modèle `User`. Cette méthode supprime notamment :
- Les progressions
- Les scores SCORM
- Les tentatives quiz
- Les réponses aux outils

La suppression d'un compte formateur via `cleanupOwnedGroupsAndLinkedStagiaires()` supprime **toujours physiquement** (`forceDelete()`, explicite depuis le 14 juillet 2026) tous les groupes dont il est `instructor_id`, y compris leurs données pédagogiques liées par `ON DELETE CASCADE` (séances, progressions, sessions d'outils live). Ce comportement reste volontairement une purge RGPD irréversible, distincte du point suivant. Pour chaque stagiaire lié directement au formateur ou à l'un de ces groupes, le code recherche un autre formateur ou un autre groupe principal : il réaffecte le stagiaire lorsque c'est possible, sinon il supprime aussi son compte et déclenche sa purge de données liées.

### `Group` utilise `SoftDeletes` depuis le 14 juillet 2026

La suppression directe d'un groupe (admin ou formateur, hors purge de compte formateur ci-dessus) est désormais **réversible** : le modèle `Group` utilise `SoftDeletes`, la ligne `groups` n'est plus jamais physiquement effacée par un `delete()` classique, ce qui empêche les contraintes `ON DELETE CASCADE` de se déclencher — séances, progressions, sessions d'outils live et pivots `group_user`/`group_module` (sauf détachement manuel explicite, voir `Formateur\GroupeController::destroy()`) survivent à la suppression.

Points techniques à connaître :
- **Unicité `name`/`emargement_code`** : les contraintes `UNIQUE` en base ont été retirées (remplacées par des index simples) car MySQL ne peut pas exprimer nativement "unique parmi les lignes non supprimées". L'unicité est appliquée côté validation Laravel via `Rule::unique(...)->withoutTrashed()`, ce qui permet de recréer un groupe avec le même nom qu'un groupe supprimé.
- **Requêtes SQL brutes** : les 5 domaines d'outils autonomes (Pendu, Mémoire, TriCartes, Carrousel, CartesRetourner — voir [07-outils-animation.md](07-outils-animation.md)) interrogent `groups`/`group_user` via `DB::table()` plutôt qu'Eloquent, par choix architectural (indépendance vis-à-vis d'Eloquent, testée explicitement). Ces requêtes ont été mises à jour pour filtrer `groups.deleted_at IS NULL` explicitement, sans quoi elles ignoreraient silencieusement le soft delete.
- **Restauration** : pas d'interface de restauration pour l'instant (pas de "corbeille" côté admin) — un groupe soft-supprimé est récupérable en base (`Group::withTrashed()->find($id)->restore()`) mais uniquement via un accès direct, pas depuis l'UI.

---

## SCORM et CSRF

Les routes `/scorm/save-progress`, `/scorm/progress`, `/scorm/save-block-progress` et `/scorm/evaluation-progress` utilisent le middleware `PreventRequestForgery` de Laravel 13. Depuis le 22 septembre 2026, aucune de ces routes n'en est exemptée. Les wrappers de même origine lisent le jeton CSRF dans la page parente et l'envoient avec `X-CSRF-TOKEN`, y compris lors de la fermeture d'une évaluation.

En complément, le middleware `auth` exige une session authentifiée et les contrôleurs vérifient l'accès à la leçon/évaluation (`User::aAccesAuModule()` — stagiaire d'un groupe actif auquel le module est affecté), avec 403 sinon. Les scores et compteurs existants, dont `last_session_time`, ne sont pas modifiés par ce durcissement.

---

## Considérations RGPD

Oneduc collecte des données d'apprentissage qui sont des **données personnelles** au sens du RGPD :

| Donnée collectée | Table | Finalité |
|-----------------|-------|----------|
| Temps de connexion | `video_segment_trackings`, `scorm_scores.session_time` | Suivi pédagogique |
| Scores et résultats | `quiz_attempts`, `scorm_scores` | Évaluation, suivi |
| Réponses aux questions | `quiz_attempt_questions` | Analyse pédagogique |
| Activité horodatée | `progressions`, `quiz_attempts` | Reporting |
| Adresse email | `users` | Authentification, communication |

### Politique publique : déjà tranchée, ce wiki était en retard

Contrairement à ce que cette page affirmait jusqu'au 14 juillet 2026, la durée de conservation, les finalités et la base légale **sont déjà décidées et publiées** sur `/confidentialite` (`resources/views/frontend/contenu/confidentialite.blade.php`, datée du 16 mai 2026) — ce wiki technique n'avait simplement pas été recroisé avec la page publique. Ne pas re-décider ces points sans relire d'abord cette page.

Résumé de la politique publiée :

1. **Finalités** : gestion du compte/accès, suivi de la progression pédagogique, sécurité et journaux techniques.
2. **Base légale** : exécution du contrat (art. 6.1.b) pour compte et suivi pédagogique ; intérêt légitime (art. 6.1.f) pour la sécurité/logs.
3. **Durée de conservation** : compte = durée de l'inscription + 3 ans après clôture ; données pédagogiques = durée de la formation + 5 ans ; journaux techniques = 12 mois.
4. **Droit d'accès / rectification / effacement / portabilité / opposition** : listés sur la page, exercice via `contact@oneduc.fr`, réponse sous 1 mois, réclamation possible auprès de la CNIL.
5. **Sous-traitants déclarés** : IONOS SARL (hébergement, UE, aucun transfert hors UE) ; Mistral AI ajouté le 14 juillet 2026 (génération de contenu pédagogique assistée par IA côté formateur — prompts de contenu de cours envoyés à l'API Mistral, pas de données personnelles de stagiaire transmises).

### Écarts réels entre la politique publiée et le code (vérifiés le 14 juillet 2026)

Ces deux points restent de vraies lacunes techniques, pas des décisions en attente :

1. **Rétention non appliquée automatiquement** : les durées ci-dessus (3 ans, 5 ans, 12 mois) ne sont enforced par aucune tâche planifiée (`routes/console.php` ne contient qu'une commande de nettoyage de questions de quiz orphelines, rien sur la rétention/anonymisation). Aujourd'hui, l'effacement ne se produit que sur suppression manuelle d'un compte (`cleanupRelatedStagiaireData()`, `cleanupOwnedGroupsAndLinkedStagiaires()`). Tant qu'aucune tâche planifiée n'existe, un compte inactif au-delà de la durée annoncée n'est pas purgé automatiquement. **Ne pas implémenter dans la précipitation** : une purge automatisée est une opération destructive et irréversible sur des données réelles, elle mérite sa propre revue dédiée (quels comptes exactement, quel préavis, quel test) plutôt qu'un ajout rapide.
2. **Portabilité non implémentée** : la page publique promet un export dans un format structuré ; aucun code ne l'implémente (`grep` sur les contrôleurs ne remonte aucun endpoint d'export). En attendant, la demande peut être honorée manuellement (export SQL/CSV ponctuel par un admin) dans le délai d'un mois annoncé — mais cette procédure manuelle n'était documentée nulle part avant aujourd'hui (voir ci-dessous).

### Procédure interne en cas de demande stagiaire/formateur (droit d'accès, effacement, portabilité)

Non documentée avant le 14 juillet 2026, ajoutée ici pour que quiconque reçoit un message sur `contact@oneduc.fr` sache quoi faire en attendant l'outillage en libre-service :

1. **Droit d'accès** : un admin exporte manuellement les lignes concernant l'utilisateur (`users`, `progressions`, `quiz_attempts`, `scorm_scores`, etc.) et les transmet dans un format lisible (CSV/PDF).
2. **Droit à l'effacement** : un admin supprime le compte depuis l'interface admin — déclenche `cleanupRelatedStagiaireData()` (stagiaire) ou `cleanupOwnedGroupsAndLinkedStagiaires()` (formateur). Rappel : purge physique immédiate et non réversible sur les données liées (voir plus haut, section "Suppression de compte et données liées").
3. **Droit à la portabilité** : même export manuel que le droit d'accès, dans un format structuré (CSV a minima).
4. Toute demande doit être traitée sous 1 mois (délai annoncé publiquement), et tracée (date de la demande, action réalisée) — pas encore de registre dédié : à créer si le volume de demandes le justifie.

### Ce qui est déjà en place

- Politique de confidentialité publique complète et à jour dans ses grandes lignes (`/confidentialite`), avec un point de vigilance : la mention des sous-traitants a été corrigée le 14 juillet 2026 pour inclure Mistral AI
- Soft delete de la ligne utilisateur, avec avertissement nécessaire car les données liées peuvent être purgées immédiatement par les événements du modèle
- Nettoyage automatique des données liées à la suppression de compte, destructif et non intégralement réversible
- Aucun tiers tracker côté frontend identifié
- Cookie consent via `spatie/laravel-cookie-consent`

---

## Bonnes pratiques développeur

```bash
# Vérifier les routes exposées publiquement
php artisan route:list --path=admin | grep -v "auth"

# Vérifier les FormRequests avec authorize() retournant false
grep -r "return false" app/Http/Requests/

# Vérifier les imports morts
grep -r "use App\\Http\\Controllers\\Scorm" routes/
```

---

[Retour au wiki](README.md)
