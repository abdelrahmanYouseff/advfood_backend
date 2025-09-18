<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Restaurant;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'restaurant', 'orderItems']);

        // Filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by restaurant_id if provided
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment_status if provided
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request, ShippingService $shippingService)
    {
        $validator = Validator::make($request->all(), [
            // Required fields
            'user_id' => 'required|exists:users,id',
            'restaurant_id' => 'required|exists:restaurants,id',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'delivery_address' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:20',
            'delivery_name' => 'required|string|max:191',
            'payment_method' => 'required|in:cash,card,online',

            // Optional fields
            'status' => 'sometimes|in:pending,confirmed,preparing,ready,delivering,delivered,cancelled',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:1000',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'estimated_delivery_time' => 'sometimes|date',
            'shop_id' => 'sometimes|string|max:50',

            // Order items (optional for now, can be added separately)
            'items' => 'sometimes|array',
            'items.*.menu_item_id' => 'required_with:items|exists:menu_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderData = $validator->validated();

            // Set defaults
            $orderData['status'] = $orderData['status'] ?? 'pending';
            $orderData['delivery_fee'] = $orderData['delivery_fee'] ?? 0;
            $orderData['tax'] = $orderData['tax'] ?? 0;
            $orderData['payment_status'] = $orderData['payment_status'] ?? 'pending';

            // Remove items from order data (we'll handle them separately)
            $items = $orderData['items'] ?? [];
            unset($orderData['items']);

            // Set fixed shop_id for shipping integration
            $orderData['shop_id'] = '821017371';

            // Create the order
            $order = Order::create($orderData);

            // Create order items if provided
            if (!empty($items)) {
                foreach ($items as $item) {
                    $order->orderItems()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['price'] * $item['quantity'],
                    ]);
                }
            }

            // Load relationships
            $order->load(['user', 'restaurant', 'orderItems.menuItem']);

            // Automatically create shipping order (shop_id is now always available)
            $shippingResult = null;
            if (!empty($order->shop_id)) {
                try {
                    $shippingResult = $shippingService->createOrder($order);
                    if ($shippingResult) {
                        Log::info('Shipping order created automatically', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id' => $shippingResult['dsp_order_id'] ?? null,
                        ]);

                        // Update the order with shipping details if available
                        if (!empty($shippingResult['dsp_order_id'])) {
                            $order->update([
                                'dsp_order_id' => $shippingResult['dsp_order_id'],
                                'shipping_status' => $shippingResult['shipping_status'] ?? 'New Order',
                            ]);
                            $order->refresh();
                        }
                    } else {
                        Log::warning('Failed to create shipping order automatically', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception while creating shipping order automatically', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $response = [
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ];

            // Include shipping info in response if available
            if ($shippingResult) {
                $response['shipping'] = [
                    'created' => true,
                    'dsp_order_id' => $shippingResult['dsp_order_id'] ?? null,
                    'shipping_status' => $shippingResult['shipping_status'] ?? null,
                ];
            } elseif (!empty($order->shop_id)) {
                $response['shipping'] = [
                    'created' => false,
                    'message' => 'Shipping order creation failed - check logs',
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
        $order = Order::with(['user', 'restaurant', 'orderItems.menuItem'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,preparing,ready,delivering,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'special_instructions' => 'sometimes|string|max:1000',
            'estimated_delivery_time' => 'sometimes|date',
            'delivered_at' => 'sometimes|date',
            'shop_id' => 'sometimes|string|max:50',
            'shipping_status' => 'sometimes|string|max:50',
            'driver_name' => 'sometimes|string|max:191',
            'driver_phone' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update($validator->validated());
            $order->load(['user', 'restaurant', 'orderItems.menuItem']);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders for a specific user.
     */
    public function getUserOrders($userId, Request $request)
    {
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $query = Order::with(['restaurant', 'orderItems.menuItem'])
                     ->where('user_id', $userId);

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'orders_count' => $orders->total(),
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'has_more' => $orders->hasMorePages(),
            ]
        ]);
    }
}
