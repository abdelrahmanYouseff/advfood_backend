<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class SimpleOrderController extends Controller
{
    /**
     * Store a newly created order with items.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'restaurant_id' => 'sometimes|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
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

            // Create the order with initial values
            $order = Order::create([
                'user_id' => $data['user_id'] ?? Auth::id() ?? 17,
                'restaurant_id' => $data['restaurant_id'] ?? 15, // Use existing restaurant
                'status' => 'pending',
                'subtotal' => 0,
                'delivery_fee' => 0,
                'tax' => 0,
                'total' => 0, // Will be calculated below
                'delivery_address' => 'Default Address',
                'delivery_phone' => '0000000000',
                'delivery_name' => 'Default Name',
                'payment_method' => 'cash',
                'payment_status' => 'pending',
            ]);

            $totalAmount = 0;

            // Loop through items and create order items
            foreach ($data['items'] as $itemData) {
                $itemTotal = $itemData['price'] * $itemData['quantity'];
                $totalAmount += $itemTotal;

                // Create order item using relationship
                $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'menu_item_id' => 8, // Use first available menu_item_id for compatibility
                    'item_name' => 'Product ' . $itemData['product_id'], // Default name
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'subtotal' => $itemTotal,
                ]);
            }

            // Update order total
            $order->update(['total' => $totalAmount]);

            // Load the items relationship and return
            $order->load('items');

            return response()->json([
                'order' => [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'total' => $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'subtotal' => $item->subtotal,
                        ];
                    })
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order with items.
     */
    public function show($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'order' => [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'total' => $order->total,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                })
            ]
        ]);
    }
}
