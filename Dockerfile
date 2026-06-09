FROM php:8.3-cli

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    curl git unzip libzip-dev libpng-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd xml \
    && apt-get clean

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/backend

# Copia tudo
COPY backend/ .

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Build do frontend (Vite → public/build/)
RUN npm install && npm run build

# Cache do Laravel
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 8080

CMD php artisan migrate --force && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=8080
