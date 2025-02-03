FROM php:8.3-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Установка Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Копируем конфиг Xdebug в контейнер
COPY ../../docker/php/conf.d/order-service-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Установка Composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем проект внутрь контейнера
WORKDIR /var/www
#COPY . /var/www

# Устанавливаем зависимости (Composer)
#RUN composer install --no-interaction --optimize-autoloader --ignore-platform-reqs

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Копируем entrypoint
COPY ./services/order-service/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Используем entrypoint
ENTRYPOINT ["/entrypoint.sh"]