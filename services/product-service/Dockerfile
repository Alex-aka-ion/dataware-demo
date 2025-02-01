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

# Установка Composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем проект внутрь контейнера
WORKDIR /var/www
#COPY . /var/www

# Указываем PHP-FPM слушать 0.0.0.0:9000
#RUN sed -i 's/^listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Устанавливаем зависимости (Composer)
#RUN composer install --no-interaction --optimize-autoloader

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Копируем entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Используем entrypoint
ENTRYPOINT ["/entrypoint.sh"]