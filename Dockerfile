FROM php:8.2-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev

# 2. Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Install PHP extensions ya MySQL
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. Enable Apache Rewrite Module (Muhimu kwa Laravel URLs)
RUN a2enmod rewrite

# 5. Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Set working directory
WORKDIR /var/www/html

# 7. Copy kodi za backend pekee
COPY . /var/www/html

# 8. Rekebisha ruhusa (Permissions)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Elekeza Apache kwenye folder la /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 10. Badilisha Port iwe 8080 kwa ajili ya Clever Cloud
ENV PORT 8080
EXPOSE 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]