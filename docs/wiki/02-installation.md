# 02 — Installation & Configuration

*Public : développeurs et administrateurs système.*

## Prérequis

| Outil | Version minimale |
|-------|-----------------|
| PHP | 8.3+ (Laravel 13) |
| Composer | 2.x |
| Node.js | 18+ |
| NPM | 9+ |
| MySQL / MariaDB | 8.0+ / 10.4+ recommandé |
| LibreOffice Impress | Conversion des fichiers PowerPoint en PDF (outil « PowerPoint vers module ») |
| Poppler (`pdftocairo`) | Conversion des pages PDF en diapositives (outil « PowerPoint vers module ») |

> Le fichier `.env.example` Laravel indique encore `DB_CONNECTION=sqlite`, mais le dépôt contient une baseline de schéma **MySQL** (`database/schema/mysql-schema.sql`). Pour une installation fiable d'Oneduc, utiliser MySQL/MariaDB ou générer explicitement une baseline SQLite équivalente.

Sous Debian/Ubuntu, les dépendances de l'outil « PowerPoint vers module » peuvent être installées avec :

```bash
sudo apt install libreoffice-impress poppler-utils
```

---

## Installation (environnement de développement)

### 1. Cloner le dépôt

```bash
git clone https://github.com/LaurentSta/Oneduc_Dev.git
cd Oneduc_Dev
```

### 2. Installer les dépendances PHP et JavaScript

```bash
composer install
npm install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` — voir la section [Variables d'environnement](#variables-denvironnement) ci-dessous.

### 4. Créer la base de données et migrer

```bash
# Créer la base MySQL/MariaDB au préalable
# Dans .env : DB_CONNECTION=mysql, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan migrate
php artisan db:seed   # optionnel — données de démonstration
```

Laravel applique d'abord le schéma baseline situé dans `database/schema/mysql-schema.sql`, puis les migrations post-baseline présentes dans `database/migrations/`.

### 5. Lancer l'environnement de développement

```bash
# Recommandé — lance tout en parallèle (serveur, queue, logs, Vite)
composer dev

# Ou individuellement :
php artisan serve      # Backend Laravel
npm run dev            # Frontend Vite (hot reload)
php artisan queue:listen  # Queue de travail
```

---

## Configuration du HMR Vite (développement)

Le fichier `vite.config.js` écoute sur `0.0.0.0`, impose le port Vite (`strictPort: true`) et contient une adresse IP en dur pour le Hot Module Replacement :

```js
hmr: {
    host: '192.168.189.129' // <- IP Debian actuelle
}
```

Mettre à jour cette valeur avec l'IP locale de la machine de développement pour que le rechargement automatique fonctionne correctement.

---

## Variables d'environnement

### Variables standard Laravel

| Variable | Description | Valeur dev typique |
|----------|-------------|-------------------|
| `APP_NAME` | Nom de l'application | `Oneduc` |
| `APP_ENV` | Environnement | `local` / `production` |
| `APP_URL` | URL de base | `http://localhost:8000` |
| `APP_DEBUG` | Mode debug | `true` (dev), `false` (prod) |
| `APP_LOCALE` | Langue principale | `fr` |
| `APP_FALLBACK_LOCALE` | Langue fallback | `fr` |
| `APP_TIMEZONE` | Fuseau horaire | `Europe/Paris` en production France |
| `DB_CONNECTION` | Driver BDD | `mysql` recommandé |
| `DB_HOST` | Hôte BDD | `127.0.0.1` |
| `DB_PORT` | Port BDD | `3306` |
| `DB_DATABASE` | Nom de la base | `oneduc` |
| `DB_USERNAME` | Utilisateur BDD | — |
| `DB_PASSWORD` | Mot de passe BDD | — |
| `MAIL_MAILER` | Driver mail | `smtp` / `log` (dev) |
| `SESSION_DRIVER` | Driver session | `database` |
| `QUEUE_CONNECTION` | Driver queue | `database` |
| `CACHE_STORE` | Driver cache | `database` |

### Variables spécifiques au projet

| Variable | Description | Requis |
|----------|-------------|--------|
| `VITE_APP_NAME` | Nom exposé au frontend via Vite | Oui |
| `HEDGEDOC_BASE_URL` | URL de l'instance HedgeDoc (pages collaboratives) | Non |
| `HEDGEDOC_NEW_PATH` | Chemin de création de nouvelle page HedgeDoc | Non (défaut : `/new`) |
| `DISCORD_SUPPORT_WEBHOOK_URL` | Webhook Discord pour les notifications support | Non |
| `DISCORD_SUPPORT_INVITE_URL` | URL d'invitation Discord affichée aux utilisateurs | Non |
| `DISCORD_SERVER_ID` | ID du serveur Discord | Non |
| `NOCAPTCHA_SITEKEY` | Clé publique reCAPTCHA si les formulaires publics l'utilisent | Selon usage |
| `NOCAPTCHA_SECRET` | Secret reCAPTCHA | Selon usage |
| `COOKIE_CONSENT_ENABLED` | Active/désactive le bandeau cookies Spatie | Non (défaut : `true`) |
| `MISTRAL_API_KEY` | Clé API Mistral pour la génération de contenu dans les constructeurs formateur et admin | Non (fonctionnalité désactivée sans clé) |
| `MISTRAL_MODEL` | Modèle Mistral utilisé | Non (défaut : `mistral-large-latest`) |
| `MISTRAL_MONTHLY_TOKEN_LIMIT` | Budget mensuel personnel d'un formateur | Non (défaut : `500000`) |
| `MISTRAL_ADMIN_MONTHLY_TOKEN_LIMIT` | Budget mensuel plateforme partagé entre les comptes admin | Non (défaut : `2000000`) |
| `MISTRAL_ADMIN_DAILY_GENERATION_LIMIT` | Quota quotidien admin, par type et partagé entre les comptes admin | Non (défaut : `20`) |
| `SLIDES_SOFFICE_BINARY` | Chemin ou commande LibreOffice utilisée pour convertir les `.ppt/.pptx` | Non (défaut : `soffice`) |
| `SLIDES_PDFTOCAIRO_BINARY` | Chemin ou commande Poppler utilisée pour générer les images | Non (défaut : `pdftocairo`) |

---

## Dépendances principales

### PHP (Composer)

| Package | Usage |
|---------|-------|
| `laravel/framework` ^11 | Framework principal |
| `anhskohbo/no-captcha` | Captcha sur formulaires publics |
| `spatie/laravel-cookie-consent` | Bandeau RGPD cookies |
| `laravel-lang/common` | Traductions Laravel |
| `smalot/pdfparser` | Extraction de texte PDF (génération de leçon par IA) |
| `phpoffice/phpword` | Extraction de texte Word `.docx` (génération de leçon par IA) |

### Dev PHP

| Package | Usage |
|---------|-------|
| `laravel/pint` | Formatage du code PHP |
| `pestphp/pest` | Framework de tests |
| `laravel/pail` | Viewer de logs en temps réel |
| `laravel/sail` | Docker pour dev local (optionnel) |

---

## Base de données de test dédiée

Les tests (`php artisan test` / Pest) tournent sur une base **MySQL/MariaDB dédiée**, distincte de la base de développement — SQLite n'est pas supporté par ce projet (la baseline de schéma est MySQL-only, voir `database/schema/mysql-schema.sql`).

1. Créer la base :
   ```sql
   CREATE DATABASE oneduc_testing;
   ```
2. Copier `.env.testing.example` vers `.env.testing` et renseigner `DB_USERNAME`/`DB_PASSWORD` (ce fichier n'est pas versionné) :
   ```bash
   cp .env.testing.example .env.testing
   php artisan key:generate --env=testing
   ```
3. Lancer les tests normalement : Laravel charge automatiquement `.env.testing` quand `APP_ENV=testing` (positionné par `phpunit.xml`).

Sans cette base dédiée créée au préalable, `php artisan test` échoue avec une erreur de connexion à la base de données.

---

## Commandes utiles

```bash
# Tests
php artisan test                                    # Tous les tests
./vendor/bin/pest tests/Feature/SomeTest.php        # Fichier spécifique
php artisan test --filter NomDuTest                 # Filtrer par nom

# Formatage
php artisan pint                                    # Formatter le PHP

# Base de données
php artisan migrate                                 # Migrations en attente
php artisan migrate:fresh --seed                    # Réinitialiser la BDD

# Débogage
php artisan route:list                              # Lister les routes
php artisan tinker                                  # Console interactive
```

---

## Stockage des assets SCORM

Les packages SCORM importés ne sont **pas versionnés dans git**. Les chemins applicatifs sont centralisés dans `config/learning_assets.php` :

| Type | Chemin principal |
|------|------------------|
| Leçons SCORM | `modules/00_Lecons` |
| Vidéos | `modules/videos` |
| Évaluations SCORM | `modules/evaluations/scorm` |

Des chemins legacy restent déclarés pour relire les anciens imports (`modules/scorm/00_Lecons`, `modules/scorm/01_evaluations`, `modules/scorm/02_videos`).

En production, ces dossiers doivent être sauvegardés séparément du code source.

---

## Notes pour la production

Suivre la [procédure de préparation du déploiement](19-securite-deploiement.md), puis lancer `php artisan oneduc:verifier-production` sur la release préparée. La CI contrôle désormais les tests MySQL, PHPStan, le format des fichiers modifiés, les audits et le build frontend.

- Désactiver `APP_DEBUG=false` obligatoirement
- Configurer un vrai driver mail (SMTP)
- Configurer `QUEUE_CONNECTION=database` et lancer un worker queue en continu (`php artisan queue:work`)
- Installer LibreOffice Impress et Poppler pour activer la création de modules depuis PowerPoint/PDF
- Configurer le scheduler Laravel dans cron (`php artisan schedule:run`)
- Vérifier que `APP_NAME`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_TIMEZONE` et les valeurs mail ne gardent pas les valeurs génériques de `.env.example`
- Vérifier les routes exposées publiquement (voir [Sécurité](10-securite-rgpd.md))

---

[Retour au wiki](README.md)
