#!/bin/sh
set -e

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Start server
exec php artisan serve --host=0.0.0.0 --port=8000