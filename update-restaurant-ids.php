<?php

// Script to update restaurant IDs
// WARNING: This will update IDs in all related tables
// Run: php update-restaurant-ids.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "⚠️  تحذير: هذا السكريبت سيغير IDs المطاعم في جميع الجداول المرتبطة!\n";
echo "📋 القيم الحالية:\n";
echo str_repeat("=", 80) . "\n";

$currentRestaurants = [
    'Tant Bakiza' => Restaurant::where('name', 'Tant Bakiza')->first(),
    'Gather Us' => Restaurant::where('name', 'Gather Us')->first(),
    'Delawa' => Restaurant::where('name', 'Delawa')->first(),
];

foreach ($currentRestaurants as $name => $restaurant) {
    if ($restaurant) {
        echo "   {$name}: ID = {$restaurant->id}, shop_id = " . ($restaurant->shop_id ?? 'NULL') . "\n";
    } else {
        echo "   {$name}: غير موجود!\n";
    }
}

echo "\n📋 القيم الجديدة المطلوبة:\n";
echo str_repeat("=", 80) . "\n";
echo "   Tant Bakiza => ID = 117\n";
echo "   Gather Us => ID = 118\n";
echo "   Delawa => ID = 119\n";

echo "\n⚠️  الجداول التي سيتم تحديثها:\n";
echo "   - restaurants\n";
echo "   - orders (restaurant_id)\n";
echo "   - menu_items (restaurant_id)\n";
echo "   - invoices (restaurant_id)\n";
echo "   - online_customers (restaurant_id)\n";
echo "   - link_orders (restaurant_id)\n";

echo "\n❓ هل تريد المتابعة؟ (اكتب 'yes' للمتابعة): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirmation = trim($line);
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ تم الإلغاء.\n";
    exit(0);
}

echo "\n🔄 بدء التحديث...\n\n";

// Mapping: old_id => new_id
$idMappings = [
    5 => 117,              // Tant Bakiza
    821017372 => 118,      // Gather Us
    821017371 => 119,      // Delawa
];

// Disable foreign key checks temporarily
DB::statement('SET FOREIGN_KEY_CHECKS = 0');

try {
    // Update restaurants table
    echo "1️⃣  تحديث جدول restaurants...\n";
    foreach ($idMappings as $oldId => $newId) {
        // Check if new ID already exists
        $existing = Restaurant::find($newId);
        if ($existing && $existing->id != $oldId) {
            echo "   ⚠️  ID {$newId} موجود بالفعل للمطعم: {$existing->name}\n";
            echo "   ❌ لا يمكن المتابعة!\n";
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            exit(1);
        }
        
        DB::table('restaurants')->where('id', $oldId)->update(['id' => $newId]);
        echo "   ✅ تم تحديث ID من {$oldId} إلى {$newId}\n";
    }
    
    // Update related tables
    $relatedTables = [
        'orders' => 'restaurant_id',
        'menu_items' => 'restaurant_id',
        'invoices' => 'restaurant_id',
        'online_customers' => 'restaurant_id',
        'link_orders' => 'restaurant_id',
    ];
    
    foreach ($relatedTables as $table => $column) {
        if (Schema::hasTable($table)) {
            echo "\n2️⃣  تحديث جدول {$table}...\n";
            foreach ($idMappings as $oldId => $newId) {
                $count = DB::table($table)->where($column, $oldId)->count();
                if ($count > 0) {
                    DB::table($table)->where($column, $oldId)->update([$column => $newId]);
                    echo "   ✅ تم تحديث {$count} سجل من {$oldId} إلى {$newId}\n";
                } else {
                    echo "   ℹ️  لا توجد سجلات للـ ID {$oldId}\n";
                }
            }
        } else {
            echo "\n⚠️  جدول {$table} غير موجود، تم التخطي\n";
        }
    }
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n✅ تم التحديث بنجاح!\n\n";
    
    // Verify
    echo "🔍 التحقق من التحديثات:\n";
    echo str_repeat("=", 80) . "\n";
    $restaurants = Restaurant::whereIn('id', [117, 118, 119])->get(['id', 'name', 'shop_id']);
    foreach ($restaurants as $restaurant) {
        echo "   ✅ {$restaurant->name}: ID = {$restaurant->id}, shop_id = " . ($restaurant->shop_id ?? 'NULL') . "\n";
    }
    
} catch (\Exception $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    echo "\n❌ حدث خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

