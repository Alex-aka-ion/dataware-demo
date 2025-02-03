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
COPY ../../docker/php/conf.d/api-gateway-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Копируем проект внутрь контейнера
WORKDIR /var/www
#COPY . /var/www

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Запуск PHP-FPM
CMD ["php-fpm"]