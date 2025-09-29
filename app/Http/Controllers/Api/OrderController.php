<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\MenuItem;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

            // Generate order number
            $orderData['order_number'] = $this->generateOrderNumber();

            // Create the order
            $order = Order::create($orderData);

            // Create order items if provided
            if (!empty($items)) {
                foreach ($items as $item) {
                    // Get menu item details
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    if (!$menuItem) {
                        throw new \Exception("Menu item with ID {$item['menu_item_id']} not found");
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
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', today())->count() + 1;
        return 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Display the specified order with complete details.
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

        // Get shipping details if available
        $shippingOrder = null;
        if (!empty($order->dsp_order_id)) {
            $shippingOrder = DB::table('shipping_orders')
                ->where('dsp_order_id', $order->dsp_order_id)
                ->first();
        }

        // Calculate totals from order items
        $itemsSubtotal = $order->orderItems->sum('subtotal');
        $itemsCount = $order->orderItems->sum('quantity');

        // Format response with complete details
        $response = [
            'success' => true,
            'data' => [
                // Order basic info
                'order_info' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                    'estimated_delivery_time' => $order->estimated_delivery_time,
                    'delivered_at' => $order->delivered_at,
                    'special_instructions' => $order->special_instructions,
                ],

                // Customer details
                'customer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone_number ?? $order->user->phone,
                    'role' => $order->user->role,
                ],

                // Delivery details
                'delivery' => [
                    'name' => $order->delivery_name,
                    'phone' => $order->delivery_phone,
                    'address' => $order->delivery_address,
                ],

                // Restaurant details
                'restaurant' => [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                    'description' => $order->restaurant->description,
                    'address' => $order->restaurant->address,
                    'phone' => $order->restaurant->phone,
                    'email' => $order->restaurant->email,
                    'delivery_fee' => $order->restaurant->delivery_fee,
                    'delivery_time' => $order->restaurant->delivery_time,
                    'rating' => $order->restaurant->rating,
                ],

                // Order items with details
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'item_name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'special_instructions' => $item->special_instructions,
                        'menu_item_details' => [
                            'name' => $item->menuItem->name,
                            'description' => $item->menuItem->description,
                            'original_price' => $item->menuItem->price,
                            'image' => $item->menuItem->image,
                            'preparation_time' => $item->menuItem->preparation_time,
                            'ingredients' => $item->menuItem->ingredients,
                            'allergens' => $item->menuItem->allergens,
                        ]
                    ];
                }),

                // Price breakdown
                'pricing' => [
                    'items_subtotal' => $itemsSubtotal,
                    'order_subtotal' => $order->subtotal,
                    'delivery_fee' => $order->delivery_fee,
                    'tax' => $order->tax,
                    'total' => $order->total,
                    'items_count' => $itemsCount,
                ],

                // Shipping details
                'shipping' => $shippingOrder ? [
                    'shop_id' => $order->shop_id,
                    'dsp_order_id' => $order->dsp_order_id,
                    'shipping_status' => $order->shipping_status,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                    'driver_location' => [
                        'latitude' => $order->driver_latitude,
                        'longitude' => $order->driver_longitude,
                    ],
                    'recipient_details' => [
                        'name' => $shippingOrder->recipient_name,
                        'phone' => $shippingOrder->recipient_phone,
                        'address' => $shippingOrder->recipient_address,
                        'location' => [
                            'latitude' => $shippingOrder->latitude,
                            'longitude' => $shippingOrder->longitude,
                        ]
                    ],
                    'shipping_total' => $shippingOrder->total,
                    'payment_type' => $shippingOrder->payment_type,
                    'notes' => $shippingOrder->notes,
                ] : [
                    'shop_id' => $order->shop_id,
                    'status' => 'Not shipped yet',
                    'message' => 'Order has not been sent to shipping provider'
                ]
            ]
        ];

        return response()->json($response);
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
     * Get all orders for a specific user with complete details.
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

        $query = Order::with(['restaurant', 'orderItems.menuItem', 'user'])
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

        // Search by order number
        if ($request->has('order_number')) {
            $query->where('order_number', 'like', '%' . $request->order_number . '%');
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['created_at', 'updated_at', 'total', 'status', 'order_number'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        // Format orders with complete details
        $formattedOrders = $orders->map(function ($order) {
            // Calculate totals from order items
            $itemsSubtotal = $order->orderItems->sum('subtotal');
            $itemsCount = $order->orderItems->sum('quantity');

            // Get shipping details if available
            $shippingOrder = null;
            if (!empty($order->dsp_order_id)) {
                $shippingOrder = DB::table('shipping_orders')
                    ->where('dsp_order_id', $order->dsp_order_id)
                    ->first();
            }

            return [
                // Order basic info
                'order_info' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'estimated_delivery_time' => $order->estimated_delivery_time,
                    'delivered_at' => $order->delivered_at,
                    'special_instructions' => $order->special_instructions,
                ],

                // Customer details
                'customer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone_number ?? $order->user->phone,
                    'role' => $order->user->role,
                ],

                // Delivery details
                'delivery' => [
                    'name' => $order->delivery_name,
                    'phone' => $order->delivery_phone,
                    'address' => $order->delivery_address,
                ],

                // Restaurant details
                'restaurant' => [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                    'description' => $order->restaurant->description,
                    'address' => $order->restaurant->address,
                    'phone' => $order->restaurant->phone,
                    'email' => $order->restaurant->email,
                    'delivery_fee' => $order->restaurant->delivery_fee,
                    'delivery_time' => $order->restaurant->delivery_time,
                    'rating' => $order->restaurant->rating,
                ],

                // Order items with details
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'item_name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'special_instructions' => $item->special_instructions,
                        'menu_item_details' => [
                            'name' => $item->menuItem->name,
                            'description' => $item->menuItem->description,
                            'original_price' => $item->menuItem->price,
                            'image' => $item->menuItem->image,
                            'preparation_time' => $item->menuItem->preparation_time,
                            'ingredients' => $item->menuItem->ingredients,
                            'allergens' => $item->menuItem->allergens,
                        ]
                    ];
                }),

                // Price breakdown
                'pricing' => [
                    'items_subtotal' => $itemsSubtotal,
                    'order_subtotal' => $order->subtotal,
                    'delivery_fee' => $order->delivery_fee,
                    'tax' => $order->tax,
                    'total' => $order->total,
                    'items_count' => $itemsCount,
                ],

                // Shipping details
                'shipping' => $shippingOrder ? [
                    'shop_id' => $order->shop_id,
                    'dsp_order_id' => $order->dsp_order_id,
                    'shipping_status' => $order->shipping_status,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                    'driver_location' => [
                        'latitude' => $order->driver_latitude,
                        'longitude' => $order->driver_longitude,
                    ],
                    'recipient_details' => [
                        'name' => $shippingOrder->recipient_name,
                        'phone' => $shippingOrder->recipient_phone,
                        'address' => $shippingOrder->recipient_address,
                        'location' => [
                            'latitude' => $shippingOrder->latitude,
                            'longitude' => $shippingOrder->longitude,
                        ]
                    ],
                    'shipping_total' => $shippingOrder->total,
                    'payment_type' => $shippingOrder->payment_type,
                    'notes' => $shippingOrder->notes,
                ] : [
                    'shop_id' => $order->shop_id,
                    'status' => 'Not shipped yet',
                    'message' => 'Order has not been sent to shipping provider'
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOrders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? $user->phone,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Get order statistics for a specific user.
     */
    public function getUserOrdersStats($userId, Request $request)
    {
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $query = Order::where('user_id', $userId);

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Calculate statistics
        $totalOrders = $query->count();
        $totalSpent = $query->sum('total');
        $averageOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;

        // Orders by status
        $statusStats = [
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'confirmed' => $query->clone()->where('status', 'confirmed')->count(),
            'preparing' => $query->clone()->where('status', 'preparing')->count(),
            'ready' => $query->clone()->where('status', 'ready')->count(),
            'delivering' => $query->clone()->where('status', 'delivering')->count(),
            'delivered' => $query->clone()->where('status', 'delivered')->count(),
            'cancelled' => $query->clone()->where('status', 'cancelled')->count(),
        ];

        // Recent activity (last 30 days)
        $recentOrders = Order::where('user_id', $userId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();

        $recentSpent = Order::where('user_id', $userId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->sum('total');

        // Most ordered items
        $topItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.user_id', $userId)
            ->select(
                'menu_items.name',
                'menu_items.id',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(order_items.id) as order_count')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // Favorite restaurants
        $topRestaurants = DB::table('orders')
            ->join('restaurants', 'orders.restaurant_id', '=', 'restaurants.id')
            ->where('orders.user_id', $userId)
            ->select(
                'restaurants.name',
                'restaurants.id',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total) as total_spent')
            )
            ->groupBy('restaurants.id', 'restaurants.name')
            ->orderBy('order_count', 'desc')
            ->limit(5)
            ->get();

        $stats = [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'average_order_value' => round($averageOrderValue, 2),
            'orders_by_status' => $statusStats,
            'recent_activity' => [
                'orders_last_30_days' => $recentOrders,
                'spent_last_30_days' => $recentSpent,
            ],
            'top_items' => $topItems,
            'favorite_restaurants' => $topRestaurants
        ];

        return response()->json([
            'success' => true,
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? $user->phone,
                'role' => $user->role,
            ],
            'statistics' => $stats,
            'filters_applied' => [
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
            ]
        ]);
    }
}
