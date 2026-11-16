# syntax=docker/dockerfile:1

# One shared package cache for every stage and every ecosystem: pnpm's store and
# Composer's download cache live side by side under the same BuildKit cache
# mount, so both survive across rebuilds and across containers.

# ---------- Frontend build ----------
FROM docker.io/library/node:24.22.3-slim AS frontend

ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
    COREPACK_HOME=/pkg-cache/corepack \
    CI=true

WORKDIR /app

RUN corepack enable pnpm

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN --mount=type=cache,id=lightlms-pkg-cache,target=/pkg-cache \
    pnpm install --frozen-lockfile --ignore-scripts --store-dir=/pkg-cache/pnpm/store

COPY vite.config.ts tsconfig.json ./
COPY resources ./resources

RUN pnpm run build


# ---------- PHP dependencies ----------
FROM docker.io/library/php:8.5-fpm-alpine AS vendor

# mbstring and opcache ship with the base image; installing them again fails.
RUN apk add --no-cache --virtual .build-deps git unzip icu-dev oniguruma-dev postgresql-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl pcntl pdo_pgsql zip bcmath \
    && apk del --no-network .build-deps

COPY --from=composer:2.11.2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_CACHE_DIR=/pkg-cache/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,id=lightlms-pkg-cache,target=/pkg-cache \
    composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && composer run-script post-autoload-dump --no-interaction


# ---------- Runtime (nginx + php-fpm) ----------
FROM docker.io/library/php:8.5-fpm-alpine AS runtime

RUN apk add --no-cache nginx \
    && apk add --no-cache --virtual .build-deps icu-dev oniguruma-dev postgresql-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl pcntl pdo_pgsql zip bcmath \
    && apk del .build-deps \
    && apk add --no-cache icu-libs libpq oniguruma libzip

COPY docker/php/php.ini /usr/local/etc/php/conf.d/lightlms.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-lightlms.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN rm -f /etc/nginx/http.d/default.conf

WORKDIR /app

COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && mkdir -p /var/lib/nginx/logs /tmp/nginx \
    && chown -R www-data:www-data storage bootstrap/cache /var/lib/nginx /var/log/nginx /tmp/nginx

USER www-data

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["sh", "-c", "php-fpm -F & exec nginx -g 'daemon off;'"]
