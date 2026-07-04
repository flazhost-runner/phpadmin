# PHPAdmin starter kit — FlazHost PaaS (CapRover) build context.
#
# Single-stage image on php:8.3-cli-alpine. The container:
#   - installs composer deps (--no-dev) at build time
#   - runs Phinx migrations + idempotent seed on boot (never blocks start)
#   - serves via the PHP built-in server: php -S 0.0.0.0:$APP_PORT -t public
#   - listens on $APP_PORT (default 80; CapRover injects $PORT → mapped in
#     the entrypoint)
#   - defaults to a zero-config SQLite DB under /app/data and a bundled
#     local Redis, while allowing managed DB/Redis purely via env vars.
FROM php:8.3-cli-alpine

WORKDIR /app

# Runtime extras:
#   - redis : bundled local session/cache store for zero-config deploys
#   - tini  : proper PID 1 / signal handling (graceful SIGTERM shutdown)
# PHP extensions (composer.json / src audit):
#   - pdo_mysql / pdo_pgsql : managed MySQL / PostgreSQL (pdo_sqlite is built in)
#   - gd (jpeg/webp/freetype) : Media module thumbnails (imagecreatefrom*)
RUN apk add --no-cache redis tini libpq libpng libjpeg-turbo libwebp freetype \
 && apk add --no-cache --virtual .build-deps \
        postgresql-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql gd \
 && apk del .build-deps

# PHP config: production ini as base, but force variables_order to include
# "E" — AppConfig/phinx.php read $_ENV, which php.ini-production would leave
# empty (GPCS) and silently break all env-driven configuration.
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
 && { \
        echo 'variables_order = "EGPCS"'; \
        echo 'memory_limit = 256M'; \
        echo 'upload_max_filesize = 32M'; \
        echo 'post_max_size = 32M'; \
    } > /usr/local/etc/php/conf.d/zz-flazhost.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# App source + production dependencies (composer.lock is authoritative).
COPY . .
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --optimize-autoloader \
 && rm -f /app/.env

# Writable locations: default SQLite DB + runtime secrets (/app/data, mount a
# volume there to persist) and local upload storage.
RUN mkdir -p /app/data /app/storage/uploads \
 && chmod +x /app/docker-entrypoint.sh

# --- Defaults: zero-config boot, every value overridable via env -------------
# DB_DATABASE stays RELATIVE on purpose: the app resolves it against APP_ROOT
# (/app) while Phinx resolves it against the workdir (/app) — a relative path
# is the only spelling both agree on (→ /app/data/phpadmin.sqlite3).
ENV APP_NAME=PHPAdmin \
    APP_ENV=production \
    APP_MODE=full \
    APP_PORT=80 \
    APP_URL=http://localhost \
    DB_DRIVER=sqlite \
    DB_DATABASE=data/phpadmin.sqlite3 \
    SESSION_DRIVER=database \
    REDIS_URL=redis://127.0.0.1:6379 \
    STORAGE_DRIVER=local \
    STORAGE_BASE_PATH=storage/uploads \
    TZ=UTC

EXPOSE 80

ENTRYPOINT ["/sbin/tini", "--", "/app/docker-entrypoint.sh"]
