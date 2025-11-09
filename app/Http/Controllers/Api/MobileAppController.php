<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Restaurant;

class MobileAppController extends Controller
{
    /**
     * Test authentication
     */
    public function testAuth(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Authentication working',
            'user' => $request->user()
        ]);
    }

    /**
     * Get user points by point_customer_id
     */
    public function getPointsByCustomerId(Request $request, $pointCustomerId)
    {
        try {
            if (empty($pointCustomerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Point customer ID is required'
                ], 400);
            }

            $pointsService = new PointsService();
            $pointsData = $pointsService->getCustomerPoints($pointCustomerId);

            if ($pointsData && isset($pointsData['status']) && $pointsData['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Points retrieved successfully',
                    'data' => [
                        'customer_id' => $pointCustomerId,
                        'points_balance' => $pointsData['data']['points_balance'] ?? 0,
                        'tier' => $pointsData['data']['tier'] ?? 'bronze',
                        'total_earned' => $pointsData['data']['total_earned'] ?? 0,
                        'total_redeemed' => $pointsData['data']['total_redeemed'] ?? 0,
                        'name' => $pointsData['data']['name'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or unable to retrieve points',
                    'data' => [
                        'customer_id' => $pointCustomerId,
                        'points_balance' => 0,
                        'tier' => 'bronze'
                    ]
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders created by a user (mobile app)
     */
    public function getUserOrders(Request $request)
    {
        $request->validate([
            'phone_number' => 'nullable|string|max:20',
            'user_id' => 'nullable|integer|exists:users,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if (!$request->filled('phone_number') && !$request->filled('user_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Either phone_number or user_id is required',
            ], 422);
        }

        $query = Order::with(['restaurant:id,name,address,phone', 'orderItems:id,order_id,item_name,quantity,price,subtotal'])
            ->orderByDesc('created_at');

        if ($request->filled('phone_number')) {
            $query->where('delivery_phone', $request->string('phone_number'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $limit = $request->integer('limit', 20);
        $orders = $query->take($limit)->get();

        $formatted = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'shipping_status' => $order->shipping_status,
                'payment_status' => $order->payment_status,
                'total' => (float) $order->total,
                'delivery_name' => $order->delivery_name,
                'delivery_phone' => $order->delivery_phone,
                'delivery_address' => $order->delivery_address,
                'restaurant' => $order->restaurant ? [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                    'address' => $order->restaurant->address,
                    'phone' => $order->restaurant->phone,
                ] : null,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }),
                'created_at' => optional($order->created_at)->toDateTimeString(),
                'updated_at' => optional($order->updated_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formatted->count(),
            'orders' => $formatted,
        ]);
    }

    /**
     * Get payment checkout URL for mobile app
     */
    public function getPaymentCheckoutUrl(Request $request)
    {
        try {
            // Debug: Check if restaurant exists
            $restaurant = Restaurant::find($request->restaurant_id);
            if (!$restaurant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Restaurant not found with ID: ' . $request->restaurant_id
                ], 404);
            }

            $request->validate([
                'restaurant_id' => 'required|integer',
                'user_id' => 'nullable|integer',
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'building_no' => 'required|string|max:50',
                'floor' => 'required|string|max:50',
                'apartment_number' => 'required|string|max:50',
                'street' => 'required|string|max:255',
                'note' => 'nullable|string',
                'total' => 'required|numeric|min:0',
                'cart_items' => 'required|array',
            ]);

            // Resolve user ID
            if ($request->filled('user_id')) {
                $userId = $request->integer('user_id');
                if (!\App\Models\User::where('id', $userId)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found',
                    ], 404);
                }
            } else {
                $guestUser = \App\Models\User::firstOrCreate(
                    ['email' => 'guest@advfood.com'],
                    [
                        'name' => 'Guest User',
                        'password' => bcrypt('guest_password_' . uniqid()),
                        'role' => 'user',
                    ]
                );
                $userId = $guestUser->id;
            }

            // Build delivery address
            $deliveryAddress = sprintf(
                'مبنى %s، الطابق %s، شقة %s، %s',
                $request->building_no,
                $request->floor,
                $request->apartment_number,
                $request->street
            );

            // Calculate subtotal (total without delivery fee and tax for now)
            $subtotal = $request->total;
            $deliveryFee = 0;
            $tax = 0;

            // Generate unique order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Get restaurant shop_id for shipping
            $restaurant = Restaurant::find($request->restaurant_id);
            $shopId = $restaurant?->shop_id ?? (string) $request->restaurant_id;

            // Create order in orders table
            $order = \App\Models\Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'restaurant_id' => $request->restaurant_id,
                'shop_id' => $shopId,
                'status' => 'pending',
                'source' => 'application',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total' => $request->total,
                'delivery_address' => $deliveryAddress,
                'delivery_phone' => $request->phone_number,
                'delivery_name' => $request->full_name,
                'customer_latitude' => $request->customer_latitude ?? null,
                'customer_longitude' => $request->customer_longitude ?? null,
                'special_instructions' => $request->note,
                'payment_method' => 'online',
                'payment_status' => 'pending',
            ]);

            // Create order items
            foreach ($request->cart_items as $item) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['id'] ?? null,
                    'item_name' => $item['name'] ?? 'منتج',
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            // Prepare Noon payment request
            $noonRequestData = [
                "apiOperation" => "INITIATE",
                "order" => [
                    "amount" => $request->total,
                    "currency" => "SAR",
                    "reference" => "ORDER-" . $order->id . "-" . now()->timestamp,
                    "name" => "Order #" . $order->id,
                    "category" => "pay"
                ],
                "configuration" => [
                    "returnUrl" => route('payment.success') . '?order_id=' . $order->id,
                    "paymentAction" => "SALE"
                ]
            ];

            // Create Authorization header
            $businessId = config('noon.business_id');
            $applicationId = config('noon.application_id');
            $apiKey = config('noon.api_key');
            $authString = base64_encode($businessId . '.' . $applicationId . ':' . $apiKey);

            // Send request to Noon
            $response = \Illuminate\Support\Facades\Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Key ' . $authString,
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $noonRequestData);

            \Illuminate\Support\Facades\Log::info('Mobile API - Noon Payment Request', [
                'order_id' => $order->id,
                'request' => $noonRequestData,
                'response' => $response->json()
            ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('Mobile API - Noon Payment Failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway error. Please try again.',
                    'details' => $response->json()
                ], $response->status());
            }

            $data = $response->json();

            // Get checkout URL from response
            if (!isset($data['result']['checkoutData']['postUrl'])) {
                \Illuminate\Support\Facades\Log::error('Mobile API - Noon Payment: checkout URL missing', [
                    'order_id' => $order->id,
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from payment gateway',
                    'details' => $data
                ], 500);
            }

            $checkoutUrl = $data['result']['checkoutData']['postUrl'];

            \Illuminate\Support\Facades\Log::info('Mobile API - Noon Payment Success', [
                'order_id' => $order->id,
                'checkout_url' => $checkoutUrl,
                'noon_order_id' => $data['result']['order']['id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment checkout URL generated successfully',
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'total' => $request->total,
                    'restaurant_name' => $restaurant->name
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mobile API - Payment Checkout Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error generating payment checkout URL: ' . $e->getMessage()
            ], 500);
        }
    }
}
