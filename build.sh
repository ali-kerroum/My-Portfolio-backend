#!/bin/bash
set -e

# Sanitize env vars - strip CR/LF/Tab characters that break Laravel
export APP_URL=$(printf '%s' "$APP_URL" | tr -d '\r\n\t')
export APP_KEY=$(printf '%s' "$APP_KEY" | tr -d '\r\n\t')
export DATABASE_URL=$(printf '%s' "$DATABASE_URL" | tr -d '\r\n\t')
export CLOUDINARY_URL=$(printf '%s' "$CLOUDINARY_URL" | tr -d '\r\n\t')
export ADMIN_EMAIL=$(printf '%s' "$ADMIN_EMAIL" | tr -d '\r\n\t')
export ADMIN_PASSWORD=$(printf '%s' "$ADMIN_PASSWORD" | tr -d '\r\n\t')

# Install PHP dependencies (skip scripts, run them after env is ready)
composer install --no-dev --optimize-autoloader --no-scripts

# Copy .env if not exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Only generate key if APP_KEY env var is not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run package discovery
php artisan package:discover --ansi

# Clear any old config cache so env() works during migrations/seeding
php artisan config:clear

# Run migrations
php artisan migrate --force

# Seed database only if empty (first deploy)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "First deploy: seeding database..."
    php artisan db:seed --force
else
    echo "Database already seeded ($USER_COUNT users found), updating admin credentials..."
    php artisan tinker --execute="
        \$email = env('ADMIN_EMAIL', 'admin@portfolio.com');
        \$pass = env('ADMIN_PASSWORD', 'changeme123');
        \$u = \App\Models\User::first();
        if (\$u) { \$u->update(['email' => \$email, 'password' => \Illuminate\Support\Facades\Hash::make(\$pass)]); }
        echo 'Admin updated: ' . \$email;
    " 2>/dev/null || true
fi

# Clean up duplicate records (one-time fix)
php artisan tinker --execute="
    foreach (['App\Models\Project', 'App\Models\Experience', 'App\Models\Service', 'App\Models\Skill', 'App\Models\ContactLink'] as \$model) {
        \$all = \$model::orderBy('id')->get();
        \$seen = [];
        foreach (\$all as \$item) {
            \$key = \$item->title ?? \$item->role ?? \$item->category ?? \$item->label ?? \$item->number ?? '';
            if (in_array(\$key, \$seen)) { \$item->delete(); }
            else { \$seen[] = \$key; }
        }
    }
    echo 'Duplicates cleaned.';
" 2>/dev/null || true

# NOW cache config & routes for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Debug: show key status
echo "APP_KEY is set: $([ -n \"$APP_KEY\" ] && echo 'YES' || echo 'NO')"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DATABASE_URL set: $([ -n \"$DATABASE_URL\" ] && echo 'YES' || echo 'NO')"

# Create storage symlink (only for local storage)
php artisan storage:link 2>/dev/null || true
