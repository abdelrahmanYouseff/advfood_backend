<?php

// Script to check shop_id values on server
// Upload to server and run: php check-shop-ids-server.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Restaurant;

echo "🔍 Checking shop_id values in restaurants table (SERVER)...\n\n";

$restaurants = Restaurant::all(['id', 'name', 'shop_id']);

if ($restaurants->isEmpty()) {
    echo "❌ No restaurants found!\n";
    exit(1);
}

echo "📋 Restaurants and their shop_id values:\n";
echo str_repeat("=", 60) . "\n";
printf("%-15s | %-30s | %-15s | %-10s\n", "ID", "Name", "shop_id", "Type");
echo str_repeat("-", 60) . "\n";

foreach ($restaurants as $restaurant) {
    $shopId = $restaurant->shop_id ?? 'NULL';
    $shopIdType = gettype($shopId);
    $status = empty($restaurant->shop_id) ? '⚠️ MISSING' : '✅';
    printf("%-15s | %-30s | %-15s | %-10s %s\n",
        $restaurant->id,
        $restaurant->name,
        $shopId,
        $shopIdType,
        $status
    );
}

echo str_repeat("=", 60) . "\n\n";

// Check for missing shop_id
$missingShopId = $restaurants->filter(function($r) {
    return empty($r->shop_id);
});

if ($missingShopId->count() > 0) {
    echo "⚠️  WARNING: Found restaurants with missing shop_id:\n";
    foreach ($missingShopId as $restaurant) {
        echo "   - {$restaurant->name} (ID: {$restaurant->id})\n";
    }
    echo "\n";
}

// Expected shop_id values
$expectedShopIds = [
    'Delawa' => '11183',
    'Gather Us' => '11185',
    'Tant Bakiza' => '11184',
];

echo "📋 Expected shop_id values:\n";
foreach ($expectedShopIds as $name => $expectedId) {
    $restaurant = $restaurants->firstWhere('name', $name);
    if ($restaurant) {
        $currentId = $restaurant->shop_id ?? 'NULL';
        $currentIdString = (string) $currentId;
        $match = ($currentIdString === $expectedId) ? '✅' : '❌';
        echo "   {$match} {$name}: Expected '{$expectedId}', Found '{$currentId}' (type: " . gettype($currentId) . ")\n";

        if ($currentIdString !== $expectedId) {
            echo "      ⚠️  MISMATCH! Please update this restaurant.\n";
        }
    } else {
        echo "   ⚠️  {$name}: Not found in database\n";
    }
}

echo "\n";
echo "💡 If shop_id values are incorrect, update them using:\n";
echo "   php artisan tinker\n";
echo "   >>> Restaurant::where('name', 'Delawa')->update(['shop_id' => '11183']);\n";
echo "   >>> Restaurant::where('name', 'Gather Us')->update(['shop_id' => '11185']);\n";
echo "   >>> Restaurant::where('name', 'Tant Bakiza')->update(['shop_id' => '11184']);\n";
echo "\n";

