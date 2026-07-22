#!/bin/bash

echo "Generating .env file..."

# Générer un .env propre avec seulement les variables dont nous avons besoin
cat > .env <<EOF
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_KEY=${APP_KEY}
APP_URL=${APP_URL:-https://nexus-cyi6.onrender.com}

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DATABASE_URL=${DATABASE_URL}

BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}
REVERB_APP_ID=${REVERB_APP_ID}
REVERB_APP_KEY=${REVERB_APP_KEY}
REVERB_APP_SECRET=${REVERB_APP_SECRET}
REVERB_HOST=${REVERB_HOST:-0.0.0.0}
REVERB_PORT=${REVERB_PORT:-6001}
REVERB_SCHEME=${REVERB_SCHEME:-http}

VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
VITE_REVERB_HOST=${VITE_REVERB_HOST}
VITE_REVERB_PORT=${VITE_REVERB_PORT}
VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

SESSION_DRIVER=${SESSION_DRIVER:-database}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
CACHE_STORE=${CACHE_STORE:-database}
EOF

echo "Fichier .env généré avec succès."

# Nettoyer le cache et régénérer
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations et seed (à exécuter une seule fois, mais on peut les laisser)
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force

php artisan migrate --force
# Lancer Supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf