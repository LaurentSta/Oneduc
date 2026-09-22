# 13 — Checklist : prêt à publier sur GitHub

*Public : développeurs et mainteneurs du projet.*

Cette checklist doit être complétée avant de rendre le dépôt public sur GitHub. Elle couvre quatre axes : sécurité du code, nettoyage de l'historique git, conformité légale, et configuration GitHub.

---

## Axe 1 — Sécurité du code (bloquant)

Ces points doivent être corrigés AVANT de publier. Publier avec ces failles expose vos utilisateurs et donne une carte d'attaque publique.

- [x] **S1** — Route `/admin/stagiaires/{id}/debug-progression` supprimée ou absente
  Vérifié le 5 juillet 2026 : aucune route `debug-progression` dans `php artisan route:list --json`

- [x] **S2** — `POST /admin/stagiaires/{user}/reset-progression` protégé côté admin
  Vérifié : route `admin.stagiaires.reset` dans `routes/admin.php`, sous `auth`, `role:admin`, `admin.activity`

- [x] **S3** — Corriger `Module::isVisibleTo()` pour vérifier l'appartenance groupe du stagiaire  
  Corrigé le 5 juillet 2026 : `app/Models/Module.php` — stagiaire vérifié via `User::aAccesAuModule()` (groupe actif + module affecté), formateur via `formateur_id`/`is_trainer_authored` ou `Group::scopeAccessibleByTrainer()`. **Gap restant identifié** : `StagiaireController::StagiaireModuleDetail()` (route `stagiaire.module.detail`) et `Frontend\LectureController` (`show`, `showScorm`, `showScormBlock`, `showSlides`) n'appellent pas `isVisibleTo()` et n'ont aucune vérification d'appartenance groupe — à traiter en Phase 2.

- [x] **S4** — Ajouter throttling sur `/stagiaire/connexion-code`  
  Corrigé le 5 juillet 2026 : rate limiter nommé `connexion-code` (10/minute par IP) via `RateLimiter::for()` dans `AppServiceProvider`, appliqué à la route dans `routes/web.php`.

- [x] **S5** — Corriger `LessonFeedbackController::store()` (route inexistante → erreur 500)  
  Corrigé le 5 juillet 2026 : `redirect()->back()` remplace la redirection vers `module.lesson` (route inexistante).

- [x] **S6** — Corriger le cumul temps SCORM legacy
  Corrigé le 5 juillet 2026 : migration ajoutant `last_session_time` à `scorm_scores` (même type que `content_block_scorm_scores`).

- [x] **S7** — Ajouter la vérification d'appartenance à la leçon dans `POST /scorm/save-progress`
  Corrigé le 5 juillet 2026 : `User::aAccesAuModule()` appliqué dans `SCORMController`, `ContentBlockScormController` et `EvaluationSCORMController` (403 sinon), middleware `auth` ajouté sur les 3 routes. Complété le 22 septembre 2026 : protection CSRF rétablie après transmission du jeton par les wrappers de même origine, voir [Préparation du déploiement](19-securite-deploiement.md).

- [x] **S8** — Corriger la page publique `/inscription`
  Corrigé le 5 juillet 2026 : redirection 301 vers `/inscription-formateur` (seul parcours d'inscription fonctionnel).

- [x] **S9** — Remettre `php artisan test` au vert
  Corrigé le 5 juillet 2026 : le test attendait `path`, contrat obsolète antérieur à la migration Media Library. Test mis à jour pour valider `media_id`/`url` (contrat réellement utilisé par le contrôleur et le frontend). Suite complète verte (124 tests).

---

## Axe 2 — Nettoyage de l'historique git (bloquant)

### Secrets et fichiers sensibles

Vérifier que ces fichiers ne sont pas dans l'historique git (même supprimés d'un commit récent, ils restent accessibles via `git log`) :

```bash
# Vérifier si .env a jamais été commité
git log --all --full-history -- .env
git log --all --full-history -- .env.local
git log --all --full-history -- .env.production

# Vérifier les clés et credentials
git log --all --full-history -- "*.pem" "*.key" "*credentials*"

# Chercher des secrets dans l'historique complet
git grep -i "password\|secret\|api_key\|token" $(git rev-list --all) 2>/dev/null | grep -v "\.example\|test\|dummy\|placeholder" | head -20
```

Si un secret est trouvé dans l'historique :
1. Révoquez immédiatement le secret concerné (mot de passe, clé API, etc.)
2. Utilisez `git filter-repo` (ou BFG Repo Cleaner) pour le supprimer de l'historique
3. Faites un force-push après nettoyage (attention : coordonner avec les autres contributeurs)

### Données personnelles dans les seeders/fixtures

```bash
# Chercher des emails réels dans les seeders
grep -r "@" database/seeders/ database/factories/
grep -r "@" tests/

# Vérifier qu'il n'y a pas de dumps de base de données
find . -name "*.sql" -not -path "./.git/*"
find . -name "*.sqlite" -not -path "./.git/*"
```

Tout email ou nom réel doit être remplacé par des données fictives (`test@example.com`, `jean.dupont@exemple.fr`).

État local partiel au 5 juillet 2026 : `git log --all --full-history -- .env` ne retourne rien. Le scan complet des secrets reste à faire avant passage public.

### Vérification du .gitignore

```bash
# Ces éléments doivent être ignorés
cat .gitignore | grep -E "\.env$|storage/|public/build/|public/modules/|vendor/|node_modules/"
```

Vérifier que `.gitignore` contient au minimum :
```
.env
.env.*
!.env.example
/vendor/
/node_modules/
/public/build/
/storage/*.key
/storage/app/public/
/public/modules/
/public/upload/
```

### Packages SCORM

```bash
# Vérifier qu'aucun package SCORM n'est dans le dépôt
find public/modules -name "*.zip" 2>/dev/null
find storage -name "imsmanifest.xml" 2>/dev/null | head -5
```

Les packages SCORM importés ne doivent pas être dans le dépôt git (ils peuvent contenir des contenus sous droits).

---

## Axe 3 — Conformité légale

- [ ] Le fichier `LICENSE` contient le texte intégral de l'AGPL v3  
  → État local : le texte AGPL v3 complet est présent

- [ ] Le fichier `NOTICE` mentionne `© Association Oneduc` avec les années correctes

- [ ] Le fichier `CLA.md` est présent et référencé dans `CONTRIBUTING.md`

- [ ] Vérification avec votre école/organisme de certification que votre projet de fin d'année peut être publié sous AGPL au nom de l'association (vérifier la convention de formation ou le règlement intérieur)

- [ ] Les statuts de l'association Oneduc couvrent bien le développement de logiciels numériques (sinon : délibération du CA actant la titularité des droits)

- [ ] Les emails de contact dans `SECURITY.md` et `CODE_OF_CONDUCT.md` sont opérationnels (`contact@oneduc.fr`)

---

## Axe 4 — Configuration GitHub

### Paramètres du dépôt

- [ ] Le dépôt est mis en **Public** (ou restera privé temporairement le temps des corrections)
- [ ] Description du dépôt renseignée : "LMS pour l'inclusion numérique — Laravel 11, SCORM, quiz natifs"  
  Ne pas mentionner les outils d'animation live : implémentés mais pas encore activés en environnement de production (voir [07 — Outils d'animation](07-outils-animation.md))
- [ ] Topics GitHub ajoutés : `lms`, `laravel`, `scorm`, `inclusion-numerique`, `formation`, `php`, `tailwind`
- [ ] Site web renseigné : `https://oneduc.fr`

### Fonctionnalités GitHub à activer

- [ ] **Issues** activées (pour les rapports de bugs et demandes de fonctionnalités)
- [ ] **Discussions** activées (pour les questions et la communauté)
- [ ] **Wiki** GitHub activé et alimenté avec le contenu de `docs/wiki/`
- [ ] **Security advisories** activées (pour les signalements de failles privés)

### Fichiers GitHub spéciaux

- [ ] `.github/ISSUE_TEMPLATE/bug_report.md` — template de rapport de bug
- [ ] `.github/ISSUE_TEMPLATE/feature_request.md` — template de demande de fonctionnalité
- [ ] `.github/PULL_REQUEST_TEMPLATE.md` — template de Pull Request (inclure rappel du CLA)

État local : ces trois fichiers existent dans `.github/`.

### Branches et protection

- [ ] La branche `main` est protégée (au moins : interdire les push directs, exiger une PR)
- [ ] Au moins un reviewer requis pour les PRs (vous-même suffit pour commencer)

---

## Axe 5 — README principal

Le `README.md` à la racine est la première chose que les visiteurs GitHub voient. À vérifier :

- [ ] Badge de licence AGPL v3 présent  
  ```markdown
  [![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
  ```
- [ ] Description courte et claire du projet
- [ ] Lien vers le wiki (`docs/wiki/README.md`)
- [ ] Lien vers `CONTRIBUTING.md`
- [ ] Section "Licence & gouvernance" mentionnant Association Oneduc + AGPL v3
- [ ] Contact (`contact@oneduc.fr`)

---

## Récapitulatif de priorité

| Priorité | Action | Bloquant ? |
|----------|--------|------------|
| 1 | ~~Corriger les points sécurité et santé applicative restants (S3 à S9)~~ — fait le 5 juillet 2026, voir Axe 1 | Oui |
| 1bis | Traiter le gap identifié lors du correctif S3 : `StagiaireModuleDetail` et `Frontend\LectureController` ne vérifient pas l'appartenance groupe | Oui |
| 2 | Vérifier et nettoyer l'historique git (secrets, données personnelles) | Oui |
| 3 | Synchroniser `SECURITY.md` avec l'état réel de la checklist | Recommandé |
| 4 | Vérifier convention formation / statuts association | Recommandé |
| 5 | Configurer GitHub (topics, protections de branche, templates) | Non |
| 6 | Mettre à jour le README principal avec badge et sections | Non |

---

[Retour au wiki](README.md)
