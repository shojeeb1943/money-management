# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5 AS base

RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    pcntl

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-moneta.ini"

WORKDIR /app

# ---------------------------------------------------------------------------
FROM base AS builder

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git unzip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

RUN composer install --no-dev --optimize-autoloader --prefer-dist \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
FROM base AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends cron \
    && rm -rf /var/lib/apt/lists/* \
    && echo "* * * * * cd /app && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

COPY --from=builder /app /app
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/moneta-entrypoint

RUN chmod +x /usr/local/bin/moneta-entrypoint \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

VOLUME ["/app/storage"]

EXPOSE 8080

ENTRYPOINT ["moneta-entrypoint"]
