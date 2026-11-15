#!/usr/bin/env bash
# Sync workspace source into a build container without touching .git on the host.
#
# Layout inside the container:
#   /work/app       synced source tree (replaced on every sync)
#   /work/deps      installed dependencies (vendor/, node_modules/), survives syncs
#
# /work/app/vendor and /work/app/node_modules are symlinks into /work/deps, so
# a sync never wipes an install and never leaves a stale tree behind.
#
# Usage: sync-container.sh <container> [dest-dir] [deps-dir]
set -euo pipefail

CONTAINER="${1:?container name required}"
DEST="${2:-/work/app}"
DEPS="${3:-/work/deps}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

docker exec -e DEST="$DEST" -e DEPS="$DEPS" "$CONTAINER" sh -c '
set -e
mkdir -p "$DEPS"
rm -rf "$DEST"
mkdir -p "$DEST"
'

tar -C "$SRC" \
  --exclude=.git --exclude=node_modules --exclude=vendor \
  --exclude=public/build --exclude=bootstrap/cache \
  --exclude=database/database.sqlite --exclude=.env \
  -cf - . | docker exec -i -e DEST="$DEST" "$CONTAINER" sh -c 'tar -C "$DEST" -xf -'

docker exec -e DEST="$DEST" -e DEPS="$DEPS" "$CONTAINER" sh -c '
set -e
mkdir -p "$DEST/storage/framework/cache/data" "$DEST/storage/framework/sessions" \
         "$DEST/storage/framework/views" "$DEST/storage/logs" "$DEST/bootstrap/cache"
mkdir -p "$DEST/public"
for d in vendor node_modules; do
  if [ -d "$DEPS/$d" ]; then
    ln -sfn "$DEPS/$d" "$DEST/$d"
  fi
done
if [ -d "$DEPS/public-build" ]; then
  ln -sfn "$DEPS/public-build" "$DEST/public/build"
fi
if [ -f "$DEPS/.env" ]; then
  ln -sfn "$DEPS/.env" "$DEST/.env"
fi
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
