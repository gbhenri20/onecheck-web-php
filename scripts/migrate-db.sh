#!/usr/bin/env bash
# Aplica o schema PostgreSQL (idempotente: CREATE IF NOT EXISTS).
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/html}"
cd "$APP_ROOT"
MIGRATION="${MIGRATION_FILE:-database/migrations/001_schema_postgres.sql}"

if [ -z "${DATABASE_URL:-}" ]; then
  echo "[migrate] DATABASE_URL não definida — migração ignorada."
  exit 0
fi

if [ ! -f "$MIGRATION" ]; then
  echo "[migrate] Arquivo não encontrado: $MIGRATION"
  exit 1
fi

echo "[migrate] Aplicando $MIGRATION ..."
psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f "$MIGRATION"
echo "[migrate] Schema OK."
