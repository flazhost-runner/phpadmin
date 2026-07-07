#!/bin/sh
# Container boot sequence for the PHPAdmin starter kit (FlazHost / CapRover):
#   1. map CapRover's $PORT → APP_PORT (the port the built-in server binds)
#   2. ensure mandatory secrets exist (generate + persist if not provided)
#   3. start a bundled local Redis when REDIS_URL points at localhost
#   4. run Phinx migrations + idempotent seed (WARN on failure, never block boot)
#   5. exec the PHP built-in server on 0.0.0.0:$APP_PORT (mirrors composer "start")
set -eu

cd /app

# --- 1. Port: CapRover injects $PORT (default 80) -----------------------------
: "${PORT:=80}"
export APP_PORT="${APP_PORT:-$PORT}"

DATA_DIR=/app/data
SECRETS_FILE="$DATA_DIR/.runtime-secrets"
mkdir -p "$DATA_DIR"

gen_secret() {
    php -r 'echo bin2hex(random_bytes(32));'
}

# --- 2. Secrets (SESSION_SECRET / JWT_SECRET) ---------------------------------
# Honour values supplied via the environment. Otherwise generate strong random
# secrets once and persist them so sessions/JWTs survive container restarts
# (persists across restarts when /app/data is a mounted volume).
# AppConfig enforces >=32 chars in production — 64 hex chars satisfies that.
[ -f "$SECRETS_FILE" ] && . "$SECRETS_FILE"

if [ -z "${SESSION_SECRET:-}" ]; then
    SESSION_SECRET="$(gen_secret)"
    echo "SESSION_SECRET=$SESSION_SECRET" >> "$SECRETS_FILE"
    echo "[entrypoint] Generated SESSION_SECRET (persisted in $SECRETS_FILE)"
fi
if [ -z "${JWT_SECRET:-}" ]; then
    JWT_SECRET="$(gen_secret)"
    echo "JWT_SECRET=$JWT_SECRET" >> "$SECRETS_FILE"
    echo "[entrypoint] Generated JWT_SECRET (persisted in $SECRETS_FILE)"
fi
export SESSION_SECRET JWT_SECRET

# --- 3. Bundled Redis (only when targeting localhost) --------------------------
# Used by the predis session handler when SESSION_DRIVER=redis (default is
# database). A managed Redis is used by pointing REDIS_URL at a non-local host.
case "${REDIS_URL:-}" in
    ""|*127.0.0.1*|*localhost*)
        echo "[entrypoint] Starting bundled redis-server (REDIS_URL=${REDIS_URL:-default})"
        redis-server --daemonize yes --save "" --appendonly no >/dev/null 2>&1 || \
            echo "[entrypoint] WARN: could not start bundled redis-server"
        ;;
    *)
        echo "[entrypoint] Using external Redis at $REDIS_URL"
        ;;
esac

# --- 4. Database directory + migrations + seed --------------------------------
# Default DB is SQLite under /app/data; managed DBs are driven purely by env
# (DB_DRIVER/DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD/DB_DATABASE) with no edits.

# Platform mengirim DB_TYPE generik (mysql|mariadb|postgres) saat DB di-link —
# app membaca DB_DRIVER dengan nilai PDO (mysql|pgsql|sqlite). DB_TYPE = niat
# eksplisit → menang atas default Dockerfile DB_DRIVER=sqlite (pola djangoadmin
# DB_TYPE→DB_ENGINE / laraveladmin DB_TYPE→DB_CONNECTION).
if [ -n "${DB_TYPE:-}" ]; then
    case "$DB_TYPE" in
        mysql|mariadb)          export DB_DRIVER=mysql ;;
        postgres|postgresql|pg) export DB_DRIVER=pgsql ;;
        sqlite|sqlite3)         export DB_DRIVER=sqlite ;;
        *) echo "[entrypoint] WARN: unknown DB_TYPE='$DB_TYPE' — keeping DB_DRIVER=${DB_DRIVER:-sqlite}" ;;
    esac
fi

if [ "${DB_DRIVER:-sqlite}" = "sqlite" ] && [ "${DB_DATABASE:-}" != ":memory:" ]; then
    case "${DB_DATABASE:-}" in
        /*) mkdir -p "$(dirname "$DB_DATABASE")" 2>/dev/null || true ;;
        ?*) mkdir -p "/app/$(dirname "$DB_DATABASE")" 2>/dev/null || true ;;
    esac
fi

echo "[entrypoint] DB_DRIVER=${DB_DRIVER:-sqlite} DB_DATABASE=${DB_DATABASE:-} APP_PORT=${APP_PORT} APP_ENV=${APP_ENV:-production}"

# Idempotent: phinx skips applied migrations; InitialSeed guards every INSERT
# (creates admin@admin.com / 12345678 on first boot only). A failure here
# (e.g. transient managed-DB outage) must NOT block boot — log and continue so
# the server can still come up against an existing schema.
echo "[entrypoint] Running database migrations (phinx migrate)..."
if php vendor/bin/phinx migrate -e production; then
    echo "[entrypoint] Migrations applied. Running seed (idempotent)..."
    php vendor/bin/phinx seed:run -e production -s InitialSeed || \
        echo "[entrypoint] WARN: seed exited non-zero — continuing"
else
    echo "[entrypoint] WARN: migrate exited non-zero — continuing to start server"
fi

# --- 5. Start the built-in server (PID 1 via tini for clean SIGTERM) ----------
# Mirrors composer.json "start": php -d opcache.enable=0 -S ... -t public public/index.php
echo "[entrypoint] Starting server on 0.0.0.0:${APP_PORT}"
exec php -d opcache.enable=0 -S "0.0.0.0:${APP_PORT}" -t public public/index.php
