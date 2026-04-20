# Gunakan image PHP 8.4 dengan Apache
FROM php:8.4-apache

# Instal dependensi sistem Linux untuk GD, ZIP, dan ekstensi lainnya
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo_mysql bcmath

# Aktifkan mod_rewrite untuk Laravel
RUN a2enmod rewrite

# Ganti DocumentRoot Apache ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Composer dari image resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy semua file proyek
COPY . .

# Instal dependensi PHP (tanpa dev untuk produksi)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Instal dependensi Node dan build assets (Vite)
RUN npm install && npm run build

# Atur izin folder storage dan bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Port yang digunakan Railway
EXPOSE 80

# Command untuk menjalankan Apache dan setup Laravel
CMD php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground