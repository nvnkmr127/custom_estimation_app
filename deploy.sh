#!/bin/bash
set -e

# Logging function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting deployment..."

# Check for required tools
command -v php >/dev/null 2>&1 || { log "PHP is required but not installed. Aborting."; exit 1; }
command -v composer >/dev/null 2>&1 || { log "Composer is required but not installed. Aborting."; exit 1; }
command -v npm >/dev/null 2>&1 || { log "NPM is required but not installed. Aborting."; exit 1; }
command -v git >/dev/null 2>&1 || { log "Git is required but not installed. Aborting."; exit 1; }

# Enter maintenance mode
log "Entering maintenance mode..."
(php artisan down) || log "Application is already in maintenance mode or artisan is failed."

# Update codebase
log "Updating codebase from git..."
git pull origin main

# Install dependencies based on lock file
log "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Migrate database
log "Running database migrations..."
php artisan migrate --force

# Note: If using queues, restart them
log "Restarting queues..."
php artisan queue:restart || log "Queue restart failed (maybe no queue worker running)."

# Clear and optimize caches
log "Clearing and optimizing caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install node dependencies and build assets
log "Installing and building frontend assets..."
npm ci
npm run build

# Exit maintenance mode
log "Exiting maintenance mode..."
php artisan up

log "Deployment finished successfully!"
