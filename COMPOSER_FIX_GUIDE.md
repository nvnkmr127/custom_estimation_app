# Composer Installation Error - Fix Guide

## Problem
Composer installation fails during deployment with error:
```
require(/vendor/composer/../livewire/livewire/src/helpers.php): Failed to open stream: No such file or directory
```

This happens when Composer tries to uninstall old Livewire packages but the autoloader still references deleted files.

## Root Cause
- Corrupted autoloader state during package upgrade
- Livewire package transition causing file reference conflicts
- Incomplete previous composer operations

## Solutions

### Solution 1: Use the Emergency Recovery Script (Recommended)

On your cPanel server, run:

```bash
cd /home/concept1/crmstag.concept2designs.in
./fix_composer.sh
```

This script will:
1. Clear composer cache
2. Remove vendor directory
3. Remove composer.lock
4. Clear bootstrap cache
5. Reinstall all dependencies cleanly

### Solution 2: Manual Recovery Steps

If the script doesn't work, manually execute:

```bash
# 1. Navigate to your project
cd /home/concept1/crmstag.concept2designs.in

# 2. Clear composer cache
composer clear-cache

# 3. Remove vendor and lock file
rm -rf vendor
rm -f composer.lock

# 4. Clear bootstrap cache
rm -rf bootstrap/cache/*.php

# 5. Reinstall dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 6. Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 7. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 8. Bring app back online (if in maintenance mode)
php artisan up
```

### Solution 3: Updated Deployment Script

The `cpanel_deploy.sh` script has been updated to automatically handle this issue. It will:
- Attempt normal composer install
- If it fails, automatically run recovery steps
- Retry installation with clean state

Next deployment will use this improved script automatically.

## Prevention

To prevent this in the future:

1. **Always commit composer.lock**: Ensures consistent dependency versions
2. **Use the updated deployment script**: Includes automatic recovery
3. **Monitor composer updates**: Review changes before deploying
4. **Keep backups**: Regular database and file backups

## Verification

After fixing, verify everything works:

```bash
# Check composer autoload
composer dump-autoload

# Check Laravel can boot
php artisan about

# Check Livewire is working
php artisan livewire:list

# Check site is accessible
curl -I https://crmstag.concept2designs.in
```

## Emergency Contacts

If issues persist:
1. Check PHP version: `php -v` (should be 8.2+)
2. Check disk space: `df -h`
3. Check error logs: `tail -f storage/logs/laravel.log`
4. Contact hosting support if server-level issues

## Files Modified

- `cpanel_deploy.sh` - Updated with automatic recovery
- `fix_composer.sh` - New emergency recovery script
- `COMPOSER_FIX_GUIDE.md` - This guide

## Next Steps

1. Upload `fix_composer.sh` to your cPanel server
2. Run the recovery script
3. Verify the application is working
4. Future deployments will use the improved script automatically
