# Oneduc - LMS & Inclusion Numerique

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

Oneduc est une plateforme de gestion de l'apprentissage concue pour l'inclusion numerique et la formation accompagnee. Elle combine un socle Laravel, des espaces par role, des contenus SCORM et des quiz natifs pour suivre finement les parcours des apprenants.

## Points Cles

- **Architecture metier** : espaces Admin, Formateur, Stagiaire et Observateur.
- **E-learning** : integration SCORM 1.2 / 2004, quiz, progression et resultats.
- **Accompagnement** : groupes, parcours et tableaux de bord pour les formateurs.
- **Outils d'animation live** (nuage de mots, sondage, tableau blanc...) : developpes, pas encore actives en environnement de production.
- **Accessibilite** : interface Tailwind CSS orientee lisibilite et usages terrain.
- **Gouvernance ouverte** : projet associatif distribue sous AGPL v3.

## Stack Technique

| Composant | Technologie |
| --- | --- |
| Backend | Laravel, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS, Vite |
| Base de donnees | MySQL / MariaDB |
| Tests | Pest / PHPUnit |
| Standard pedagogique | SCORM |

## Documentation

La documentation projet est dans [docs/wiki/README.md](docs/wiki/README.md).

Ressources utiles :

- [Installation et configuration](docs/wiki/02-installation.md)
- [Architecture technique](docs/wiki/03-architecture.md)
- [Securite et RGPD](docs/wiki/10-securite-rgpd.md)
- [Audit securite et mise en production - septembre 2026](docs/audit-securite-production-2026-09-22.md)
- [Roadmap](docs/wiki/11-roadmap.md)
- [Checklist de publication GitHub](docs/wiki/13-publication-github.md)

## Installation Locale

Prerequis :

- PHP 8.2+
- Composer
- Node.js et npm
- MySQL ou MariaDB

```bash
git clone https://github.com/LaurentSta/Oneduc_Dev.git
cd Oneduc_Dev

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

npm run dev
php artisan serve
```

## Structure Du Depot

Le depot doit rester leger et portable.

Versionne :

- code applicatif Laravel ;
- migrations, factories et tests ;
- ressources frontend ;
- documentation projet ;
- fichiers de gouvernance et de licence.

Ignore :

- `.env` et secrets locaux ;
- `vendor/` et `node_modules/` ;
- fichiers generes par Vite ;
- stockage applicatif ;
- modules SCORM importes ;
- dumps SQL, exports et donnees personnelles.

## Contribution

Les contributions sont bienvenues si elles respectent la mission du projet : accessibilite, inclusion numerique, qualite pedagogique et securite.

Avant de proposer une Pull Request, lire :

- [CONTRIBUTING.md](CONTRIBUTING.md)
- [CLA.md](CLA.md)
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- [SECURITY.md](SECURITY.md)

Commandes de verification recommandees :

```bash
php artisan test
php artisan pint
npm run build
```

## Securite

Ne publiez pas de faille exploitable dans une issue publique. Suivez la procedure de [SECURITY.md](SECURITY.md).

Contact securite : contact@oneduc.fr

## Licence Et Gouvernance

Oneduc est distribue sous licence GNU Affero General Public License v3.0 ou toute version ulterieure. Voir [LICENSE](LICENSE) et [NOTICE](NOTICE).

Le projet est porte par l'Association Oneduc. Les contributions externes sont soumises au Contributor License Agreement du projet.
