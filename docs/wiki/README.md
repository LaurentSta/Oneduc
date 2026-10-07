# Wiki Oneduc — Documentation du projet

Bienvenue sur la documentation d'Oneduc, un LMS dédié à l'inclusion numérique et à la formation accompagnée.

---

## Par où commencer selon votre profil

Tout le wiki n'est pas à lire par tout le monde. Voici les parcours conseillés :

**Formateur** — vous voulez savoir ce que la plateforme permet de faire :
[Présentation](01-presentation.md) → [Profils utilisateurs](04-profils-utilisateurs.md) → [Groupes & Parcours](06-groupes-parcours.md) → [Outils d'animation](07-outils-animation.md) → [Émargement](16-emargement.md) → [Génération de contenu par IA](15-generation-ia.md) → [Tableaux de bord](08-tableaux-de-bord.md). Le [Glossaire](12-glossaire.md) sert de référence pour le vocabulaire.

**Développeur** — vous voulez contribuer au code :
[Installation](02-installation.md) → [Architecture](03-architecture.md) → [Roadmap](11-roadmap.md), puis les pages du domaine sur lequel vous travaillez. Lisez la [Checklist de publication](13-publication-github.md) avant tout travail touchant à la sécurité.

**Partenaire ou financeur** — vous voulez situer le projet :
[Présentation](01-presentation.md) et [Roadmap](11-roadmap.md) suffisent.

**Administrateur système** :
[Installation](02-installation.md) → [Sécurité & RGPD](10-securite-rgpd.md).

Chaque page indique en tête son public visé. Les pages mixtes regroupent la partie technique en fin de page.

---

## Table des matières

| # | Page | Résumé |
|---|------|--------|
| 01 | [Présentation du projet](01-presentation.md) | Vision, usages possibles, bilan fonctionnel, trajectoire produit |
| 02 | [Installation & configuration](02-installation.md) | Prérequis, mise en route dev, variables d'environnement |
| 03 | [Architecture technique](03-architecture.md) | Structure Laravel, routing multi-rôle, services, schéma SQL baseline |
| 04 | [Profils utilisateurs](04-profils-utilisateurs.md) | Admin, Formateur, Stagiaire, Observateur — droits et espaces |
| 05 | [Modules, SCORM & Quiz](05-modules-scorm-quiz.md) | Contenu pédagogique, builders formateur/admin, versions, SCORM, quiz et progression |
| 06 | [Groupes & Parcours](06-groupes-parcours.md) | Groupes de formation, co-formateurs, parcours ordonnés |
| — | [Modèles globaux de parcours](modeles-parcours.md) | Catalogue admin de modèles, duplication formateur et limite runtime actuelle |
| 07 | [Outils d'animation pédagogique](07-outils-animation.md) | Activités live intégrées, tableau blanc, minuteur, pages collaboratives — sommaire avec une [fiche dédiée par outil](outils/) |
| 08 | [Tableaux de bord](08-tableaux-de-bord.md) | Analytics Admin, Formateur, Stagiaire — sources de données |
| 09 | [Design system](09-design-system.md) | Tokens Tailwind, composants, typographie, accessibilité |
| 10 | [Sécurité & RGPD](10-securite-rgpd.md) | Middleware, authentification, données personnelles |
| 11 | [Roadmap](11-roadmap.md) | Feuille de route en 4 phases, bugs connus, dette technique |
| 12 | [Glossaire](12-glossaire.md) | Vocabulaire unifié du projet |
| 13 | [Prêt à publier sur GitHub](13-publication-github.md) | Checklist avant publication — sécurité, licence, nettoyage |
| 15 | [Génération de contenu par IA](15-generation-ia.md) | Génération de leçons/formations par IA (Mistral), garde-fous, configuration |
| 16 | [Émargement](16-emargement.md) | Feuille de présence par séance datée, signature graphique, export PDF Qualiopi/OPCO |
| 17 | [Audit site du 14 juillet 2026](17-audit-site-2026-07-14.md) | Suivi de l'audit du 5 juillet, faille d'accès module corrigée, crawl public 100% sain |
| 18 | [Accessibilité et démarche RGAA](18-accessibilite-rgaa.md) | Déclaration publique, schéma 2026-2028, plan 2026 et règles de suivi |
| 19 | [Sécurité et préparation du déploiement](19-securite-deploiement.md) | Correctifs CSRF, dépendances, CI, contrôles de production, sauvegarde et retour arrière |

---

## Pages archivées

Pages conservées pour l'historique, remplacées par une page plus récente.

| # | Page | Remplacée par |
|---|------|----------------|
| 14 | [Audit site du 5 juillet 2026](14-audit-site-2026-07-05.md) | [Audit site du 14 juillet 2026](17-audit-site-2026-07-14.md) |

---

## Licence & gouvernance

Oneduc est distribué sous **licence GNU Affero General Public License v3 (AGPL v3)**.

Concrètement :
- tout le monde peut utiliser, modifier et redistribuer Oneduc librement ;
- toute modification redistribuée, y compris en mode SaaS hébergé, doit être publiée sous la même licence ;
- l'usage commercial est possible sous conditions de réciprocité — contactez l'association pour une licence commerciale.

**Titulaire des droits :** Association Oneduc (loi 1901)
**Contributions :** soumises au [Contributor License Agreement](../../CLA.md)
**Gouvernance :** pilotée par l'Association Oneduc via ses instances statutaires

Fichiers légaux à la racine du projet :

| Fichier | Rôle |
|---------|------|
| [LICENSE](../../LICENSE) | Texte de la licence AGPL v3 |
| [NOTICE](../../NOTICE) | Copyright et crédits des dépendances |
| [CONTRIBUTING.md](../../CONTRIBUTING.md) | Guide de contribution |
| [CLA.md](../../CLA.md) | Contributor License Agreement |
| [SECURITY.md](../../SECURITY.md) | Politique de signalement des failles |
| [CODE_OF_CONDUCT.md](../../CODE_OF_CONDUCT.md) | Code de conduite de la communauté |

---

## Autres ressources du dossier `docs/`

- [Parcours formateur Oneduc](../parcours-formateur.md) — structure de l'onboarding formateur (5 modules)
- [Idées d'outils formateurs](../idees-outils-formateurs.md) — pistes produit (OF-001 à OF-013)

---

## État vérifié du dépôt

Analyse réalisée le **14 juillet 2026** depuis `/var/www/Oneduc_Dev` (voir [Audit du 14 juillet 2026](17-audit-site-2026-07-14.md) pour le détail).

| Vérification | Résultat |
|--------------|----------|
| Routes Laravel | 513 routes déclarées (`php artisan route:list --json`) |
| Crawl public | 23 routes GET publiques sans paramètres testées : 22 en HTTP 200, `/inscription` en redirection 301 volontaire — 0 erreur 500 |
| Tests automatisés | `php artisan test` : 268 tests passés, 0 échec, 1295 assertions |
| Build frontend | `npm run build` réussi, avec avertissements Vite sur les chunks volumineux |
| Schéma base de données | Baseline MySQL dans `database/schema/mysql-schema.sql` + migrations post-baseline, toutes marquées `Ran` localement |
| Point d'attention publication | Historique Git à vérifier avant publication ; dette technique restante documentée dans [11-roadmap.md](11-roadmap.md) |

---

*Dernière mise à jour : 14 juillet 2026 — Version locale vérifiée : Laravel 11.42.0, PHP 8.3.6, Tailwind CSS v4, React 19 pour certains écrans riches*
