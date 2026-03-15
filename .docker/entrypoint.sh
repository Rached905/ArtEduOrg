#!/bin/bash
set -e

# Wait for MySQL (when using docker-compose)
if [ -n "${DATABASE_URL}" ]; then
  echo "Waiting for database..."
  for i in $(seq 1 30); do
    if php /var/www/html/.docker/wait-for-db.php 2>/dev/null; then
      echo "Database is ready."
      break
    fi
    if [ "$i" -eq 30 ]; then
      echo "Database did not become ready in time."
      exit 1
    fi
    sleep 2
  done
fi

# Create tables
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true

# Cache
php bin/console cache:clear 2>/dev/null || true
php bin/console cache:warmup 2>/dev/null || true

exec apache2-foreground
