# Refonte : connexion, tableaux de bord, À propos, Crédits — 12 novembre 2026

Légende : **CT** corrigé et testé · **CNT** corrigé mais non testé · **TF** testé et fonctionnel · **NC** non corrigé · **B** bloqué.

## 1. Problèmes identifiés

| Problème | Statut |
|---|---|
| « Espace citoyen » menait à la liste des consulats sans parcours de connexion clair ; agents et citoyens sans entrée commune | CT |
| Un citoyen qui tentait l'espace agents recevait seulement « identifiants incorrects » | CT |
| Compte de démonstration (mot de passe public) utilisable même en production | CT |
| Page de connexion citoyen affichée à un citoyen déjà connecté | CT |
| Connexion d'administration sombre (suivait le thème de l'ordinateur), couleur indigo sans lien avec la plateforme | CT |
| Tableaux de bord sans accueil ni raccourcis ; mêmes entrées pour tous | CT |
| Sur téléphone, menu latéral de l'administration ouvert par-dessus le contenu à chaque chargement | CT |
| Sur téléphone, boutons flottants « Besoin d'aide ? » et « WhatsApp » recouvrant les formulaires | CT |
| Pages À propos et Crédits peu structurées ; Crédits listant des logiciels au lieu des compétences | CT |

## 2. Rôles réellement présents

- **Super administrateur** (`super_admin`) : toute la plateforme.
- **Consul** (`consulate_admin`) : son consulat, son équipe, son abonnement.
- **Agent** (`staff`) : accueil, rendez-vous et dossiers de son consulat.
- **Citoyen** : compte séparé (table `citizens`, jeton Sanctum dans un cookie httpOnly), rattaché à un consulat.

## 3. Authentification et redirections

- `/connexion` : page d'entrée unique. Citoyen → choix du consulat → connexion citoyen. Agent → connexion de l'administration.
- Administration (Filament) : redirection d'un utilisateur déjà connecté, 5 tentatives par minute, **régénération de la session** à la connexion, invalidation à la déconnexion (fonctionnement de Filament, vérifié dans le code et par les tests).
- Rôle contrôlé côté serveur à chaque requête (`User::canAccessPanel`, `canAccess`, `canViewAny`, portée par consulat).
- Nouveau : compte `@demo.test` refusé en production (`User::isDemoAccount`), message dédié.
- Nouveau : un compte citoyen saisi dans l'espace agents est orienté vers l'espace citoyen (message affiché seulement si le mot de passe est correct, pour ne pas révéler l'existence des comptes).
- Citoyen : connexion vérifiée par l'API ; si une session valide existe, la page de connexion renvoie directement à l'espace ; déconnexion = jeton supprimé côté serveur.

### Comportement vérifié par rôle

| Rôle | Après connexion | Accès interdit vérifié | Statut |
|---|---|---|---|
| Super admin | Tableau de bord plateforme (raccourcis « Nouveau consulat », « Consulats ») | — | CT (test) |
| Consul | Accueil + « Aujourd'hui au consulat » + « Mon consulat » | tableau global, autres consulats (403/404) | CT (test + navigateur) |
| Agent | Accueil + travail du jour | utilisateurs, tableau global, nouveau consulat (403) | CT (test + navigateur) |
| Citoyen | `/compte` avec « Bonjour, … » | autre consulat (401), après déconnexion (401) | CT (navigateur) |
| Non connecté | — | `/admin`, `/admin/appointments`, `/admin/users`, `/admin/file-du-jour` → connexion | CT (test) |

## 4. Améliorations visuelles

- **Connexion administration** : fond clair et doux, carte arrondie à ombre légère, logo et couleurs de la plateforme (vert profond `#1b5148`), titre « Espace agents et consuls », encart d'information, lien vers l'espace citoyen. Mode sombre désactivé.
- **Connexion citoyen** : logo et nom du consulat, badge « Espace citoyen », champs espacés, affichage/masquage du mot de passe, indicateur de chargement, erreurs avec icône, liens inscription et espace agents.
- **Tableaux de bord** : palette unifiée, cartes et tableaux adoucis, accueil personnalisé (date, salutation, rôle, consulat), jusqu'à 6 raccourcis filtrés par autorisation, menu repliable, recherche Ctrl+K, menu fermé par défaut sur téléphone.
- **À propos** : en-tête avec encadré « outil d'information et d'orientation », mission (3 cartes), difficultés traitées, avantages (4 cartes), fonctionnement en 4 étapes, « sur la plateforme / au consulat ».
- **Crédits** : profil (nom, prénom, profil), présentation, 6 domaines d'intervention en cartes (sans niveaux ni certifications ; coaching et formation présentés comme domaines d'intérêt), contribution à la plateforme, mot du concepteur. Aucun lien externe ajouté (aucun lien réel fourni).

## 5. Fichiers modifiés ou créés

Backend : `app/Filament/Pages/Auth/Login.php`, `app/Models/User.php`, `app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/Admin/Widgets/WelcomeWidget.php` (nouveau), `resources/views/filament/brand.blade.php` (nouveau), `resources/views/filament/theme-styles.blade.php` (nouveau), `resources/views/filament/admin/widgets/welcome.blade.php` (nouveau), `resources/views/filament/auth/login-links.blade.php`, `tests/Feature/AuthFlowTest.php` (nouveau), `tests/Feature/RoleDashboardTest.php`.

Frontend : `app/connexion/page.tsx` (nouveau), `components/CitizenEntry.tsx` (nouveau), `app/a-propos/page.tsx`, `app/credits/page.tsx`, `app/[consulate]/[lang]/compte/connexion/page.tsx`, `components/CitizenAuth.tsx`, `components/CitizenDashboard.tsx`, `components/PlatformChrome.tsx`, `components/ChatBot.tsx`, `components/SiteFooter.tsx`, `lib/i18n.ts`.

## 6. Tests exécutés

| Test | Résultat |
|---|---|
| Backend complet `php artisan test` | **184 réussis**, 542 assertions |
| dont `AuthFlowTest` (7) et `RoleDashboardTest` (8) | réussis |
| Frontend `tsc --noEmit` | aucune erreur |
| Frontend `next build` | réussi (32 pages) |
| Navigateur : connexion administration (rendu clair), tableau de bord consul ordinateur et téléphone | TF |
| Navigateur : `/connexion` → choix du consulat → connexion citoyen ; mauvais mot de passe ; afficher/masquer ; connexion ; retour sur la page de connexion (redirection) ; déconnexion | TF |
| Téléphone (375 px) : accueil, À propos, Connexion, Crédits, connexion citoyen sans débordement horizontal | TF |

Les parcours navigateur ont utilisé des données de démonstration **rechargées temporairement**, puis supprimées (`consulat:purge-demo`) : la base contient de nouveau uniquement le super administrateur.

## 7. Problèmes restants et décisions

| Point | Statut |
|---|---|
| Changer l'email et le mot de passe de `superadmin@demo.test` (sinon bloqué en production par la nouvelle règle) | décision / action de votre part |
| Double authentification obligatoire (`TWO_FACTOR_REQUIRED_ROLES=super_admin,consulate_admin`) | décision |
| ESLint en panne (`npm ci` à relancer) | B |
| Rendu tablette de l'administration non vérifié en détail | NC (non testé) |
| À propos, Crédits et Connexion disponibles en **français, anglais et arabe** (`/a-propos`, `/a-propos/en`, `/a-propos/ar`, idem `/credits` et `/connexion`), sélecteur de langue dans l'en-tête, arabe de droite à gauche, textes dans `frontend/lib/platformText.ts` | CT |
| Traductions anglaise et arabe non relues par un locuteur natif | à confirmer |
| Choix du consulat dans `/connexion/en` et `/connexion/ar` non testé avec des consulats réels (base vide) | CNT |
| Code HTTP 200 sur la page « introuvable » (rendu en flux de Next.js) | NC (faible) |
| Rien n'est commité ni déployé | — |
