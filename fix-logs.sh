#!/bin/bash

# Script to fix logging issues on server
# Usage: ./fix-logs.sh

echo "🔧 Fixing Laravel Logging Configuration..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

# Check LOG_CHANNEL
LOG_CHANNEL=$(grep "^LOG_CHANNEL=" .env | cut -d '=' -f2)
echo "Current LOG_CHANNEL: $LOG_CHANNEL"

if [ "$LOG_CHANNEL" = "null" ] || [ -z "$LOG_CHANNEL" ]; then
    echo "⚠️  LOG_CHANNEL is null or empty, setting to 'single'..."
    sed -i 's/^LOG_CHANNEL=.*/LOG_CHANNEL=single/' .env
    echo "✅ Updated LOG_CHANNEL to 'single'"
fi

# Set LOG_LEVEL if not set
if ! grep -q "^LOG_LEVEL=" .env; then
    echo "⚠️  LOG_LEVEL not set, adding LOG_LEVEL=debug..."
    echo "LOG_LEVEL=debug" >> .env
    echo "✅ Added LOG_LEVEL=debug"
fi

# Fix permissions
echo "🔧 Fixing permissions..."
chmod -R 775 storage/logs 2>/dev/null || true
chmod -R 775 storage/framework 2>/dev/null || true

# Create log file if it doesn't exist
if [ ! -f storage/logs/laravel.log ]; then
    echo "📝 Creating laravel.log file..."
    touch storage/logs/laravel.log
    chmod 664 storage/logs/laravel.log
fi

# Clear and cache config
echo "🔄 Clearing and caching config..."
php artisan config:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

# Test logging
echo "🧪 Testing logging..."
php artisan tinker --execute="\Illuminate\Support\Facades\Log::info('Test log - ' . date('Y-m-d H:i:s'));" 2>/dev/null || true

# Check if log was written
if [ -s storage/logs/laravel.log ]; then
    echo "✅ Logging is working! Last 3 lines:"
    tail -3 storage/logs/laravel.log
else
    echo "⚠️  Log file is still empty. Check permissions and LOG_CHANNEL in .env"
    echo "   Current LOG_CHANNEL: $(grep '^LOG_CHANNEL=' .env)"
fi

echo ""
echo "✅ Done! Check storage/logs/laravel.log"

