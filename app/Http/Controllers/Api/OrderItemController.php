<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Display order items for a specific order.
     */
    public function index($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $orderItems = OrderItem::with('menuItem')
            ->where('order_id', $orderId)
            ->get();

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
            ],
            'items_count' => $orderItems->count(),
            'total_quantity' => $orderItems->sum('quantity'),
            'total_amount' => $orderItems->sum('subtotal'),
            'data' => $orderItems
        ]);
    }

    /**
     * Store a new order item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Get menu item details
            $menuItem = MenuItem::find($data['menu_item_id']);
            if (!$menuItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found'
                ], 404);
            }

            // Check if order exists and is not completed/cancelled
            $order = Order::find($data['order_id']);
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add items to completed or cancelled orders'
                ], 400);
            }

            // Create order item
            $orderItem = OrderItem::create([
                'order_id' => $data['order_id'],
                'menu_item_id' => $data['menu_item_id'],
                'item_name' => $menuItem->name,
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'subtotal' => $data['price'] * $data['quantity'],
                'special_instructions' => $data['special_instructions'] ?? null,
            ]);

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            $orderItem->load('menuItem');

            return response()->json([
                'success' => true,
                'message' => 'Order item added successfully',
                'data' => $orderItem,
                'order_totals' => [
                    'items_subtotal' => $order->orderItems()->sum('subtotal'),
                    'order_total' => $order->fresh()->total,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order item.
     */
    public function show($id)
    {
        $orderItem = OrderItem::with(['menuItem', 'order'])->find($id);

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $orderItem
        ]);
    }

    /**
     * Update the specified order item.
     */
    public function update(Request $request, $id)
    {
        $orderItem = OrderItem::find($id);

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        // Check if order can be modified
        $order = $orderItem->order;
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify items in completed or cancelled orders'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Update subtotal if quantity or price changed
            if (isset($data['quantity']) || isset($data['price'])) {
                $newQuantity = $data['quantity'] ?? $orderItem->quantity;
                $newPrice = $data['price'] ?? $orderItem->price;
                $data['subtotal'] = $newQuantity * $newPrice;
            }

            $orderItem->update($data);

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            $orderItem->load('menuItem');

            return response()->json([
                'success' => true,
                'message' => 'Order item updated successfully',
                'data' => $orderItem,
                'order_totals' => [
                    'items_subtotal' => $order->orderItems()->sum('subtotal'),
                    'order_total' => $order->fresh()->total,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified order item.
     */
    public function destroy($id)
    {
        $orderItem = OrderItem::find($id);

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        // Check if order can be modified
        $order = $orderItem->order;
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove items from completed or cancelled orders'
            ], 400);
        }

        try {
            $orderItem->delete();

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            return response()->json([
                'success' => true,
                'message' => 'Order item removed successfully',
                'order_totals' => [
                    'items_subtotal' => $order->orderItems()->sum('subtotal'),
                    'order_total' => $order->fresh()->total,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add multiple items to an order at once.
     */
    public function addMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.special_instructions' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            $order = Order::find($data['order_id']);
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add items to completed or cancelled orders'
                ], 400);
            }

            $createdItems = [];

            DB::transaction(function () use ($data, &$createdItems) {
                foreach ($data['items'] as $itemData) {
                    $menuItem = MenuItem::find($itemData['menu_item_id']);

                    $orderItem = OrderItem::create([
                        'order_id' => $data['order_id'],
                        'menu_item_id' => $itemData['menu_item_id'],
                        'item_name' => $menuItem->name,
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['price'] * $itemData['quantity'],
                        'special_instructions' => $itemData['special_instructions'] ?? null,
                    ]);

                    $orderItem->load('menuItem');
                    $createdItems[] = $orderItem;
                }
            });

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            return response()->json([
                'success' => true,
                'message' => count($createdItems) . ' items added successfully',
                'data' => $createdItems,
                'order_totals' => [
                    'items_subtotal' => $order->orderItems()->sum('subtotal'),
                    'order_total' => $order->fresh()->total,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate order totals based on order items.
     */
    private function recalculateOrderTotals(Order $order)
    {
        $itemsSubtotal = $order->orderItems()->sum('subtotal');
        $deliveryFee = $order->delivery_fee ?? 0;
        $tax = $order->tax ?? 0;

        $order->update([
            'subtotal' => $itemsSubtotal,
            'total' => $itemsSubtotal + $deliveryFee + $tax,
        ]);
    }
}
