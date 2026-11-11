# Plateforme multi-consulats — DÉMO NON OFFICIELLE

> ⚠ **Démo / Maquette non officielle.** Données fictives (adresses, téléphones, frais, délais, personnes).
> Aucun logo ni armoirie officiels. Ce projet n'est ni édité ni validé par un État ou un consulat.

Une seule base de code et une seule base MySQL servent plusieurs consulats africains.
Démos incluses : **Consulat du Tchad au Sénégal** et **Consulat du Cameroun au Sénégal**.

## Structure du dépôt

```
CONSULAT/
├── backend/    Laravel 12 — API JSON v1 (/api/v1) + administration Filament (/admin)
└── frontend/   Next.js 16 (React 19, App Router) — site public, FR / EN / AR (RTL)
```

| Côté | Stack | Rôle |
|---|---|---|
| `backend/` | Laravel 12, PHP 8.2+, MySQL 8, Filament 3, spatie/laravel-permission, spatie/laravel-translatable, PHPUnit | Données, règles métier (capacité, double réservation, fermetures, RGPD), emails, administration |
| `frontend/` | Next.js 16, React 19, TypeScript, Tailwind CSS 4 | Pages publiques, prise de rendez-vous, FAQ, contact, SEO (sitemap, robots, balises) |

Documents de référence : [backend/PLAN.md](backend/PLAN.md), [backend/DECISIONS.md](backend/DECISIONS.md),
[backend/docs/ROADMAP_V2.md](backend/docs/ROADMAP_V2.md), [backend/docs/DEPLOIEMENT.md](backend/docs/DEPLOIEMENT.md).

## Prérequis

- PHP 8.2+ avec `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `fileinfo`, `exif`, `openssl`, `curl`
- Composer 2, Node.js 20+ et npm
- MySQL 8.0

Sous Windows, `intl` et `exif` sont souvent désactivés : décommentez `extension=intl` et `extension=exif` dans `php.ini`,
ou utilisez le dossier `backend/.phpini/` (voir plus bas).

## 1. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Créez la base une seule fois :

```sql
CREATE DATABASE consulat_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Renseignez `backend/.env` (`DB_*`, et `FRONTEND_URL=http://localhost:3000`), puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve              # API sur http://127.0.0.1:8000
```

Le seeder crée les rôles, les deux consulats (services, actualités, FAQ, pages légales, horaires, créneaux,
quelques rendez-vous et messages), le backlog du projet et les comptes de démo.

Tâches planifiées (à lancer en production via le cron de Laravel, voir `docs/DEPLOIEMENT.md`) :

```bash
php artisan schedule:work               # en local : exécute rappels J-1 et purge RGPD
php artisan consulat:send-reminders     # rappels de la veille (une fois)
php artisan consulat:purge-personal-data
```

### Sous Windows : activer `intl` sans modifier `php.ini`

```powershell
cd backend
$env:PHP_INI_SCAN_DIR = "$PWD\.phpini"
php -m | Select-String intl
```

## 2. Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.example .env.local     # NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
npm run dev                    # http://localhost:3000
```

Pour la version de production : `npm run build` puis `npm run start`.

## URLs à ouvrir

| Page | URL |
|---|---|
| Choix du consulat | http://localhost:3000 |
| Tchad (FR) | http://localhost:3000/tchad-senegal/fr |
| Tchad (EN / AR, RTL) | http://localhost:3000/tchad-senegal/en · `/ar` |
| Cameroun (FR) | http://localhost:3000/cameroun-senegal/fr |
| Prise de rendez-vous | http://localhost:3000/tchad-senegal/fr/appointment |
| Administration | http://127.0.0.1:8000/admin |
| API (exemple) | http://127.0.0.1:8000/api/v1/tchad-senegal?lang=fr |

## Comptes de démo

Mot de passe commun : **`Demo#Consulat2026`** (ou la valeur de `DEMO_PASSWORD` dans `backend/.env` avant le seed).

| Rôle | Email | Accès |
|---|---|---|
| super_admin | `superadmin@demo.test` | tous les consulats |
| consulate_admin (Tchad) | `admin.tchad@demo.test` | contenus, rendez-vous, messages, fermetures, utilisateurs |
| staff (Tchad) | `staff.tchad@demo.test` | rendez-vous et messages, lecture des contenus |
| consulate_admin / staff (Cameroun) | `admin.cameroun@demo.test` / `staff.cameroun@demo.test` | idem pour le Cameroun |

## Fonctionnalités

**Public (frontend)** : accueil avec hero, mot du consul, mission, horaires, carte OpenStreetMap ; services avec
checklist des documents (mémorisée localement) ; actualités paginées ; FAQ avec recherche ; recherche dans tout le
site ; contact avec consentement et anti-spam ; prise de rendez-vous en 4 étapes ; confirmation avec référence,
annulation et export `.ics` ; pages légales ; FR / EN / AR avec mise en page RTL ; thème par consulat.

**Backend** :
- API v1 scopée par consulat, réponses JSON, langue via `?lang=` ou `Accept-Language`.
- Rendez-vous : capacité par créneau, verrou anti double réservation, consentement RGPD obligatoire.
- Confidentialité : l'API ne renvoie jamais l'email ni les notes ; la consultation et l'annulation exigent un
  jeton personnel (haché en base, transmis dans un fragment d'URL ou un en-tête).
- Annulation par le visiteur jusqu'à 24 h avant le rendez-vous (configurable).
- Fermetures exceptionnelles par consulat ; les jours fermés ne sont plus proposés et le nombre de rendez-vous touchés est signalé.
- Rappels J-1 par email, une seule fois par rendez-vous.
- Purge RGPD automatique des données personnelles au-delà de `DATA_RETENTION_DAYS`.
- Export CSV des rendez-vous, graphique des rendez-vous par service dans le tableau de bord.
- Rôles et permissions (Spatie) : isolation des données par consulat, policies, super_admin.

## Tests

```bash
cd backend
php artisan test
```

Les tests utilisent SQLite en mémoire (`phpunit.xml`). Ils couvrent l'API publique (réservation, jeton, annulation,
ics, fermetures, consentement, anti-spam, rappels, purge), l'isolation multi-consulats et la création de contenu par un admin.

## Limites connues

- Pas d'envoi d'email réel par défaut (`MAIL_MAILER=log`). En production, configurez SMTP et une file d'attente.
- Pas encore : journal d'activité des actions admin, double authentification admin, PWA, images WebP automatiques,
  éditeur riche, tests de bout en bout dans le navigateur. Voir la table `feature_backlog` (MySQL).
- Pas de paiement ni de pré-demande en ligne : voir la feuille de route V2.
