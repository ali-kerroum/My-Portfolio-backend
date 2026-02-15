#!/bin/bash
set -e

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Copy .env if not exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate app key if not set
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed database (only if users table is empty)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

# Cache config & routes for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink (only for local storage)
php artisan storage:link 2>/dev/null || true
