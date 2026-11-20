#!/usr/bin/env bash
# Prepare a local demo environment inside a build container.
#
# The demo database lives on a named volume, so it survives container rebuilds
# and every re-sync. Re-running this script does not wipe the data: it only
# migrates what is missing and seeds when the database is still empty.
#
# The dependency trees live outside the synced tree. DEPS points at them; the
# dev containers keep them in the shared cache volume.
#
# Usage: bash docker/demo.sh <container> [db-volume]
set -euo pipefail
CONTAINER="${1:?container name required}"
DB_VOLUME="${2:-sbx-lightlms-db}"
DEPS="${DEPS:-/work/deps}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

docker volume create "$DB_VOLUME" >/dev/null

# The volume starts root-owned; hand it to the host user so the container can
# write into it without elevated privileges.
docker run --rm -u 0 \
    --volume "$DB_VOLUME:/work/db" \
    --entrypoint sh docker.io/library/php:8.5-fpm-alpine \
    -c "chown -R $(id -u):$(id -g) /work/db"

bash "$SRC/docker/sync-container.sh" "$CONTAINER" /work/app "$DEPS"

docker exec -e APP_ENV=local -e DEPS="$DEPS" "$CONTAINER" sh -c '
set -e
cd /work/app

if [ ! -f "$DEPS/.env" ]; then
    cp /work/app/docker/demo.env "$DEPS/.env"
    php artisan key:generate --force --env=local
    echo "created $DEPS/.env from docker/demo.env"
fi

php artisan migrate --force

users=$(php -r "\$db = new PDO(\"sqlite:/work/db/database.sqlite\"); echo (int) \$db->query(\"select count(*) from users\")->fetchColumn();" 2>/dev/null || echo 0)

if [ "$users" -eq 0 ]; then
    php artisan db:seed --force --no-interaction
    echo "seeded demo data"
else
    echo "demo data already present ($users users), left untouched"
fi
'

echo "demo environment ready in $CONTAINER (database volume: $DB_VOLUME)"
