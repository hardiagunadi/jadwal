#!/usr/bin/env bash
set -euo pipefail

############################################
# CONFIG (override via env)
############################################
APP_DIR="${APP_DIR:-/var/www/jadwal}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"
DEPLOY_USER="${DEPLOY_USER:-sapawatu}"

DOMAIN="${DOMAIN:-localhost}"

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

RUN_MIGRATIONS="${RUN_MIGRATIONS:-0}"
CACHE_OPTIMIZE="${CACHE_OPTIMIZE:-1}"

############################################
# SAFE ROOT HANDLER
############################################
as_root() {
  if [[ $EUID -eq 0 ]]; then
    "$@"
  else
    sudo "$@"
  fi
}

run_as() {
  local user="$1"
  shift
  sudo -u "$user" "$@"
}

############################################
# CHECK REQUIREMENTS
############################################
need_cmd() {
  command -v "$1" >/dev/null 2>&1 || { echo "Missing command: $1"; exit 1; }
}

echo "[0/7] Checking requirements..."
need_cmd php
need_cmd composer
need_cmd git

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

############################################
# FIX OWNERSHIP
############################################
echo "[1/7] Fixing ownership..."
as_root chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "${APP_DIR}" || true

############################################
# WRITABLE FOLDERS
############################################
echo "[2/7] Preparing writable folders..."
as_root mkdir -p "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

as_root chown -R "${APP_USER}:${APP_GROUP}" \
  "${APP_DIR}/storage" \
  "${APP_DIR}/bootstrap/cache"

as_root chmod -R 2775 \
  "${APP_DIR}/storage" \
  "${APP_DIR}/bootstrap/cache"

as_root find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" \
  -type f -exec chmod 664 {} \;

############################################
# ENV FILE
############################################
echo "[3/7] Preparing .env..."

if [[ ! -f "${APP_DIR}/.env" && -f "${APP_DIR}/.env.example" ]]; then
  cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
fi

as_root chown "${DEPLOY_USER}:${DEPLOY_USER}" "${APP_DIR}/.env" 2>/dev/null || true
as_root chmod 664 "${APP_DIR}/.env" 2>/dev/null || true

set_env() {
  local key="$1"
  local val="$2"
  [[ -z "$val" ]] && return 0

  if grep -qE "^${key}=" "${APP_DIR}/.env"; then
    sed -i "s|^${key}=.*|${key}=${val}|g" "${APP_DIR}/.env"
  else
    printf "\n%s=%s\n" "$key" "$val" >> "${APP_DIR}/.env"
  fi
}

set_env "APP_URL" "https://${DOMAIN}"
set_env "DB_HOST" "${DB_HOST}"
set_env "DB_PORT" "${DB_PORT}"
set_env "DB_DATABASE" "${DB_NAME}"
set_env "DB_USERNAME" "${DB_USER}"

if [[ -n "$DB_PASS" ]]; then
  set_env "DB_PASSWORD" "\"${DB_PASS}\""
fi

############################################
# COMPOSER INSTALL
############################################
echo "[4/7] Installing composer dependencies..."
cd "${APP_DIR}"
run_as "${DEPLOY_USER}" composer install --no-dev --prefer-dist --optimize-autoloader

############################################
# DATABASE TEST
############################################
if [[ -n "$DB_NAME" && -n "$DB_USER" ]]; then
  echo "[5/7] Testing database connection..."
  if mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" -P"$DB_PORT" -e "USE $DB_NAME;" >/dev/null 2>&1; then
    echo "Database connection OK"
  else
    echo "WARNING: Database connection failed (check credentials)"
  fi
fi

############################################
# ARTISAN COMMANDS
############################################
echo "[6/7] Running artisan..."
run_as "${DEPLOY_USER}" php artisan key:generate --force

if [[ "$RUN_MIGRATIONS" == "1" ]]; then
  run_as "${DEPLOY_USER}" php artisan migrate --force || true
fi

if [[ "$CACHE_OPTIMIZE" == "1" ]]; then
  run_as "${DEPLOY_USER}" php artisan config:cache || true
  run_as "${DEPLOY_USER}" php artisan route:cache || true
  run_as "${DEPLOY_USER}" php artisan view:cache  || true
fi

############################################
# FINAL CHECK
############################################
echo "[7/7] Permission validation..."
if sudo -u "${APP_USER}" test -w "${APP_DIR}/storage"; then
  echo "OK: ${APP_USER} can write storage"
else
  echo "ERROR: ${APP_USER} cannot write storage"
fi

echo "✅ Installation completed successfully."