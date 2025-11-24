<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateMissingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:create-missing 
                            {--dry-run : فقط اعرض عدد الطلبات بدون إنشاء فواتير فعلية}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إنشاء فواتير لكل الطلبات المدفوعة التي لا يوجد لها فاتورة';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔎 البحث عن الطلبات المدفوعة بدون فواتير ...');

        // جميع الطلبات المدفوعة التي لا يوجد لها سجل في جدول الفواتير
        $ordersQuery = Order::where('payment_status', 'paid')
            ->whereNotIn('id', function ($q) {
                $q->select('order_id')->from('invoices');
            });

        $count = $ordersQuery->count();

        if ($count === 0) {
            $this->info('✅ لا توجد طلبات مدفوعة بدون فواتير. كل شيء مرتب.');
            return Command::SUCCESS;
        }

        $this->warn("📌 تم العثور على {$count} طلب(ات) مدفوعة بدون فواتير.");

        if ($this->option('dry-run')) {
            $this->info('وضع التجربة (dry-run): لم يتم إنشاء أي فواتير، فقط عرض العدد.');
            return Command::SUCCESS;
        }

        $this->info('🚀 بدء إنشاء الفواتير المفقودة ...');

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $ordersQuery->chunkById(50, function ($orders) use ($bar) {
            foreach ($orders as $order) {
                try {
                    $invoice = $order->createInvoice();

                    if ($invoice) {
                        Log::info('Missing invoice created via console command', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                        ]);
                    } else {
                        Log::warning('createInvoice() returned null for order when running invoices:create-missing', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Error creating missing invoice for order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'message' => $e->getMessage(),
                    ]);

                    $this->error(PHP_EOL . "❌ خطأ في إنشاء فاتورة للطلب رقم {$order->order_number}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ تم إنشاء جميع الفواتير المفقودة (قدر الإمكان).');
        $this->info('يمكنك الآن فتح صفحة الفواتير للتأكد: /invoices');

        return Command::SUCCESS;
    }
}


