#!/bin/bash
set -e

# Schema automático quando DATABASE_URL vem do Render (Blueprint onecheck-db)
if [ -n "${DATABASE_URL:-}" ]; then
  /usr/local/bin/migrate-db.sh || echo "[entrypoint] Migração falhou ou banco ainda indisponível; tentando subir o app mesmo assim."
fi

PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
