FROM php:8.3-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

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
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Используем entrypoint
ENTRYPOINT ["/entrypoint.sh"]