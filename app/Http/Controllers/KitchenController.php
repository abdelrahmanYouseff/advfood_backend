<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KitchenController extends Controller
{
    /**
     * Tablet-optimized kitchen display for active orders.
     */
    public function index()
    {
        $branch = Auth::guard('branches')->user();
        $isBranch = $branch !== null;
        $branchId = $branch?->id;

        $orderQuery = Order::with(['restaurant', 'orderItems'])
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('scheduled_for')
                    ->orWhere('scheduled_for', '<=', now());
            });

        if ($isBranch && $branchId) {
            $orderQuery->where('branch_id', $branchId);
        }

        $orders = $orderQuery->latest()->get();

        $openOrders = $orders->reject(function ($order) {
            $status = strtolower($order->status ?? '');
            $shippingStatus = strtolower($order->shipping_status ?? '');

            return in_array($status, ['delivered', 'cancelled'], true)
                || in_array($shippingStatus, ['delivered', 'cancelled'], true)
                || ! is_null($order->delivered_at);
        });

        $kitchenOrders = $openOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'website_order_code' => $order->website_order_code,
                'status' => $order->status,
                'shipping_status' => $order->shipping_status,
                'total' => (float) $order->total,
                'sound' => (bool) $order->sound,
                'created_at' => $order->created_at?->toIso8601String(),
                'special_instructions' => $order->special_instructions,
                'delivery_name' => $order->delivery_name,
                'restaurant' => [
                    'name' => $order->restaurant?->name ?? '—',
                ],
                'order_items' => $order->orderItems->map(fn ($item) => [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'special_instructions' => $item->special_instructions,
                ])->values(),
            ];
        })->values();

        return Inertia::render('Kitchen', [
            'orders' => $kitchenOrders,
        ]);
    }
}
