FROM php:8.2-fpm

# Installe les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql

# Installe Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
