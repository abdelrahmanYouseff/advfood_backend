<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSyncService
{
    /**
     * Insert or update a scraped order in zyda_orders table.
     */
    public function saveScrapedOrder(array $orderData): bool
    {
        if (empty($orderData['phone'])) {
            return false;
        }

        $payload = [
            'name' => $orderData['name'] ?? null,
            'address' => $orderData['address'] ?? null,
            'location' => $orderData['location'] ?? null,
            'total_amount' => isset($orderData['total_amount']) ? (float) $orderData['total_amount'] : 0,
            'items' => isset($orderData['items']) ? json_encode($orderData['items']) : json_encode([]),
            'updated_at' => Carbon::now(),
        ];

        if (!DB::table('zyda_orders')->where('phone', $orderData['phone'])->exists()) {
            $payload['phone'] = $orderData['phone'];
            $payload['created_at'] = Carbon::now();
            return DB::table('zyda_orders')->insert($payload);
        }

        return (bool) DB::table('zyda_orders')
            ->where('phone', $orderData['phone'])
            ->update($payload);
    }
}

