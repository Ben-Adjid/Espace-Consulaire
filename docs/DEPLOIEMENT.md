# Déploiement (aperçu)

1. Serveur PHP 8.2+ avec extensions `pdo_mysql`, `intl`, `mbstring`, `zip`, `gd`, `fileinfo`, `exif`.
2. Copier le projet, `composer install --no-dev -o`, `npm ci && npm run build`.
3. `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine`, identifiants MySQL.
4. `php artisan key:generate` (une seule fois), `php artisan migrate --force`, `php artisan db:seed --force` (démo uniquement).
5. `php artisan storage:link`, `php artisan config:cache route:cache view:cache event:cache`.
6. Pointer le domaine vers `public/`, HTTPS obligatoire, sauvegardes MySQL quotidiennes.
7. Pour de vrais emails : configurer `MAIL_MAILER` (SMTP) ; en production, passer les jobs en file (`QUEUE_CONNECTION=database` + `php artisan queue:work`).
