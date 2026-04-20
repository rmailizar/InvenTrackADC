FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan storage:link || true
RUN chmod -R 775 storage bootstrap/cache public/storage

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT