FROM php:8.3-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Установка Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Копируем конфиг Xdebug в контейнер
COPY api-gateway-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем проект внутрь контейнера
WORKDIR /var/www
COPY . /var/www

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Устанавливаем зависимости (Composer)
RUN composer install --no-interaction --optimize-autoloader

# Создание симлинка для Swagger UI
RUN ln -s /var/www/vendor/swagger-api/swagger-ui/dist /var/www/public/bundles/swagger-ui

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Запуск PHP-FPM
CMD ["php-fpm"]