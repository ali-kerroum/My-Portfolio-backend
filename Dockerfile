FROM php:8.2-cli

# Custom PHP config for large file uploads
COPY docker/php.ini /usr/local/etc/php/conf.d/uploads.ini

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Set permissions
RUN chmod -R 775 storage bootstrap/cache
RUN chmod +x build.sh

# Expose port
EXPOSE 10000

# Start command
CMD ["sh", "-c", "./build.sh && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
