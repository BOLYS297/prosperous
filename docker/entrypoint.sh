#!/usr/bin/env bash
set -e

cd /var/www/html

# 1. S'assurer que les dossiers inscriptibles existent (volumes montés vides au 1er démarrage)
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# 2. Droits d'écriture pour PHP-FPM (www-data)
chown -R www-data:www-data storage bootstrap/cache || true

# 3. Lien symbolique public/storage -> storage/app/public (idempotent)
php artisan storage:link --quiet 2>/dev/null || true

# 4. Découverte des packages (au cas où, après un build)
php artisan package:discover --ansi 2>/dev/null || true

# Lance la commande demandée (supervisord par défaut, ou queue:work / schedule:work)
exec "$@"
