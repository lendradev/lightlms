#!/usr/bin/env bash
# Start the local build containers for LightLMS with one shared package cache.
#
# The cache volume holds pnpm's content-addressable store, Composer's download
# cache and the Corepack download, so both ecosystems reuse the same volume
# across containers and across rebuilds.
#
# The source tree is mounted read-only. Working trees that need to be written
# (node_modules, vendor, build output) live on a tmpfs inside the container.
#
# Usage:
#   bash docker/dev.sh start    # create the cache volume and start both containers
#   bash docker/dev.sh stop     # stop and remove both containers
#   bash docker/dev.sh status   # show container state
set -euo pipefail

CACHE_VOLUME="lightlms-pkg-cache"
NETWORK="lightlms-dev"
NODE_CONTAINER="lightlms-node"
PHP_CONTAINER="lightlms-php"
NODE_IMAGE="docker.io/library/node:24.22.3-slim"
PHP_IMAGE="docker.io/library/php:8.5-fpm-alpine"
COMPOSER_IMAGE="docker.io/library/composer:2.11.2"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

start() {
    docker volume create "$CACHE_VOLUME" >/dev/null
    docker network inspect "$NETWORK" >/dev/null 2>&1 || docker network create "$NETWORK" >/dev/null

    docker rm -f "$NODE_CONTAINER" "$PHP_CONTAINER" >/dev/null 2>&1 || true

    # The cache volume is created root-owned; hand it to the host user so both
    # containers can write into it without elevated privileges.
    docker run --rm -u 0 \
        --volume "$CACHE_VOLUME:/pkg-cache" \
        --entrypoint sh "$NODE_IMAGE" \
        -c "chown -R $(id -u):$(id -g) /pkg-cache"

    docker run -d --name "$NODE_CONTAINER" --network "$NETWORK" \
        --user "$(id -u):$(id -g)" \
        --security-opt no-new-privileges \
        --memory 2g --cpus 2 --pids-limit 512 \
        --tmpfs /work:rw,exec,nosuid,nodev,size=2g,mode=1777 \
        --mount "type=volume,src=$CACHE_VOLUME,dst=/pkg-cache" \
        -e COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
        -e COREPACK_HOME=/pkg-cache/corepack \
        -e PNPM_HOME=/pkg-cache/pnpm \
        -v "$ROOT:/src:ro" \
        -w /src \
        --entrypoint sh "$NODE_IMAGE" \
        -c 'mkdir -p /pkg-cache/bin /work/app \
            && corepack enable --install-directory /pkg-cache/bin pnpm \
            && export PATH="/pkg-cache/bin:$PATH" \
            && cd /work/app \
            && cp -r /src/. /work/app/ \
            && exec sleep infinity' >/dev/null

    docker run -d --name "$PHP_CONTAINER" --network "$NETWORK" \
        --user "$(id -u):$(id -g)" \
        --security-opt no-new-privileges \
        --memory 2g --cpus 2 --pids-limit 512 \
        --tmpfs /work:rw,exec,nosuid,nodev,size=2g,mode=1777 \
        --mount "type=volume,src=$CACHE_VOLUME,dst=/pkg-cache" \
        -e COMPOSER_HOME=/pkg-cache/composer \
        -e COMPOSER_CACHE_DIR=/pkg-cache/composer/cache \
        -v "$ROOT:/src:ro" \
        --entrypoint sh "$COMPOSER_IMAGE" \
        -c 'mkdir -p /work/app && cp -r /src/. /work/app/ && cd /work/app && exec sleep infinity' >/dev/null

    echo "started $NODE_CONTAINER and $PHP_CONTAINER (shared cache volume: $CACHE_VOLUME)"
}

case "${1:-start}" in
    start) start ;;
    stop)
        docker rm -f "$NODE_CONTAINER" "$PHP_CONTAINER" >/dev/null 2>&1 || true
        echo "stopped build containers"
        ;;
    status)
        docker ps -a --filter "name=$NODE_CONTAINER" --filter "name=$PHP_CONTAINER" \
            --format '{{.Names}}\t{{.Status}}'
        ;;
    *)
        echo "usage: $0 {start|stop|status}" >&2
        exit 1
        ;;
esac
