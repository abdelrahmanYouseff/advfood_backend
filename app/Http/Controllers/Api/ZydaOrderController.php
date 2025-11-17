<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ZydaOrderController extends Controller
{
    public function __construct(protected OrderSyncService $orderSyncService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
        ]);

        $exists = DB::table('zyda_orders')
            ->where('phone', $validated['phone'])
            ->exists();

        $payload = [
            'name' => $validated['name'] ?? null,
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'location' => $validated['location'] ?? null,
            'total_amount' => $validated['total_amount'] ?? 0,
            'items' => $validated['items'],
        ];

        $result = $this->orderSyncService->saveScrapedOrder($payload);

        if (!$result) {
            throw ValidationException::withMessages([
                'phone' => ['Failed to save Zyda order.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'operation' => $exists ? 'updated' : 'created',
        ]);
    }
}

