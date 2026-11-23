# syntax=docker/dockerfile:1.7
#
# Trip Planner: one image for the web app, the queue worker and the scheduler (see docker-compose.yml).
#
#   docker compose up -d --build
#
# Behind a TLS-intercepting proxy, pass its CA as a build secret:
#   docker build --secret id=extra_ca,src=/path/to/ca.crt .

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

# --- Front end and user guide -------------------------------------------------------------------------------------
FROM node:${NODE_VERSION}-bookworm-slim AS assets
WORKDIR /app
ENV PUPPETEER_SKIP_DOWNLOAD=true
COPY package.json package-lock.json webpack.mix.js tailwind.config.js postcss.config.js ./
RUN --mount=type=secret,id=extra_ca,required=false \
    if [ -f /run/secrets/extra_ca ]; then export NODE_EXTRA_CA_CERTS=/run/secrets/extra_ca; fi; \
    npm ci --no-audit --no-fund
COPY docs/package.json docs/package-lock.json docs/
RUN --mount=type=secret,id=extra_ca,required=false \
    if [ -f /run/secrets/extra_ca ]; then export NODE_EXTRA_CA_CERTS=/run/secrets/extra_ca; fi; \
    npm --prefix docs ci --omit=dev --no-audit --no-fund
COPY resources resources
COPY public public
COPY docs docs
RUN npx mix --production && npm --prefix docs run build

# --- PHP dependencies ---------------------------------------------------------------------------------------------
FROM composer:2 AS vendor
ARG COMPOSER_FLAGS=--prefer-dist
WORKDIR /app
COPY composer.json composer.lock ./
# Extensions are checked in the final image; scripts run there too, once the app is in place.
RUN --mount=type=secret,id=extra_ca,required=false \
    if [ -f /run/secrets/extra_ca ]; then export SSL_CERT_FILE=/run/secrets/extra_ca COMPOSER_CAFILE=/run/secrets/extra_ca GIT_SSL_CAINFO=/run/secrets/extra_ca; fi; \
    composer install --no-dev --no-scripts --no-autoloader --no-interaction --no-progress --ignore-platform-reqs $COMPOSER_FLAGS

# --- Runtime ------------------------------------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache-bookworm AS app

RUN --mount=type=secret,id=extra_ca,required=false \
    if [ -f /run/secrets/extra_ca ]; then cp /run/secrets/extra_ca /usr/local/share/ca-certificates/extra-ca.crt; fi; \
    apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libzip-dev unzip \
    && update-ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl opcache pcntl pdo_mysql zip \
    && apt-get purge -y --auto-remove libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libzip-dev \
    && apt-get install -y --no-install-recommends libfreetype6 libicu72 libjpeg62-turbo libpng16-16 libzip4 \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-trip-planner.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers expires \
    && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor vendor
COPY --from=assets --chown=www-data:www-data /app/public/js public/js
COPY --from=assets --chown=www-data:www-data /app/public/css public/css
COPY --from=assets --chown=www-data:www-data /app/public/mix-manifest.json public/mix-manifest.json
COPY --from=assets --chown=www-data:www-data /app/public/docs public/docs

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

# Redis through predis (a Composer package), so the image needs no PECL extension.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    REDIS_CLIENT=predis

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["web"]
