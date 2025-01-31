#!/bin/sh

set -e

echo "Ожидание доступности базы данных..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  sleep 2
done

echo "База данных доступна! Накатываю миграции..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Запускаю PHP-FPM..."
exec php-fpm