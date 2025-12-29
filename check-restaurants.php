<?php

// Script to check current restaurant values
// Run: php check-restaurants.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Restaurant;

echo "🔍 التحقق من القيم الحالية للمطاعم...\n\n";

$restaurants = Restaurant::all(['id', 'name', 'shop_id', 'is_active']);

if ($restaurants->isEmpty()) {
    echo "❌ لا توجد مطاعم في قاعدة البيانات!\n";
    exit(1);
}

echo "📋 القيم الحالية للمطاعم:\n";
echo str_repeat("=", 80) . "\n";
printf("%-5s | %-30s | %-15s | %-10s\n", "ID", "اسم المطعم", "shop_id", "نشط");
echo str_repeat("-", 80) . "\n";

foreach ($restaurants as $restaurant) {
    $shopId = $restaurant->shop_id ?? 'NULL';
    $isActive = $restaurant->is_active ? 'نعم' : 'لا';
    printf("%-5s | %-30s | %-15s | %-10s\n",
        $restaurant->id,
        $restaurant->name,
        $shopId,
        $isActive
    );
}

echo str_repeat("=", 80) . "\n\n";

// Check for specific restaurants
$targetRestaurants = ['Tant Bakiza', 'Gather Us', 'Delawa'];
echo "🔍 البحث عن المطاعم المحددة:\n";
echo str_repeat("-", 80) . "\n";

foreach ($targetRestaurants as $name) {
    $restaurant = Restaurant::where('name', $name)->first();
    if ($restaurant) {
        echo "✅ {$name}:\n";
        echo "   - ID: {$restaurant->id}\n";
        echo "   - shop_id: " . ($restaurant->shop_id ?? 'NULL') . "\n";
        echo "   - نشط: " . ($restaurant->is_active ? 'نعم' : 'لا') . "\n";
    } else {
        echo "❌ {$name}: غير موجود في قاعدة البيانات\n";
    }
    echo "\n";
}

echo "\n";

