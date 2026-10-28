# syntax=docker/dockerfile:1

ARG PHP_IMAGE=serversideup/php:8.5-fpm-nginx
ARG NODE_IMAGE=node:24-trixie-slim

############################################
# Base: runtime PHP com extensões extras
############################################
FROM ${PHP_IMAGE} AS base

USER root
RUN install-php-extensions intl
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/academy-entrypoint
ENTRYPOINT ["academy-entrypoint"]
CMD ["/init"]

ENV PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off \
    LOG_OUTPUT_LEVEL=warn \
    HEALTHCHECK_PATH=/up

WORKDIR /var/www/html
USER www-data

############################################
# Vendor: dependências Composer de produção
############################################
FROM base AS vendor

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

############################################
# Assets: build do frontend (Wayfinder precisa do PHP)
############################################
FROM ${NODE_IMAGE} AS node

FROM ${PHP_IMAGE} AS assets

USER root
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN cp .env.example .env \
    && composer dump-autoload \
    && php artisan key:generate \
    && npm run build

############################################
# App: imagem de produção (web, horizon, scheduler)
############################################
FROM base AS app

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && rm -rf tests docs node_modules .env

############################################
# Video worker: app + FFmpeg
############################################
FROM app AS video-worker

USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/*
USER www-data

CMD ["php", "/var/www/html/artisan", "queue:work", "redis-video", "--queue=video", "--timeout=7200", "--tries=2", "--sleep=3"]
