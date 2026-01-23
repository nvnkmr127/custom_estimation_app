#!/bin/bash

# Emergency Composer Recovery Script
# Use this when composer install fails due to autoloader corruption

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔧 Starting Composer Recovery...${NC}"
echo ""

# Step 1: Clear composer cache
echo "1. Clearing composer cache..."
composer clear-cache 2>/dev/null || true
echo -e "${GREEN}✓ Cache cleared${NC}"

# Step 2: Remove vendor directory
echo "2. Removing vendor directory..."
rm -rf vendor
echo -e "${GREEN}✓ Vendor directory removed${NC}"

# Step 3: Remove composer.lock
echo "3. Removing composer.lock..."
rm -f composer.lock
echo -e "${GREEN}✓ composer.lock removed${NC}"

# Step 4: Clear bootstrap cache
echo "4. Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
echo -e "${GREEN}✓ Bootstrap cache cleared${NC}"

# Step 5: Reinstall dependencies
echo "5. Reinstalling composer dependencies..."
echo ""
if composer install --no-dev --optimize-autoloader --no-interaction; then
    echo ""
    echo -e "${GREEN}✅ SUCCESS! Composer dependencies installed${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Run: php artisan config:clear"
    echo "  2. Run: php artisan cache:clear"
    echo "  3. Run: php artisan optimize"
    echo "  4. Run: php artisan up (if in maintenance mode)"
else
    echo ""
    echo -e "${RED}❌ FAILED! Composer install still failing${NC}"
    echo ""
    echo "Possible solutions:"
    echo "  1. Check PHP version: php -v (should be 8.2+)"
    echo "  2. Check composer version: composer --version"
    echo "  3. Check disk space: df -h"
    echo "  4. Check permissions: ls -la"
    echo "  5. Try manually: composer install -vvv (verbose mode)"
    exit 1
fi
