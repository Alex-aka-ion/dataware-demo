#!/bin/sh

set -e

echo "Ожидание доступности базы данных..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  sleep 2
done

echo "База данных доступна! Накатываю миграции..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "База данных доступна! Накатываю миграции для тестов..."
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --force --env=test

echo "Запускаю PHP-FPM..."
exec php-fpm