# Déploiement (préproduction / production)

La plateforme comprend deux applications à déployer : l'API / administration Laravel (`backend/`) et le site public Next.js (`frontend/`).
Cette procédure ne remplace pas une revue de sécurité, un test de restauration ni la validation des obligations locales de protection des données.

1. Utiliser un serveur maintenu avec PHP 8.2+, MySQL 8, Node.js 20+ et les extensions PHP requises (`pdo_mysql`, `intl`, `mbstring`, `zip`, `gd`, `fileinfo`, `exif`).
2. Construire Laravel avec `composer install --no-dev --prefer-dist --optimize-autoloader`; construire le frontend avec `npm ci && npm run build`.
3. Configurer les environnements hors du dépôt : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, identifiants MySQL, `FRONTEND_URL`, `NEXT_PUBLIC_API_URL` et `NEXT_PUBLIC_SITE_URL`. Ne jamais déployer les comptes de démonstration ni leur mot de passe.
4. Générer `APP_KEY` une seule fois et le sauvegarder de façon sécurisée. Appliquer `php artisan migrate --force` puis `php artisan db:seed --force` (rôles et permissions uniquement ; les données fictives ne sont créées qu'avec `SEED_DEMO=true`, à réserver au développement). Créer ensuite le super administrateur réel, puis supprimer tout compte `@demo.test` (`php artisan consulat:purge-demo`). En production, un compte `@demo.test` est de toute façon refusé à la connexion.
5. Configurer un transport SMTP authentifié et vérifier l'envoi ainsi que la réception des emails de réservation. En l'état, les notifications sont envoyées de façon synchrone; le worker n'est pas encore utilisé par les emails.
6. Exécuter `php artisan schedule:run` chaque minute via le cron système pour les rappels J-1 et la purge planifiée. Vérifier les journaux et la date du prochain passage.
7. Configurer HTTPS, les domaines autorisés/CORS, stockage privé des sauvegardes et accès restreint à `/admin`. Mettre en place des sauvegardes chiffrées quotidiennes et tester leur restauration.
8. Déployer d'abord en préproduction : exécuter `php artisan test`, lint/type-check/build du frontend, tester MySQL réel, les trois langues (dont RTL), les parcours contact/réservation/annulation et les courriels.

**Points à valider avant un usage réel :** double authentification **obligatoire** pour les consuls et le super administrateur (`TWO_FACTOR_REQUIRED_ROLES=super_admin,consulate_admin` ; la double authentification existe déjà, elle est facultative par défaut), configuration SMTP, supervision des tâches planifiées, procédures de sauvegarde/restauration (`consulat:backup`, `consulat:restore`) et validation juridique du traitement des données personnelles.
