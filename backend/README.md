# Plateforme multi-consulats — DÉMO NON OFFICIELLE

> ⚠ **Démo / Maquette non officielle.** Données fictives (adresses, téléphones, frais, délais, personnes).
> Aucun logo ni armoirie officiels. Ce projet n'est ni édité ni validé par un État ou un consulat.

Une seule base de code et une seule base MySQL servent plusieurs consulats africains.
Démos incluses : **Consulat du Tchad au Sénégal** et **Consulat du Cameroun au Sénégal**.

Voir [PLAN.md](PLAN.md) (architecture et schéma), [DECISIONS.md](DECISIONS.md) (choix faits en autonomie),
[docs/ROADMAP_V2.md](docs/ROADMAP_V2.md) et [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md).

## Stack

Laravel 12 · PHP 8.2+ · MySQL 8 · Filament 3 · Blade + Livewire 3 · Tailwind CSS 4 (Vite) ·
spatie/laravel-translatable (FR / EN / AR) · spatie/laravel-permission · Leaflet / OpenStreetMap · PHPUnit.

## Prérequis

- PHP 8.2+ avec : `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `fileinfo`, `exif`, `openssl`, `curl`
- Composer 2, Node.js 20+ et npm
- MySQL 8.0

Sous Windows, `intl` et `exif` sont souvent désactivés dans `php.ini` : décommentez `extension=intl` et `extension=exif`,
ou, sans toucher à la configuration système, utilisez le dossier `.phpini/` (voir ci-dessous).

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Configurer MySQL

Créez la base (une seule fois) :

```sql
CREATE DATABASE consulat_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis renseignez `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306          # adaptez si votre MySQL écoute sur un autre port
DB_DATABASE=consulat_platform
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### Migrations et données de démo

```bash
php artisan migrate --seed
php artisan storage:link
```

Le seeder crée les rôles, les deux consulats (services, actualités, FAQ, pages légales, horaires,
créneaux, quelques rendez-vous et messages) et les comptes de démo.

### Lancer le projet

Deux terminaux :

```bash
php artisan serve        # http://127.0.0.1:8000
npm run dev              # Vite (rechargement des assets)
```

Pour une version compilée sans `npm run dev` : `npm run build`.

### Sous Windows : activer `intl` sans modifier `php.ini`

Le dossier `.phpini/` (ignoré par Git) contient un fichier qui active les extensions. Dans PowerShell :

```powershell
$env:PHP_INI_SCAN_DIR = "$PWD\.phpini"
php -m | Select-String intl
```

## URLs à ouvrir

| Page | URL |
|---|---|
| Choix du consulat | http://127.0.0.1:8000/ |
| Consulat du Tchad (FR) | http://127.0.0.1:8000/tchad-senegal |
| Consulat du Tchad (EN / AR) | http://127.0.0.1:8000/tchad-senegal?lang=en · `?lang=ar` (RTL) |
| Consulat du Cameroun | http://127.0.0.1:8000/cameroun-senegal |
| Prise de rendez-vous | http://127.0.0.1:8000/tchad-senegal/appointment |
| Administration | http://127.0.0.1:8000/admin |
| Sitemap / robots | http://127.0.0.1:8000/sitemap.xml · `/robots.txt` |

## Comptes de démo

Mot de passe commun : **`Demo#Consulat2026`** (modifiable via `DEMO_PASSWORD` dans `.env`, avant le seed).

| Rôle | Email | Accès |
|---|---|---|
| super_admin | `superadmin@demo.test` | tous les consulats, création de consulats |
| consulate_admin (Tchad) | `admin.tchad@demo.test` | contenus, rendez-vous, messages, utilisateurs et fiche du Tchad |
| staff (Tchad) | `staff.tchad@demo.test` | rendez-vous et messages, lecture des contenus |
| consulate_admin (Cameroun) | `admin.cameroun@demo.test` | idem pour le Cameroun |
| staff (Cameroun) | `staff.cameroun@demo.test` | idem pour le Cameroun |

Vérifier l'isolation : connectez-vous avec `admin.tchad@demo.test` : vous ne voyez que les données du Tchad.

## Tests

```bash
php artisan test
```

Les tests utilisent SQLite en mémoire (configuré dans `phpunit.xml`). Ils couvrent : capacité des créneaux,
refus d'une double réservation, isolation multi-consulats (admin et pages publiques), anti-spam du formulaire de contact.

## Fonctionnement multi-consulats

- Les pages publiques sont sous `/{slug}` ; le middleware `consulate` charge le consulat, la langue et le thème.
- Dans Filament, le global scope `ConsulateScope` limite chaque utilisateur à son consulat ; le super_admin voit tout.
  Les policies ajoutent une seconde vérification.
- Les couleurs du consulat sont injectées en variables CSS (`--brand`, `--brand-2`, `--brand-3`) après validation.

## Sécurité

- Formulaires : Form Requests, protection CSRF, échappement Blade (le texte saisi est affiché en texte brut).
- Anti-spam : honeypot + horodatage chiffré + rate limiting (contact : 5/min, rendez-vous : 10/min par IP).
- Anti double réservation : transaction + verrou `lockForUpdate` sur le créneau + recomptage des places.
- En-têtes : `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Aucun secret dans le code : tout passe par `.env`.

## Déploiement

Voir [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md).

## Limites connues

- Pas de véritable envoi d'email par défaut (`MAIL_MAILER=log`, les messages sont dans `storage/logs`).
- Créneaux récurrents hebdomadaires uniquement ; pas encore de fermetures exceptionnelles par date.
- Les rendez-vous ne peuvent pas être modifiés par le public (annulation via l'admin ou le contact).
- Pas de paiement ni de pré-demande en ligne : voir [docs/ROADMAP_V2.md](docs/ROADMAP_V2.md).
