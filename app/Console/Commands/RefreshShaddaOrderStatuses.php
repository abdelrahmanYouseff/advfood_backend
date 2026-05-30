<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShaddaShippingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshShaddaOrderStatuses extends Command
{
    protected $signature = 'orders:refresh-shadda-status';

    protected $description = 'Poll Shadda GetOrder for open orders and sync status into the database';

    public function handle(ShaddaShippingService $shadda): int
    {
        $orders = Order::query()
            ->where('shipping_provider', 'shadda')
            ->whereNotNull('dsp_order_id')
            ->where('payment_status', 'paid')
            ->whereNull('delivered_at')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('id')
            ->get(['id', 'order_number', 'dsp_order_id', 'status', 'shipping_status']);

        if ($orders->isEmpty()) {
            $this->info('No open Shadda orders to refresh.');

            return self::SUCCESS;
        }

        $this->info("Refreshing {$orders->count()} Shadda order(s)...");

        $synced = 0;
        $failed = 0;

        foreach ($orders as $order) {
            $dspOrderId = $order->dsp_order_id;

            try {
                $result = $shadda->getOrderStatus($dspOrderId);

                if ($result === null) {
                    $failed++;
                    $this->warn("Failed: {$order->order_number} ({$dspOrderId})");
                    Log::warning('Shadda status refresh returned null', [
                        'order_id' => $order->id,
                        'dsp_order_id' => $dspOrderId,
                    ]);
                    continue;
                }

                $synced++;
                $order->refresh();
                $this->line("Synced: {$order->order_number} → {$order->shipping_status}" . ($order->status ? " / {$order->status}" : ''));
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error {$order->order_number}: {$e->getMessage()}");
                Log::error('Shadda status refresh exception', [
                    'order_id' => $order->id,
                    'dsp_order_id' => $dspOrderId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $summary = "Done. synced={$synced} failed={$failed}";
        $this->info($summary);
        Log::info('orders:refresh-shadda-status finished', [
            'total' => $orders->count(),
            'synced' => $synced,
            'failed' => $failed,
        ]);

        return $failed > 0 && $synced === 0 ? self::FAILURE : self::SUCCESS;
    }
}
