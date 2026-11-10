# DECISIONS

Décisions prises en autonomie pendant le développement.

| # | Décision | Raison |
|---|---|---|
| 1 | **Laravel 12** au lieu de Laravel 11 | Toutes les versions 11.x sont bloquées par des avis de sécurité Composer (`block-insecure`). Laravel 12 est compatible Filament 3 et PHP 8.2+. |
| 2 | **Filament ~3.3.57** | Les versions ≤ 3.3.51 sont touchées par CVE‑2026‑48500 (upload temporaire non authentifié). |
| 3 | `.env` en **MySQL**, vérification locale en SQLite | Sur la machine de dev, le MySQL local écoute sur le port 3307 avec un mot de passe root inconnu. Le code reste portable (aucune syntaxe propre à MySQL) ; les tests tournent en SQLite mémoire. |
| 4 | Extension PHP **intl** requise (Filament) | Non activée dans `php.ini` ; un dossier `.phpini/` local (variable `PHP_INI_SCAN_DIR`) l'active sans toucher à la config système. Voir README. |
| 5 | Langue choisie par `?lang=xx` puis mémorisée en **session** | Plus léger que des préfixes d'URL ; les URL restent `/{consulat}/…`. Balises `hreflang` fournies. |
| 6 | Contenus traduisibles = colonnes JSON (`spatie/laravel-translatable`), repli sur le français | Un contenu non traduit reste affiché en FR plutôt que vide. |
| 7 | Documents requis = texte multiligne (1 document par ligne) | Édition simple dans Filament, rendu en checklist côté public. |
| 8 | Créneaux = **récurrence hebdomadaire** (`weekday`, heures, capacité) | Couvre 95 % des besoins d'un consulat ; les fermetures exceptionnelles seront gérées en V2. |
| 9 | Scope multi-tenant actif **uniquement dans le panneau admin** (panel Filament courant) | Le site public requête explicitement par consulat ; un admin connecté peut donc visiter tous les sites. Les Policies ajoutent une 2ᵉ barrière. |
| 10 | Un admin de consulat édite son consulat via la ressource « Mon consulat » (liste filtrée par scope, ni création ni suppression) | Réutilise l'éditeur multilingue de Filament. |
| 11 | Logo : si aucun fichier, **logo neutre généré** (bandes aux couleurs du consulat + initiales) | Aucun logo/armoirie officiel ne doit être utilisé. |
| 12 | Emails via le mailer `log` par défaut | Aucune clé SMTP dans le code ; changer `MAIL_*` dans `.env` pour un vrai envoi. |
| 13 | Anti double réservation : transaction + `lockForUpdate` sur le créneau + recomptage + refus d'un même email sur un même créneau/jour | Garantit la capacité même en cas de requêtes concurrentes. |
| 14 | Tests en PHPUnit (fourni par Laravel) | Zéro dépendance supplémentaire. |
| 15 | Fonctionnalités V2 : drapeaux `config/features.php` + contrats PHP + `docs/ROADMAP_V2.md`, sans implémentation | Demandé : « structure sans tout implémenter ». |

## Décisions ajoutées en fin de développement

| # | Décision | Raison |
|---|---|---|
| 16 | Consulat courant enregistré en **singleton** (`ConsulateContext`) | Sans cela, le middleware et le contrôleur recevaient deux instances différentes : toutes les pages renvoyaient 404. |
| 17 | Disponibilité calculée avec `whereDate` et non `whereBetween` | Comparaison portable : SQLite stocke les dates avec heure, MySQL non. Le test de capacité l'a révélé. |
| 18 | Le scope multi-consulats n'agit que dans le panneau Filament | Décision 9 confirmée ; les tests simulent le panneau courant pour vérifier l'isolation admin. |
| 19 | Vérification locale sur **SQLite** (tests) ; MySQL non vérifié de bout en bout | Le MySQL local (port 3307) n'accepte pas le compte root sans mot de passe connu. Les migrations sont standards, mais à rejouer sur MySQL avant mise en production. |
| 20 | Commits signés `Co-Authored-By` Sonnet 5.5 jusqu'à l'étape site public, puis Haiku 5.5 | Consigne d'attribution reçue en cours de session. |
