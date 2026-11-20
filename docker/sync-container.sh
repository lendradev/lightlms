#!/usr/bin/env bash
# Sync workspace source into a build container without touching .git on the host.
#
# Layout inside the container:
#   /work/app       synced source tree (replaced on every sync)
#   /work/deps      installed dependencies (vendor/, node_modules/), survives syncs
#   /work/db        demo database, on a named volume so it survives container rebuilds
#
# /work/app/vendor and /work/app/node_modules are symlinks into /work/deps, so
# a sync never wipes an install and never leaves a stale tree behind.
# /work/app/database/database.sqlite and /work/app/.env point at /work/db, so
# the demo data and the local environment survive every sync.
#
# Usage: sync-container.sh <container> [dest-dir] [deps-dir]
set -euo pipefail

CONTAINER="${1:?container name required}"
DEST="${2:-/work/app}"
DEPS="${3:-/work/deps}"
DB_DIR="${DB_DIR:-/work/db}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

docker exec -e DEST="$DEST" -e DEPS="$DEPS" -e DB_DIR="$DB_DIR" "$CONTAINER" sh -c '
set -e
mkdir -p "$DEPS" "$DB_DIR"
# The installed trees are parked in "$DEPS" while the source tree is replaced.
# They go back to "$DEST" afterwards: Composer derives the base directory of
# its class map from the parent of the vendor directory, so a symlink out of
# the source tree would point the autoloader at the wrong place.
if [ -d "$DEST/vendor" ] && [ ! -d "$DEPS/vendor" ]; then
  mv "$DEST/vendor" "$DEPS/vendor"
fi
if [ -d "$DEST/node_modules" ] && [ ! -d "$DEPS/node_modules" ]; then
  mv "$DEST/node_modules" "$DEPS/node_modules"
fi
rm -rf "$DEST"
mkdir -p "$DEST"
'

tar -C "$SRC" \
  --exclude=.git --exclude=node_modules --exclude=vendor \
  --exclude=public/build --exclude=bootstrap/cache \
  --exclude=database/database.sqlite --exclude=.env \
  -cf - . | docker exec -i -e DEST="$DEST" "$CONTAINER" sh -c 'tar -C "$DEST" -xf -'

docker exec -e DEST="$DEST" -e DEPS="$DEPS" -e DB_DIR="$DB_DIR" "$CONTAINER" sh -c '
set -e
mkdir -p "$DEST/storage/framework/cache/data" "$DEST/storage/framework/sessions" \
         "$DEST/storage/framework/views" "$DEST/storage/logs" "$DEST/bootstrap/cache"
mkdir -p "$DEST/public" "$DB_DIR"
for d in vendor node_modules; do
  if [ -d "$DEPS/$d" ]; then
    mv "$DEPS/$d" "$DEST/$d"
  fi
done
if [ -d "$DEPS/public-build" ]; then
  ln -sfn "$DEPS/public-build" "$DEST/public/build"
fi
if [ -f "$DEPS/.env" ]; then
  ln -sfn "$DEPS/.env" "$DEST/.env"
fi
ln -sfn "$DB_DIR/database.sqlite" "$DEST/database/database.sqlite"
'
echo "synced $SRC -> $CONTAINER:$DEST"

# Laravel's package discovery cache lives in bootstrap/cache and is wiped by the
# sync, so it is rebuilt. Only PHP containers can do this; skip silently elsewhere.
docker exec -e DEST="$DEST" "$CONTAINER" sh -c '
set -e
cd "$DEST"
if command -v php >/dev/null 2>&1 && [ -d vendor ]; then
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
  php artisan package:discover --ansi >/dev/null
fi
' || echo "NOTE: skipped package discovery in $CONTAINER (no PHP runtime)" >&2
