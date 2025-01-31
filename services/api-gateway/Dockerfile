FROM php:8.3-fpm

# Установка зависимостей
#RUN apt-get update && apt-get install -y \
#    git \
#    unzip \
#    libpq-dev \
#    libzip-dev \
#    curl \
#    procps \
#    iproute2 \
#    net-tools \
#    && docker-php-ext-install pdo pdo_pgsql zip

# Копируем проект внутрь контейнера
WORKDIR /var/www
#COPY . /var/www

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www

# Запуск PHP-FPM
CMD ["php-fpm"]