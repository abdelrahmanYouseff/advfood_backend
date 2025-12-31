<?php
// Script to create a test order for Gather Us restaurant
// Run: php create-gatherus-test-order.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

echo "🛒 إنشاء طلب تجريبي لمطعم Gather Us...\n\n";

// Get Gather Us restaurant
$restaurant = Restaurant::where('name', 'Gather Us')->first();
if (!$restaurant) {
    echo "❌ مطعم Gather Us غير موجود!\n";
    exit(1);
}

echo "✅ المطعم: {$restaurant->name} (ID: {$restaurant->id}, shop_id: " . ($restaurant->shop_id ?? 'NULL') . ")\n\n";

// Get first user
$user = User::first();
if (!$user) {
    echo "❌ لا يوجد مستخدمين في قاعدة البيانات!\n";
    exit(1);
}

echo "✅ المستخدم: {$user->name} (ID: {$user->id})\n\n";

// Generate unique order number
function generateUniqueOrderNumber() {
    $date = now()->format('Ymd');
    $uniqueSuffix = strtoupper(substr(uniqid('', true), -6));
    return 'TEST-GU-' . $date . '-' . $uniqueSuffix;
}

$orderNumber = generateUniqueOrderNumber();

// Check uniqueness
while (Order::where('order_number', $orderNumber)->exists()) {
    $orderNumber = generateUniqueOrderNumber();
}

// Riyadh coordinates
$customerLatitude = 24.7136 + (rand(-50, 50) / 1000);
$customerLongitude = 46.6753 + (rand(-50, 50) / 1000);

// Calculate totals
$subtotal = round(rand(50, 200) + (rand(0, 99) / 100), 2);
$deliveryFee = round(15 + (rand(0, 10)), 2);
$tax = round($subtotal * 0.15, 2);
$total = round($subtotal + $deliveryFee + $tax, 2);

// Get shop_id from restaurant
$shopId = $restaurant->shop_id ?? '210';

echo "📝 بيانات الطلب:\n";
echo "   رقم الطلب: {$orderNumber}\n";
echo "   shop_id: {$shopId}\n";
echo "   الإجمالي: {$total} ر.س\n";
echo "   الموقع: {$customerLatitude}, {$customerLongitude}\n\n";

// Create order
echo "🚀 إنشاء الطلب...\n";
$order = Order::create([
    'order_number' => $orderNumber,
    'user_id' => $user->id,
    'restaurant_id' => $restaurant->id,
    'shop_id' => $shopId,
    'status' => 'confirmed',
    'shipping_status' => 'New Order',
    'shipping_provider' => 'shadda', // Force Shadda
    'subtotal' => $subtotal,
    'delivery_fee' => $deliveryFee,
    'tax' => $tax,
    'total' => $total,
    'delivery_address' => "شارع الملك فهد، الرياض، المملكة العربية السعودية",
    'delivery_phone' => '05' . rand(10000000, 99999999),
    'delivery_name' => 'عميل تجريبي - Gather Us',
    'customer_latitude' => $customerLatitude,
    'customer_longitude' => $customerLongitude,
    'special_instructions' => 'طلب تجريبي - Gather Us Test Order',
    'payment_method' => 'card',
    'payment_status' => 'paid',
    'source' => 'internal',
    'sound' => true,
]);

echo "✅ تم إنشاء الطلب: {$orderNumber} (ID: {$order->id})\n\n";

// Get a menu item from the restaurant if available
$menuItem = $restaurant->menuItems()->first();

if ($menuItem) {
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
    
    echo "✅ تم إضافة عنصر: {$menuItem->name} (الكمية: {$quantity})\n\n";
} else {
    echo "⚠️  لا توجد عناصر قائمة للمطعم، سيتم إنشاء عنصر تجريبي...\n";
    
    $anyMenuItem = \App\Models\MenuItem::first();
    
    if ($anyMenuItem) {
        $quantity = rand(1, 3);
        $itemPrice = round($subtotal / $quantity, 2);
        $itemSubtotal = round($itemPrice * $quantity, 2);
        
        $order->orderItems()->create([
            'menu_item_id' => $anyMenuItem->id,
            'item_name' => 'عنصر تجريبي - Gather Us',
            'quantity' => $quantity,
            'price' => $itemPrice,
            'subtotal' => $itemSubtotal,
        ]);
        
        echo "✅ تم إضافة عنصر تجريبي\n\n";
    }
}

// Refresh order to get dsp_order_id if it was set by boot method
$order->refresh();

echo "📋 ملخص الطلب:\n";
echo str_repeat("=", 80) . "\n";
echo "رقم الطلب: {$order->order_number}\n";
echo "المطعم: {$order->restaurant->name}\n";
echo "shop_id: {$order->shop_id}\n";
echo "shipping_provider: {$order->shipping_provider}\n";
echo "shipping_status: {$order->shipping_status}\n";
echo "dsp_order_id: " . ($order->dsp_order_id ?? 'NULL (لم يُرسل بعد)') . "\n";
echo "الإجمالي: {$order->total} ر.س\n";
echo str_repeat("=", 80) . "\n\n";

if ($order->dsp_order_id) {
    echo "✅ تم إرسال الطلب بنجاح لشركة الشحن!\n";
    echo "   dsp_order_id: {$order->dsp_order_id}\n";
} else {
    echo "❌ لم يتم إرسال الطلب لشركة الشحن!\n";
    echo "   يرجى فحص الـ logs لمعرفة السبب.\n";
    echo "   يمكنك فحص الـ logs في: https://advfoodapp.clarastars.com/logs\n";
}

echo "\n✅ تم الانتهاء!\n";

