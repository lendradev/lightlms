#!/bin/sh
set -e

cd /app

mkdir -p /tmp/nginx/client_body /tmp/nginx/proxy /tmp/nginx/fastcgi /tmp/nginx/uwsgi /tmp/nginx/scgi

if [ -z "${APP_KEY}" ]; then
    echo "APP_KEY is not set. Run: docker compose exec app php artisan key:generate --show" >&2
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan migrate --force --no-interaction

if [ -n "${LIGHTLMS_SEED_DEMO_DATA}" ] && [ "${LIGHTLMS_SEED_DEMO_DATA}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link >/dev/null 2>&1 || true

exec "$@"
