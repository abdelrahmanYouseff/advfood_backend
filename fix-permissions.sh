#!/bin/bash

# Fix Laravel permissions on server
echo "🔧 Fixing Laravel permissions..."

# Set correct ownership
sudo chown -R forge:forge /home/forge/advfoodapp.clarastars.com

# Set correct permissions for directories
sudo find /home/forge/advfoodapp.clarastars.com -type d -exec chmod 755 {} \;

# Set correct permissions for files
sudo find /home/forge/advfoodapp.clarastars.com -type f -exec chmod 644 {} \;

# Make sure storage directories are writable
sudo chmod -R 775 /home/forge/advfoodapp.clarastars.com/storage
sudo chmod -R 775 /home/forge/advfoodapp.clarastars.com/bootstrap/cache

# Set proper ownership for storage and cache
sudo chown -R forge:forge /home/forge/advfoodapp.clarastars.com/storage
sudo chown -R forge:forge /home/forge/advfoodapp.clarastars.com/bootstrap/cache

# Clear all caches
cd /home/forge/advfoodapp.clarastars.com
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "✅ Permissions fixed successfully!"
echo "📋 Test the website now: https://advfoodapp.clarastars.com"
