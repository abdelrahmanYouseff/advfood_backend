<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\BranchPickupBranchResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Separate API for "استلام من الفرع" orders: same orders table and dashboard list,
 * no shipping company (DSP), scoped by branch_code → Mrouj / Laban.
 */
class BranchPickupOrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_code' => 'required|integer|in:1,2',

            'website_order_code' => 'nullable|string|max:191',

            'user_id' => 'required|exists:users,id',
            'restaurant_id' => 'required|exists:restaurants,id',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'delivery_address' => 'required|string',
            'delivery_phone' => 'required|string|max:20',
            'delivery_name' => 'required|string|max:191',
            'payment_method' => 'required|in:cash,card,online',

            'status' => 'sometimes|in:pending,confirmed,preparing,ready,delivering,delivered,cancelled',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:1000',
            'estimated_delivery_time' => 'sometimes|date',
            'customer_latitude' => 'sometimes|numeric|between:-90,90',
            'customer_longitude' => 'sometimes|numeric|between:-180,180',

            'items' => 'sometimes|array',
            'items.*.menu_item_id' => 'required_with:items|exists:menu_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.special_instructions' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $branchId = BranchPickupBranchResolver::resolveBranchId((int) $data['branch_code']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        try {
            $orderData = $data;
            unset($orderData['items'], $orderData['branch_code']);

            $items = $data['items'] ?? [];

            $orderData['branch_id'] = $branchId;
            $orderData['shop_id'] = null;
            $orderData['dsp_order_id'] = null;
            $orderData['is_branch_pickup'] = true;
            $orderData['source'] = 'branch_pickup_api';
            $orderData['payment_status'] = 'paid';
            $orderData['status'] = $orderData['status'] ?? 'pending';
            // استلام من الفرع: لا رسوم توصيل (تجاهل أي قيمة مرسلة من العميل)
            $orderData['delivery_fee'] = 0;
            $orderData['tax'] = $orderData['tax'] ?? 0;
            $orderData['total'] = round((float) $orderData['subtotal'] + (float) $orderData['tax'], 2);
            $orderData['shipping_status'] = 'Branch Pickup';

            $orderData['order_number'] = $this->generateOrderNumber();

            $order = DB::transaction(function () use ($orderData, $items) {
                $order = Order::create($orderData);

                foreach ($items as $item) {
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    if (!$menuItem) {
                        throw new \RuntimeException("Menu item {$item['menu_item_id']} not found");
                    }

                    $order->orderItems()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $menuItem->name,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity'],
                        'special_instructions' => $item['special_instructions'] ?? null,
                    ]);
                }

                return $order;
            });

            $order->load(['user', 'restaurant', 'branch', 'orderItems.menuItem']);

            Log::info('Branch pickup order created via API', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'website_order_code' => $order->website_order_code,
                'branch_id' => $order->branch_id,
                'branch_code' => $data['branch_code'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created (branch pickup — no shipping company)',
                'data' => $order,
                'branch_code' => (int) $data['branch_code'],
                'website_order_code' => $order->website_order_code,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Branch pickup order API failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', today())->count() + 1;

        return 'ORD-' . $date . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
