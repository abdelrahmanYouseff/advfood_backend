<?php

// Script to send orders to shipping company
// Run: php send-orders-to-shipping.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Services\ShippingServiceFactory;

echo "🚚 إرسال الطلبات لشركة الشحن...\n\n";

// Get orders that need to be sent (paid but no dsp_order_id)
$orders = Order::where('payment_status', 'paid')
    ->whereNull('dsp_order_id')
    ->whereNotNull('shop_id')
    ->whereIn('order_number', ['ORD-20251229-FD1130', 'ORD-20251229-C76369', 'ORD-20251229-F10FF3'])
    ->get();

if ($orders->isEmpty()) {
    echo "❌ لا توجد طلبات تحتاج إرسال لشركة الشحن\n";
    exit(0);
}

echo "📋 عدد الطلبات المراد إرسالها: " . $orders->count() . "\n\n";

foreach ($orders as $order) {
    echo "📦 معالجة الطلب: {$order->order_number}...\n";
    echo "   المطعم: {$order->restaurant->name}\n";
    echo "   shop_id: {$order->shop_id}\n";
    echo "   shipping_provider: " . ($order->shipping_provider ?? 'NULL') . "\n";
    
    try {
        // Get shipping provider
        $provider = $order->shipping_provider ?? \App\Models\AppSetting::get('default_shipping_provider', 'shadda');
        
        echo "   استخدام شركة الشحن: {$provider}\n";
        
        // Get shipping service
        $shippingService = ShippingServiceFactory::getService($provider);
        
        // Send order to shipping
        $shippingResult = $shippingService->createOrder($order);
        
        if ($shippingResult && isset($shippingResult['dsp_order_id'])) {
            // Update order with shipping information
            $order->dsp_order_id = $shippingResult['dsp_order_id'];
            $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
            $order->shipping_provider = $shippingResult['shipping_provider'] ?? $provider;
            $order->save();
            
            echo "   ✅ تم إرسال الطلب بنجاح!\n";
            echo "   📝 dsp_order_id: {$order->dsp_order_id}\n";
            echo "   📊 shipping_status: {$order->shipping_status}\n";
        } else {
            echo "   ❌ فشل إرسال الطلب لشركة الشحن\n";
            echo "   ⚠️  shipping_result: " . json_encode($shippingResult) . "\n";
            echo "   💡 تحقق من logs للتفاصيل\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ أثناء إرسال الطلب: " . $e->getMessage() . "\n";
        echo "   📄 Trace: " . substr($e->getTraceAsString(), 0, 200) . "...\n";
    }
    
    echo "\n";
}

echo "✅ تم الانتهاء من معالجة جميع الطلبات!\n\n";

// Show summary
echo "📋 ملخص الطلبات:\n";
echo str_repeat("=", 80) . "\n";
printf("%-20s | %-20s | %-15s | %-20s\n", "رقم الطلب", "المطعم", "dsp_order_id", "حالة الشحن");
echo str_repeat("-", 80) . "\n";

$updatedOrders = Order::whereIn('order_number', ['ORD-20251229-FD1130', 'ORD-20251229-C76369', 'ORD-20251229-F10FF3'])->get();
foreach ($updatedOrders as $order) {
    printf("%-20s | %-20s | %-15s | %-20s\n",
        $order->order_number,
        $order->restaurant->name,
        $order->dsp_order_id ?? 'NULL',
        $order->shipping_status ?? 'NULL'
    );
}

echo str_repeat("=", 80) . "\n\n";

