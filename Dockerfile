# syntax=docker/dockerfile:1

# ---------- Frontend build ----------
FROM docker.io/library/node:24.22.3-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts --no-audit --no-fund

COPY vite.config.ts tsconfig.json ./
COPY resources ./resources

RUN npm run build


# ---------- PHP dependencies ----------
FROM docker.io/library/php:8.5-fpm-alpine AS vendor

RUN apk add --no-cache --virtual .build-deps git unzip icu-dev oniguruma-dev postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl mbstring opcache pcntl pdo_pgsql zip bcmath

COPY --from=composer:2.11.2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && composer run-script post-autoload-dump --no-interaction


# ---------- Runtime (nginx + php-fpm) ----------
FROM docker.io/library/php:8.5-fpm-alpine AS runtime

RUN apk add --no-cache nginx \
    && apk add --no-cache --virtual .build-deps icu-dev oniguruma-dev postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl mbstring opcache pcntl pdo_pgsql zip bcmath \
    && apk del .build-deps \
    && apk add --no-cache icu-libs libpq oniguruma

COPY docker/php/php.ini /usr/local/etc/php/conf.d/lightlms.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-lightlms.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /app

COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
COPY --from=frontend --chown=www-data:www-data /app/public/hot ./public/hot

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["sh", "-c", "php-fpm -F & exec nginx -g 'daemon off;'"]
