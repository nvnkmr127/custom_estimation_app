#!/bin/bash
set -e

echo "Deploying application..."

# Enter maintenance mode
(php artisan down) || true

# Update codebase
git pull origin main

# Install dependencies based on lock file
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Migrate database
php artisan migrate --force

# Note: If using queues, restart them
php artisan queue:restart

# Clear caches
php artisan optimize:clear

# Cache config and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install node dependencies and build assets
npm ci
npm run build

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
