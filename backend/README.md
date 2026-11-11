# Plateforme multi-consulats — DÉMO NON OFFICIELLE

> ⚠ **Démo / Maquette non officielle.** Données fictives (adresses, téléphones, frais, délais, personnes).
> Aucun logo ni armoirie officiels. Ce projet n'est ni édité ni validé par un État ou un consulat.

Une seule base de code et une seule base MySQL servent plusieurs consulats africains.
Démos incluses : **Consulat du Tchad au Sénégal** et **Consulat du Cameroun au Sénégal**.

Voir [PLAN.md](PLAN.md) (architecture et schéma), [DECISIONS.md](DECISIONS.md) (choix faits en autonomie),
[docs/ROADMAP_V2.md](docs/ROADMAP_V2.md) et [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md).

## Stack

Laravel 12 · PHP 8.2+ · MySQL 8 · API JSON v1 · Filament 3 ·
spatie/laravel-translatable (FR / EN / AR) · spatie/laravel-permission · PHPUnit.
Le site public est une application Next.js séparée dans `../frontend`.

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
php artisan serve        # API + administration : http://127.0.0.1:8000
```

Dans un second terminal, lancez le site public depuis `../frontend` :

```bash
cd ../frontend
npm install
npm run dev              # http://localhost:3000
```

### Sous Windows : activer `intl` sans modifier `php.ini`

Le dossier `.phpini/` (ignoré par Git) contient un fichier qui active les extensions. Dans PowerShell :

```powershell
$env:PHP_INI_SCAN_DIR = "$PWD\.phpini"
php -m | Select-String intl
```

## URLs à ouvrir

| Page | URL |
|---|---|
| Choix du consulat | http://localhost:3000/ |
| Consulat du Tchad (FR / EN / AR) | http://localhost:3000/tchad-senegal/fr · `/en` · `/ar` |
| Consulat du Cameroun (FR) | http://localhost:3000/cameroun-senegal/fr |
| Prise de rendez-vous | http://localhost:3000/tchad-senegal/fr/appointment |
| API publique | http://127.0.0.1:8000/api/v1/tchad-senegal |
| Administration | http://127.0.0.1:8000/admin |
| Sitemap / robots | http://localhost:3000/sitemap.xml · `/robots.txt` |

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

Les tests utilisent SQLite en mémoire (configuré dans `phpunit.xml`). Ils couvrent l'API publique, capacité et fermeture des créneaux,
protection des rendez-vous par jeton, consentement et purge, anti-spam, isolation multi-consulats et journal d'activité admin.

## Fonctionnement multi-consulats

- Les pages publiques sont servies par Next.js sous `/{slug}/{lang}`; l'API Laravel résout le consulat et la langue.
- Dans Filament, le global scope `ConsulateScope` limite chaque utilisateur à son consulat ; le super_admin voit tout.
  Les policies ajoutent une seconde vérification.
- Les couleurs du consulat sont injectées en variables CSS (`--brand`, `--brand-2`, `--brand-3`) après validation.

## Sécurité

- API : Form Requests et validation; les contenus saisis sont rendus en texte brut côté frontend.
- Anti-spam : honeypot + horodatage chiffré + rate limiting (contact : 5/min, rendez-vous : 10/min par IP).
- Anti double réservation : transaction + verrou `lockForUpdate` sur le créneau + recomptage des places.
- Les actions admin sont consignées sans conserver les valeurs des champs modifiés; l'accès au journal respecte l'isolation par consulat.
- En-têtes : `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Aucun secret dans le code : tout passe par `.env`.

## Déploiement

Voir [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md).

## Limites connues

- Pas de véritable envoi d'email par défaut (`MAIL_MAILER=log`, les messages sont dans `storage/logs`).
- Les emails sont envoyés de façon synchrone; le worker de file d'attente n'est pas encore utilisé par les notifications.
- Pas encore de double authentification admin ni de tests de bout en bout navigateur.
- Les rendez-vous peuvent être annulés par lien personnel; le visiteur ne peut pas encore les replanifier.
- Pas de paiement ni de pré-demande en ligne : voir [docs/ROADMAP_V2.md](docs/ROADMAP_V2.md).
