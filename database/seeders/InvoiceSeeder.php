<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create required data
        $user = User::first();
        $restaurant = Restaurant::first();
        
        if (!$user || !$restaurant) {
            $this->command->error('يجب وجود مستخدم ومطعم واحد على الأقل في قاعدة البيانات');
            return;
        }

        // Get first paid order or create a test order
        $order = Order::where('payment_status', 'paid')->first();
        
        if (!$order) {
            // Create a test order if no paid orders exist
            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-TEST',
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'shop_id' => $restaurant->shop_id ?? '11183',
                'status' => 'confirmed',
                'shipping_status' => 'Confirmed',
                'subtotal' => 100.00,
                'delivery_fee' => 15.00,
                'tax' => 5.00,
                'total' => 120.00,
                'delivery_address' => 'عنوان تجريبي',
                'delivery_phone' => '0501234567',
                'delivery_name' => $user->name,
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'source' => 'internal',
            ]);
        }

        // Create invoice (will generate unique invoice_number automatically)
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'restaurant_id' => $order->restaurant_id,
            'subtotal' => $order->subtotal ?? 100.00,
            'delivery_fee' => $order->delivery_fee ?? 15.00,
            'tax' => $order->tax ?? 5.00,
            'total' => $order->total ?? 120.00,
            'status' => 'paid',
            'due_date' => now()->addDays(30),
            'paid_at' => now(),
            'order_reference' => $order->payment_order_reference ?? $order->order_number,
            'notes' => 'فاتورة تجريبية للطلب: ' . $order->order_number,
        ]);

        $this->command->info('✅ تم إنشاء الفاتورة بنجاح!');
        $this->command->info('رقم الفاتورة: ' . $invoice->invoice_number);
        $this->command->info('المبلغ: ' . number_format($invoice->total, 2) . ' ريال');
        $this->command->info('الحالة: ' . $invoice->status);
    }
}
