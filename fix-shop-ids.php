<?php

// Script to fix shop_id values on server
// Run: php fix-shop-ids.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Restaurant;

echo "🔧 Fixing shop_id values in restaurants table...\n\n";

$updates = [
    'Delawa' => '11183',
    'Gather Us' => '11185',
    'Tant Bakiza' => '11184',
];

foreach ($updates as $name => $correctShopId) {
    $restaurant = Restaurant::where('name', $name)->first();

    if ($restaurant) {
        $oldShopId = $restaurant->shop_id ?? 'NULL';
        $restaurant->shop_id = $correctShopId;
        $restaurant->save();

        echo "✅ Updated {$name}: {$oldShopId} → {$correctShopId}\n";
    } else {
        echo "❌ Restaurant '{$name}' not found!\n";
    }
}

echo "\n✅ All shop_id values have been updated!\n\n";

// Verify the updates
echo "🔍 Verifying updates...\n";
$restaurants = Restaurant::all(['id', 'name', 'shop_id']);
foreach ($restaurants as $restaurant) {
    $expected = $updates[$restaurant->name] ?? 'N/A';
    $actual = $restaurant->shop_id ?? 'NULL';
    $status = ($actual === $expected) ? '✅' : '❌';
    echo "   {$status} {$restaurant->name}: {$actual} (expected: {$expected})\n";
}

echo "\n";

