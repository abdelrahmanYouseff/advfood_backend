<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShippingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResendOrderToShipping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:resend-shipping {order_id? : The order ID to resend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend order to shipping company API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');

        if ($orderId) {
            // Resend specific order
            $this->resendOrder($orderId);
        } else {
            // Resend all paid orders without dsp_order_id
            $orders = Order::with('restaurant')
                ->where('payment_status', 'paid')
                ->whereNull('dsp_order_id')
                ->get();

            if ($orders->isEmpty()) {
                $this->info('✅ No orders to resend. All paid orders have been sent to shipping company.');
                return 0;
            }

            $this->info("Found {$orders->count()} orders to resend...");

            foreach ($orders as $order) {
                $this->resendOrder($order->id);
            }
        }

        return 0;
    }

    protected function resendOrder($orderId)
    {
        $order = Order::with('restaurant')->find($orderId);

        if (!$order) {
            $this->error("❌ Order #{$orderId} not found");
            return;
        }

        if ($order->payment_status !== 'paid') {
            $this->warn("⚠️  Order #{$orderId} payment status is '{$order->payment_status}', skipping...");
            return;
        }

        if ($order->dsp_order_id) {
            $this->warn("⚠️  Order #{$orderId} already sent to shipping (DSP ID: {$order->dsp_order_id})");
            return;
        }

        // Check if restaurant has shop_id
        if (!$order->restaurant || !$order->restaurant->shop_id) {
            $this->error("❌ Order #{$orderId}: Restaurant has no shop_id configured");
            $this->warn("   Please set shop_id for restaurant: {$order->restaurant->name}");
            return;
        }

        // Update order shop_id from restaurant
        $order->shop_id = $order->restaurant->shop_id;
        $order->save();

        $this->info("📦 Sending Order #{$orderId} ({$order->order_number}) to shipping company...");

        try {
            $shippingService = new ShippingService();
            $result = $shippingService->createOrder($order);

            if ($result && isset($result['dsp_order_id'])) {
                $order->dsp_order_id = $result['dsp_order_id'];
                $order->shipping_status = $result['shipping_status'] ?? 'New Order';
                $order->save();

                $this->info("   ✅ Success! DSP Order ID: {$result['dsp_order_id']}");
                $this->info("   📍 Customer: {$order->delivery_name}");
                $this->info("   📞 Phone: {$order->delivery_phone}");
                $this->info("   💰 Total: {$order->total} SAR");

                Log::info('Order resent to shipping successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'dsp_order_id' => $result['dsp_order_id'],
                ]);
            } else {
                $this->error("   ❌ Failed to send order to shipping");
                Log::error('Failed to resend order to shipping', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            Log::error('Exception while resending order to shipping', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
