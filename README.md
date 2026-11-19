# Espace consulaire — plateforme de gestion multi-consulats

Une seule base de code et une seule base MySQL servent plusieurs consulats. Chaque consulat dispose de son
site public (FR / EN / AR), de son administration (consul et agents) et d'un espace pour ses citoyens.
La plateforme gère les rendez-vous, les pré-demandes avec pièces justificatives, les décisions du consulat,
le suivi des dossiers jusqu'au retrait et les abonnements des consulats.

## Structure du dépôt

```
CONSULAT/
├── backend/    Laravel 12 — API JSON v1 (/api/v1) + administration Filament 3 (/admin)
├── frontend/   Next.js 16 (React 19, App Router) — plateforme, sites des consulats, espace citoyen (FR / EN / AR, RTL)
└── docs/       Rapports d'audit
```

Documents de référence : [backend/PLAN.md](backend/PLAN.md) (architecture initiale), [backend/DECISIONS.md](backend/DECISIONS.md),
[backend/docs/ROADMAP_V2.md](backend/docs/ROADMAP_V2.md), [backend/docs/DEPLOIEMENT.md](backend/docs/DEPLOIEMENT.md).

## Rôles

| Rôle | Espace | Responsabilités |
|---|---|---|
| `super_admin` | `/admin` | Création des consulats (assistant), abonnements et paiements, tableau global, signalements d'intégrité |
| `consulate_admin` (consul) | `/admin` | Son consulat : équipe, services, créneaux, fermetures, contenus, rendez-vous, dossiers, rapports, association, abonnement |
| `staff` (agent) | `/admin` | File du jour, rendez-vous, pré-demandes, comptes citoyens, encaissements, messages |
| Citoyen | site du consulat, `/compte` | Rendez-vous, pré-demandes avec pièces, famille, signalements, profil, rôle associatif |

Le rôle et le consulat sont vérifiés côté serveur à chaque requête (policies, scope par consulat, `canAccess`).
Entrée de connexion commune : `/connexion` (citoyen → consulat ; agents → administration).

## Fonctionnalités

**Consulat (administration)**
- Tableau de bord par rôle : accueil personnalisé, raccourcis autorisés, travail du jour, pilotage, vue plateforme.
- Rendez-vous : file du jour, pointage et numéro d'appel, confirmation, clôture, annulation, notes internes, export CSV.
- Pré-demandes : pièces stockées en privé (nom aléatoire, empreinte), examen, validation / renvoi / refus motivé, email au citoyen.
- Suivi de dossier : déposé → en traitement → prêt à retirer → retiré (ou rejeté), email à chaque étape.
- Encaissements avec reçus numérotés, plan de charge, rapport mensuel, fermetures exceptionnelles avec alerte.
- Comptes citoyens : validation de la résidence, refus motivé.
- Association des étudiants : membres, bureau relié aux comptes citoyens, élections, fin de mandat, archives, activités.
- Abonnement annuel des consulats : paiements, reçus, rappels, période de grâce, suspension et réactivation.
- Sécurité : double authentification (TOTP + codes de secours), journal d'activité, comptes de démonstration bloqués en production.

**Citoyen**
- Compte vérifié par email puis validé par le consulat ; modification du téléphone, changement d'email confirmé par lien.
- Prise, déplacement et annulation de rendez-vous ; export `.ics` ; espace famille ; signalements.

**Public**
- Plateforme : accueil avec recherche de consulat, À propos, Crédits, Connexion (FR / EN / AR).
- Site de chaque consulat : services, pièces, frais, prochain créneau, actualités, FAQ, contact, itinéraire, pages légales.

## Installation

Prérequis : PHP 8.2+ (`pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `fileinfo`, `exif`, `openssl`, `curl`), Composer 2,
Node.js 20+, MySQL 8.0.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # crée uniquement les rôles et permissions
php artisan storage:link
php artisan serve                # http://127.0.0.1:8000
```

```bash
cd frontend
npm ci
cp .env.example .env.local       # NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
npm run build && npm run start   # ou npm run dev
```

Données de démonstration (développement uniquement) : `SEED_DEMO=true php artisan db:seed`.
Elles se suppriment avec `php artisan consulat:purge-demo` (simulation : `--dry-run`), qui conserve un seul super administrateur.

Tâches planifiées : `php artisan schedule:run` chaque minute (rappels J-1, rappels d'abonnement, purge des données expirées).

## Tests

```bash
cd backend && php artisan test     # SQLite en mémoire
cd frontend && npx tsc --noEmit && npm run build
```

## Avant la mise en production

Voir [backend/docs/DEPLOIEMENT.md](backend/docs/DEPLOIEMENT.md) et les rapports dans [docs/](docs/) : SMTP, `APP_DEBUG=false`,
double authentification obligatoire pour les consuls, remplacement du compte super administrateur initial, HTTPS, sauvegardes.
