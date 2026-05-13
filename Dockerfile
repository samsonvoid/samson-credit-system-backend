FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd && \
    pecl install redis && \
    docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Create startup script that respects environment
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'echo "Checking startup mode..."' >> /start.sh && \
    echo 'if [ "$USE_LARAVEL_SERVER" = "true" ]; then' >> /start.sh && \
    echo '    echo "Starting Laravel server on port 8080 (Clever Cloud mode)"' >> /start.sh && \
    echo '    php artisan serve --host=0.0.0.0 --port=8080' >> /start.sh && \
    echo 'else' >> /start.sh && \
    echo '    echo "Starting PHP-FPM on port 9000 (Local/Dev mode)"' >> /start.sh && \
    echo '    php-fpm' >> /start.sh && \
    echo 'fi' >> /start.sh && \
    chmod +x /start.sh

# Expose both ports (8080 for Clever Cloud, 9000 for local)
EXPOSE 8080 9000

# Use startup script
CMD ["/start.sh"]