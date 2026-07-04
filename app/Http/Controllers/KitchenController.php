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
        $publicImageUrl = static function (?string $path): ?string {
            if (! is_string($path) || trim($path) === '') {
                return null;
            }

            $path = trim($path);

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
                return $path;
            }

            return asset('storage/' . ltrim($path, '/'));
        };

        $branch = Auth::guard('branches')->user();
        $isBranch = $branch !== null;
        $branchId = $branch?->id;

        $orderQuery = Order::with(['restaurant', 'orderItems.menuItem'])
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

            return in_array($status, ['delivering', 'delivered', 'cancelled'], true)
                || in_array($shippingStatus, ['waiting for delivery', 'out for delivery', 'delivered', 'cancelled'], true)
                || ! is_null($order->delivered_at);
        });

        $kitchenOrders = $openOrders->map(function ($order) use ($publicImageUrl) {
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
                'is_test' => $order->isTestOrder(),
                'restaurant' => [
                    'name' => $order->restaurant?->name ?? '—',
                    'logo' => $publicImageUrl($order->restaurant?->logo),
                ],
                'order_items' => $order->orderItems->map(function ($item) use ($publicImageUrl) {
                    $menuItem = $item->menuItem;
                    $itemNameEn = $item->getAttribute('item_name_en')
                        ?? $menuItem?->getAttribute('name_en')
                        ?? $menuItem?->getAttribute('english_name')
                        ?? $menuItem?->getAttribute('name_english');

                    return [
                        'id' => $item->id,
                        'item_name' => $item->item_name,
                        'item_name_en' => is_string($itemNameEn) && trim($itemNameEn) !== '' ? trim($itemNameEn) : null,
                        'image' => $publicImageUrl($menuItem?->image),
                        'quantity' => $item->quantity,
                        'special_instructions' => $item->special_instructions,
                    ];
                })->values(),
            ];
        })->values();

        return Inertia::render('Kitchen', [
            'orders' => $kitchenOrders,
        ]);
    }
}
