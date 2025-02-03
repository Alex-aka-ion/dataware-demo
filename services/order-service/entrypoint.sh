#!/bin/sh

set -e

echo "Ожидание доступности базы данных..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  sleep 2
done

sleep 3

echo "База данных доступна! Накатываю миграции..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "База данных доступна! Накатываю миграции для тестов..."
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:update --force --env=test

echo "База данных доступна! Создаю фейковые товары..."
php bin/console doctrine:fixtures:load --no-interaction --env=dev

echo "Запускаю PHP-FPM..."
exec php-fpm