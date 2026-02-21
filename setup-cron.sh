#!/usr/bin/env bash

# This script sets up the Laravel task scheduler cron job for this project.

PROJECT_DIR=$(pwd)
PHP_BIN=$(which php)

# The cron job command to run Laravel's schedule
CRON_JOB="* * * * * cd $PROJECT_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"

# Check if the cron job already exists
crontab -l | grep -q "artisan schedule:run"
if [ $? -eq 0 ]; then
    echo "The Laravel schedule cron job already exists for this user."
    exit 0
fi

# Add the cron job
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

if [ $? -eq 0 ]; then
    echo "Successfully added the following cron job:"
    echo "$CRON_JOB"
    echo "Your Laravel scheduled tasks will now run every minute."
else
    echo "Failed to add the cron job."
fi
