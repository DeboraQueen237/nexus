FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    nginx

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code de l'application
WORKDIR /var/www/html
COPY . .

# Installer les dépendances PHP et Node
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Configurer Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Exposer le port 8080
EXPOSE 8080

# Script de démarrage (Laravel + Reverb + queue)
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=8080 & php artisan reverb:start --host=0.0.0.0 --port=6001 & php artisan queue:work --sleep=3 --tries=3"]