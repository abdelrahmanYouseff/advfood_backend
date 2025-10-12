#!/bin/bash

# 🚀 سكريبت نشر التحديثات على السيرفر
# استخدام: bash deploy-to-server.sh

echo "🚀 Starting deployment to server..."
echo ""

# المرحلة 1: على السيرفر
echo "📦 Step 1: Pull latest changes"
git pull origin main

echo ""
echo "🗄️  Step 2: Running migrations"
php artisan migrate --force

echo ""
echo "🔧 Step 3: Updating shop_id for all restaurants"
php artisan tinker --execute="\App\Models\Restaurant::query()->update(['shop_id' => '821017371']); echo 'Shop IDs updated to 821017371';"

echo ""
echo "🧹 Step 4: Clearing all caches (including views)"
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "⚡ Step 5: Rebuilding cache"
php artisan config:cache
php artisan route:cache

echo ""
echo "📤 Step 6: Resending paid orders to shipping"
php artisan order:resend-shipping

echo ""
echo "✅ Step 7: Verification"
echo "Checking restaurants shop_id:"
php artisan tinker --execute="echo json_encode(\App\Models\Restaurant::select('id','name','shop_id')->get()->toArray(), JSON_PRETTY_PRINT);"

echo ""
echo "Checking recent paid orders:"
php artisan tinker --execute="echo json_encode(\App\Models\Order::select('id','order_number','dsp_order_id','shop_id')->where('payment_status','paid')->orderBy('id','desc')->limit(3)->get()->toArray(), JSON_PRETTY_PRINT);"

echo ""
echo "🎉 Deployment completed!"
echo ""
echo "📋 Next steps:"
echo "1. Test a new order at: https://advfoodapp.clarastars.com/rest-link"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. Verify shipping: php artisan tinker --execute=\"Order::where('payment_status','paid')->whereNull('dsp_order_id')->count();\""
echo ""

