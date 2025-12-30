#!/bin/bash
set -e

# --- Configuration ---
# Update these if your environment differs
PHP_PATH=$(which php 2>/dev/null || echo "php")
COMPOSER_PATH=$(which composer 2>/dev/null || echo "composer")
NPM_PATH=$(which npm 2>/dev/null || echo "npm")
BUILD_ASSETS=true # Set to false if you build locally and upload

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting cPanel Deployment..."

# 1. Maintenance Mode
log "Entering maintenance mode..."
$PHP_PATH artisan down || log "Application is already down."

# 2. Pull latest code
if [ -d ".git" ]; then
    log "Pulling latest changes from Git..."
    git pull origin main
else
    log "Warning: Not a Git repository. Skipping git pull. (Ignore if you uploaded files manually)"
fi

# 3. Install Dependencies
log "Installing PHP dependencies (Composer)..."
# Increase memory limit and disable scripts to avoid pre-uninstall errors on shared hosting
export COMPOSER_MEMORY_LIMIT=-1
$PHP_PATH "$COMPOSER_PATH" install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# 4. Database Migrations
log "Running database migrations..."
$PHP_PATH artisan migrate --force

# 5. Build Assets (if needed)
if [ "$BUILD_ASSETS" = true ]; then
    if command -v npm &> /dev/null; then
        log "Installing NPM dependencies and building assets..."
        $NPM_PATH install
        $NPM_PATH run build
    else
        log "NPM not found. Skipping asset build. (Ignore if assets are already built)"
    fi
fi

# 6. Optimization & Cache
log "Optimizing and clearing caches..."
$PHP_PATH artisan optimize:clear
$PHP_PATH artisan config:cache
$PHP_PATH artisan route:cache
$PHP_PATH artisan view:cache

# 7. Restart Queues (if applicable)
log "Restarting queue workers..."
$PHP_PATH artisan queue:restart || log "No queue workers to restart."

# 8. Exit Maintenance Mode
log "Exiting maintenance mode..."
$PHP_PATH artisan up

log "Deployment completed successfully!"
