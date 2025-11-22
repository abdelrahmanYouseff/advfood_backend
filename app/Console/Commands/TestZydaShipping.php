<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ZydaOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestZydaShipping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zyda:test-shipping {--check-recent : Check recent orders instead of all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and verify Zeada orders shipping integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║       ZEADA ORDERS - SHIPPING INTEGRATION TEST           ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // 1. Check Environment Configuration
        $this->checkEnvironment();
        $this->newLine();

        // 2. Check Database Tables
        $this->checkDatabaseTables();
        $this->newLine();

        // 3. Check Recent Orders
        if ($this->option('check-recent')) {
            $this->checkRecentOrders();
        } else {
            $this->checkAllOrders();
        }
        $this->newLine();

        // 4. Summary
        $this->showSummary();
    }

    protected function checkEnvironment()
    {
        $this->info('📋 STEP 1: Checking Environment Configuration');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $shippingUrl = config('services.shipping.url');
        $shippingKey = config('services.shipping.key');

        if (empty($shippingUrl)) {
            $this->error('  ❌ SHIPPING_API_URL not configured in .env');
            $this->warn('     Add: SHIPPING_API_URL=https://your-shipping-api.com');
        } else {
            $this->info("  ✅ Shipping API URL: {$shippingUrl}");
        }

        if (empty($shippingKey)) {
            $this->error('  ❌ SHIPPING_API_KEY not configured in .env');
            $this->warn('     Add: SHIPPING_API_KEY=your-api-key-here');
        } else {
            $keyLength = strlen($shippingKey);
            $keyPreview = substr($shippingKey, 0, 20) . '...';
            $this->info("  ✅ Shipping API Key: {$keyPreview} (length: {$keyLength})");
        }
    }

    protected function checkDatabaseTables()
    {
        $this->info('📋 STEP 2: Checking Database Tables');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Check zyda_orders
        $zydaOrdersCount = DB::table('zyda_orders')->count();
        $this->info("  📊 zyda_orders: {$zydaOrdersCount} total orders");

        $zydaPending = DB::table('zyda_orders')->whereNull('order_id')->count();
        $zydaReceived = DB::table('zyda_orders')->whereNotNull('order_id')->count();
        
        $this->line("     ├─ Pending (no order_id): {$zydaPending}");
        $this->line("     └─ Received (has order_id): {$zydaReceived}");

        // Check orders
        $ordersCount = DB::table('orders')->where('source', 'zyda')->count();
        $this->info("  📊 orders (source=zyda): {$ordersCount} orders");

        $ordersWithShipping = DB::table('orders')
            ->where('source', 'zyda')
            ->whereNotNull('dsp_order_id')
            ->count();
        $ordersWithoutShipping = $ordersCount - $ordersWithShipping;

        $this->line("     ├─ With dsp_order_id (sent to shipping): {$ordersWithShipping}");
        $this->line("     └─ Without dsp_order_id (NOT sent): {$ordersWithoutShipping}");

        if ($ordersWithoutShipping > 0) {
            $this->warn("     ⚠️ {$ordersWithoutShipping} orders were NOT sent to shipping company!");
        }

        // Check shipping_orders
        $shippingOrdersCount = DB::table('shipping_orders')
            ->join('orders', 'shipping_orders.order_id', '=', 'orders.id')
            ->where('orders.source', 'zyda')
            ->count();
        $this->info("  📊 shipping_orders (zyda orders): {$shippingOrdersCount} records");
    }

    protected function checkRecentOrders()
    {
        $this->info('📋 STEP 3: Checking Recent Zeada Orders (Last 10)');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $orders = Order::where('source', 'zyda')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('  ⚠️ No Zeada orders found in database');
            return;
        }

        $table = [];
        foreach ($orders as $order) {
            $hasShipping = !empty($order->dsp_order_id);
            $status = $hasShipping ? '✅ Sent' : '❌ NOT Sent';
            
            $table[] = [
                $order->id,
                $order->order_number,
                $order->dsp_order_id ?? 'NULL',
                $order->shipping_status ?? 'NULL',
                $order->shop_id ?? 'NULL',
                $status,
                $order->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table(
            ['ID', 'Order Number', 'DSP Order ID', 'Shipping Status', 'Shop ID', 'Status', 'Created At'],
            $table
        );
    }

    protected function checkAllOrders()
    {
        $this->info('📋 STEP 3: Checking All Zeada Orders Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $totalOrders = Order::where('source', 'zyda')->count();
        $ordersWithShipping = Order::where('source', 'zyda')->whereNotNull('dsp_order_id')->count();
        $ordersWithoutShipping = $totalOrders - $ordersWithShipping;

        $this->info("  📊 Total Zeada Orders: {$totalOrders}");
        $this->info("  ✅ Sent to Shipping: {$ordersWithShipping}");
        
        if ($ordersWithoutShipping > 0) {
            $this->error("  ❌ NOT Sent to Shipping: {$ordersWithoutShipping}");
        } else {
            $this->info("  ✅ All orders sent to shipping successfully!");
        }

        // Show breakdown by shipping_status
        $this->newLine();
        $this->info('  📊 Shipping Status Breakdown:');
        $statusBreakdown = Order::where('source', 'zyda')
            ->whereNotNull('dsp_order_id')
            ->selectRaw('shipping_status, COUNT(*) as count')
            ->groupBy('shipping_status')
            ->get();

        foreach ($statusBreakdown as $status) {
            $this->line("     ├─ {$status->shipping_status}: {$status->count}");
        }

        // Show recent 5 orders
        $this->newLine();
        $this->info('  📋 Last 5 Zeada Orders:');
        $recentOrders = Order::where('source', 'zyda')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $table = [];
        foreach ($recentOrders as $order) {
            $hasShipping = !empty($order->dsp_order_id);
            $status = $hasShipping ? '✅' : '❌';
            
            $table[] = [
                $order->id,
                $order->order_number,
                substr($order->dsp_order_id ?? 'NULL', 0, 20),
                $order->shipping_status ?? 'NULL',
                $status,
            ];
        }

        $this->table(
            ['ID', 'Order Number', 'DSP Order ID', 'Status', 'Sent'],
            $table
        );
    }

    protected function showSummary()
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║                      SUMMARY                              ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');

        $totalZydaOrders = Order::where('source', 'zyda')->count();
        $sentToShipping = Order::where('source', 'zyda')->whereNotNull('dsp_order_id')->count();
        $notSent = $totalZydaOrders - $sentToShipping;

        if ($totalZydaOrders === 0) {
            $this->warn('  ⚠️ No Zeada orders found in system yet');
            $this->line('     - Orders will appear after location is updated');
            $this->line('     - Check zyda_orders table for pending orders');
        } elseif ($notSent === 0) {
            $this->info('  ✅ ALL ZEADA ORDERS SENT TO SHIPPING SUCCESSFULLY!');
            $this->info("     Total: {$totalZydaOrders} orders");
        } else {
            $this->warn("  ⚠️ {$notSent} out of {$totalZydaOrders} orders NOT sent to shipping");
            $this->line('');
            $this->line('  📝 Troubleshooting Steps:');
            $this->line('     1. Check Laravel logs: tail -f storage/logs/laravel.log');
            $this->line('     2. Look for error messages with ❌ prefix');
            $this->line('     3. Common issues:');
            $this->line('        - Missing coordinates (invalid location URL)');
            $this->line('        - Invalid shop_id (not registered with shipping company)');
            $this->line('        - Missing required fields (name, phone, address)');
            $this->line('        - Shipping API credentials incorrect');
            $this->line('');
            $this->line('  📖 See ZYDA_SHIPPING_INTEGRATION.md for detailed troubleshooting');
        }

        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║              HOW TO VERIFY INTEGRATION                    ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->line('');
        $this->line('  1️⃣ Check if order has dsp_order_id:');
        $this->line('     SELECT id, order_number, dsp_order_id, shipping_status');
        $this->line('     FROM orders WHERE source = \'zyda\' ORDER BY created_at DESC;');
        $this->line('');
        $this->line('  2️⃣ Check Laravel logs for success/error messages:');
        $this->line('     tail -f storage/logs/laravel.log | grep "🚀\\|✅\\|❌"');
        $this->line('');
        $this->line('  3️⃣ Check shipping_orders table:');
        $this->line('     SELECT * FROM shipping_orders WHERE order_id = {your_order_id};');
        $this->line('');
        $this->line('  📖 Full documentation: ZYDA_SHIPPING_INTEGRATION.md');
    }
}

