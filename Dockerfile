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
    nginx \
    supervisor

# Installer les extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier tout le code
COPY . .

# Installer les dépendances PHP et Node (en production)
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Copier la configuration Nginx (on va la créer)
COPY nginx.conf /etc/nginx/sites-available/default

# Copier la configuration Supervisord (pour lancer plusieurs processus)
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exposer le port 8080 (Render attend ce port pour le web)
EXPOSE 8080

# Démarrer supervisord (qui lancera Nginx, PHP-FPM, Reverb, Queue)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]