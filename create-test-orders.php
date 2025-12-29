<?php

// Script to create 3 test orders
// Run: php create-test-orders.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\OrderItem;

echo "🛒 إنشاء 3 طلبات تجريبية...\n\n";

// Get first user
$user = User::first();
if (!$user) {
    echo "❌ لا يوجد مستخدمين في قاعدة البيانات!\n";
    exit(1);
}

echo "✅ المستخدم: {$user->name} (ID: {$user->id})\n\n";

// Restaurant IDs
$restaurants = [
    'Delawa' => 119,
    'Tant Bakiza' => 117,
    'Gather Us' => 118,
];

// Generate completely unique order number (guaranteed unique using timestamp + random)
function generateUniqueOrderNumber() {
    $maxAttempts = 1000; // Maximum attempts to find a unique number
    $attempt = 0;
    
    do {
        $date = now()->format('Ymd');
        
        // Use microtime + random to ensure absolute uniqueness
        // Format: ORD-YYYYMMDD-XXXXXX where XXXXXX is a unique identifier
        $microtime = microtime(true);
        $random = rand(100000, 999999);
        $uniqueId = substr(str_replace('.', '', $microtime), -6) . $random;
        $uniqueId = substr($uniqueId, 0, 6); // Take first 6 characters
        
        // Alternative: Use uniqid with more entropy
        $uniqueSuffix = strtoupper(substr(uniqid('', true), -6));
        
        // Try multiple methods for maximum uniqueness
        $methods = [
            // Method 1: Timestamp + random (most unique)
            'ORD-' . $date . '-' . strtoupper(substr(md5($microtime . $random . uniqid()), 0, 6)),
            // Method 2: Date + uniqid
            'ORD-' . $date . '-' . $uniqueSuffix,
            // Method 3: Date + timestamp last 6 digits + random
            'ORD-' . $date . '-' . str_pad(substr((int)$microtime, -4), 4, '0', STR_PAD_LEFT) . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
            // Method 4: Date + hex of microtime
            'ORD-' . $date . '-' . strtoupper(substr(dechex((int)($microtime * 1000000)), -6)),
        ];
        
        foreach ($methods as $orderNumber) {
            // Check if this number already exists in local database
            $exists = Order::where('order_number', $orderNumber)->exists();
            
            if (!$exists) {
                // Double check: also verify it's not in shipping_orders table (if exists)
                if (\Illuminate\Support\Facades\Schema::hasTable('shipping_orders')) {
                    $existsInShipping = \Illuminate\Support\Facades\DB::table('shipping_orders')
                        ->where('dsp_order_id', $orderNumber)
                        ->exists();
                    
                    if ($existsInShipping) {
                        continue; // Try next method
                    }
                }
                
                return $orderNumber;
            }
        }
        
        // If all methods failed, use microtime with more precision
        $finalUnique = 'ORD-' . $date . '-' . strtoupper(substr(md5($microtime . uniqid() . rand(10000, 99999)), 0, 6));
        $exists = Order::where('order_number', $finalUnique)->exists();
        
        if (!$exists) {
            return $finalUnique;
        }
        
        $attempt++;
        
        // Small delay to ensure microtime changes
        usleep(1000); // 1 millisecond
        
    } while ($attempt < $maxAttempts);
    
    // Last resort: use full timestamp + random (guaranteed unique)
    $timestamp = (int)(microtime(true) * 1000000);
    $random = rand(1000, 9999);
    return 'ORD-' . $date . '-' . strtoupper(substr(dechex($timestamp . $random), -6));
}

// Riyadh coordinates (base)
$baseLat = 24.7136;
$baseLng = 46.6753;

$orders = [];

foreach ($restaurants as $restaurantName => $restaurantId) {
    $restaurant = Restaurant::find($restaurantId);
    
    if (!$restaurant) {
        echo "❌ المطعم '{$restaurantName}' (ID: {$restaurantId}) غير موجود!\n";
        continue;
    }
    
    echo "📝 إنشاء طلب لمطعم: {$restaurantName} (ID: {$restaurantId})...\n";
    
    // Generate random coordinates for variety
    $randomOffset = (rand(-50, 50) / 1000);
    $customerLatitude = $baseLat + $randomOffset;
    $customerLongitude = $baseLng + $randomOffset;
    
    // Generate completely unique order number (guaranteed unique)
    $orderNumber = generateUniqueOrderNumber();
    
    // Triple-check uniqueness before creating (local DB + shipping_orders table)
    $checkCount = 0;
    while (Order::where('order_number', $orderNumber)->exists() || 
           (\Illuminate\Support\Facades\Schema::hasTable('shipping_orders') && 
            \Illuminate\Support\Facades\DB::table('shipping_orders')->where('dsp_order_id', $orderNumber)->exists())) {
        $checkCount++;
        if ($checkCount > 10) {
            echo "   ⚠️  محاولة توليد رقم فريد (المحاولة {$checkCount})...\n";
        }
        $orderNumber = generateUniqueOrderNumber();
    }
    
    if ($checkCount > 0) {
        echo "   ℹ️  تم التحقق من التفرد ({$checkCount} محاولة)\n";
    }
    
    // Calculate totals
    $subtotal = round(rand(50, 200) + (rand(0, 99) / 100), 2);
    $deliveryFee = round(15 + (rand(0, 10)), 2);
    $tax = round($subtotal * 0.15, 2); // 15% tax
    $total = round($subtotal + $deliveryFee + $tax, 2);
    
    // Get shop_id from restaurant
    $shopId = $restaurant->shop_id ?? (string) $restaurantId;
    
    // Create order with confirmed status and paid payment to trigger automatic shipping
    $order = Order::create([
        'order_number' => $orderNumber,
        'user_id' => $user->id,
        'restaurant_id' => $restaurantId,
        'shop_id' => $shopId,
        'status' => 'confirmed', // Changed from 'pending' to 'confirmed'
        'shipping_status' => 'New Order',
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'tax' => $tax,
        'total' => $total,
        'delivery_address' => "شارع الملك فهد، الرياض، المملكة العربية السعودية",
        'delivery_phone' => '05' . rand(10000000, 99999999),
        'delivery_name' => 'عميل تجريبي - ' . $restaurantName,
        'customer_latitude' => $customerLatitude,
        'customer_longitude' => $customerLongitude,
        'special_instructions' => 'طلب تجريبي - ' . $restaurantName,
        'payment_method' => 'card', // Changed to 'card' for Shadda
        'payment_status' => 'paid', // Must be 'paid' to trigger automatic shipping
        'source' => 'internal',
        'sound' => true,
    ]);
    
    // Get a menu item from the restaurant if available
    $menuItem = $restaurant->menuItems()->first();
    
    if ($menuItem) {
        // Create order item with real menu item
        $quantity = rand(1, 3);
        $itemPrice = (float) $menuItem->price;
        $itemSubtotal = round($itemPrice * $quantity, 2);
        
        $order->orderItems()->create([
            'menu_item_id' => $menuItem->id,
            'item_name' => $menuItem->name,
            'quantity' => $quantity,
            'price' => $itemPrice,
            'subtotal' => $itemSubtotal,
        ]);
    } else {
        // If no menu items, create a simple test item with a dummy menu_item_id
        // We'll use 0 or create a minimal menu item first
        echo "   ⚠️  لا توجد عناصر قائمة للمطعم، سيتم إنشاء عنصر تجريبي...\n";
        
        // Try to find any menu item in the system
        $anyMenuItem = \App\Models\MenuItem::first();
        
        if ($anyMenuItem) {
            $quantity = rand(1, 3);
            $itemPrice = round($subtotal / $quantity, 2);
            $itemSubtotal = round($itemPrice * $quantity, 2);
            
            $order->orderItems()->create([
                'menu_item_id' => $anyMenuItem->id,
                'item_name' => 'عنصر تجريبي - ' . $restaurantName,
                'quantity' => $quantity,
                'price' => $itemPrice,
                'subtotal' => $itemSubtotal,
            ]);
        } else {
            echo "   ⚠️  لا توجد عناصر قائمة في النظام، سيتم إنشاء الطلب بدون عناصر\n";
        }
    }
    
    $orders[] = $order;
    
    echo "   ✅ تم إنشاء الطلب: {$orderNumber}\n";
    echo "   💰 الإجمالي: {$total} ر.س\n";
    echo "   📍 الموقع: {$customerLatitude}, {$customerLongitude}\n\n";
}

echo "✅ تم إنشاء " . count($orders) . " طلب بنجاح!\n\n";

echo "📋 ملخص الطلبات:\n";
echo str_repeat("=", 80) . "\n";
printf("%-20s | %-20s | %-15s | %-10s\n", "رقم الطلب", "المطعم", "الإجمالي", "الحالة");
echo str_repeat("-", 80) . "\n";

foreach ($orders as $order) {
    printf("%-20s | %-20s | %-15s | %-10s\n",
        $order->order_number,
        $order->restaurant->name,
        $order->total . ' ر.س',
        $order->status
    );
}

echo str_repeat("=", 80) . "\n\n";

