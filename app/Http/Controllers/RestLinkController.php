<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestLinkController extends Controller
{
    /**
     * Display the restaurants link page (Linktree style)
     */
    public function index(Request $request)
    {
        Log::info('🏠 Rest Link page accessed', [
            'has_order_id' => $request->has('order_id'),
            'order_id' => $request->get('order_id'),
            'ip' => $request->ip(),
        ]);

        $restaurants = Restaurant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $order = null;
        if ($request->has('order_id')) {
            $order = \App\Models\Order::with(['restaurant', 'orderItems.menuItem'])->find($request->get('order_id'));
            if ($order) {
                Log::info('📦 Order found in rest-link', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }
        }

        return view('rest-link', compact('restaurants', 'order'));
    }

    /**
     * Display Tant Bakiza restaurant page
     */
    public function tantBakiza(Request $request)
    {
        $restaurant = Restaurant::where('is_active', true)
            ->where('name', 'Tant Bakiza')
            ->firstOrFail();

        $restaurants = collect([$restaurant]);

        $order = null;
        if ($request->has('order_id')) {
            $order = \App\Models\Order::with(['restaurant', 'orderItems.menuItem'])->find($request->get('order_id'));
        }

        return view('tant-bakiza', compact('restaurants', 'order'));
    }

    public function show($id)
    {
        Log::info('🍽️ Restaurant menu page accessed', [
            'restaurant_id' => $id,
            'ip' => request()->ip(),
        ]);

        $restaurant = Restaurant::where('is_active', true)->findOrFail($id);
        $menuItems = $restaurant->menuItems()
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        Log::info('📋 Restaurant menu loaded', [
            'restaurant_id' => $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'menu_items_count' => $menuItems->count(),
        ]);

        return view('restaurant-menu', compact('restaurant', 'menuItems'));
    }

    public function customerDetails()
    {
        return view('checkout.customer-details');
    }

    public function payment()
    {
        return view('checkout.payment');
    }

    public function saveOrder(Request $request)
    {
        Log::info('💾 SAVE ORDER REQUEST', [
            'restaurant_id' => $request->restaurant_id,
            'total' => $request->total,
            'items_count' => count($request->cart_items ?? []),
            'ip' => $request->ip(),
        ]);

        try {
            $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
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

            $order = \App\Models\LinkOrder::create([
                'restaurant_id' => $request->restaurant_id,
                'status' => 'pending',
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'building_no' => $request->building_no,
                'floor' => $request->floor,
                'apartment_number' => $request->apartment_number,
                'street' => $request->street,
                'note' => $request->note,
                'total' => $request->total,
                'cart_items' => $request->cart_items,
            ]);

            Log::info('✅ LinkOrder created successfully', [
                'order_id' => $order->id,
                'restaurant_id' => $order->restaurant_id,
                'total' => $order->total,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order saved successfully',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error saving LinkOrder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Noon payment and redirect to checkout
     */
    public function initiatePayment(Request $request)
    {
        Log::info('💳 INITIATE PAYMENT REQUEST', [
            'restaurant_id' => $request->restaurant_id,
            'total' => $request->total,
            'items_count' => count($request->cart_items ?? []),
            'customer_name' => $request->full_name,
            'ip' => $request->ip(),
        ]);

        try {
            $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
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

            // Debug: Dump and die to show full request
            dd([
                'request_all' => $request->all(),
                'request_headers' => $request->headers->all(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_ip' => $request->ip(),
                'customer_latitude' => $request->customer_latitude,
                'customer_longitude' => $request->customer_longitude,
                'cart_items_count' => count($request->cart_items ?? []),
                'cart_items' => $request->cart_items,
            ]);

            // Get or create guest user
            $guestUser = \App\Models\User::firstOrCreate(
                ['email' => 'guest@advfood.com'],
                [
                    'name' => 'Guest User',
                    'password' => bcrypt('guest_password_' . uniqid()),
                    'role' => 'user',
                ]
            );

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
            $restaurant = \App\Models\Restaurant::find($request->restaurant_id);
            $shopId = $restaurant?->shop_id ?? (string) $request->restaurant_id;

            // Log customer coordinates received from request
            Log::info('📍 Customer coordinates received in initiatePayment', [
                'customer_latitude' => $request->customer_latitude ?? 'NULL',
                'customer_longitude' => $request->customer_longitude ?? 'NULL',
                'has_coordinates' => !empty($request->customer_latitude) && !empty($request->customer_longitude),
            ]);

            // Create order in orders table
            $order = \App\Models\Order::create([
                'order_number' => $orderNumber,
                'user_id' => $guestUser->id,
                'restaurant_id' => $request->restaurant_id,
                'shop_id' => $shopId,
                'status' => 'pending',
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

            Log::info('✅ Order created with customer coordinates', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_latitude' => $order->customer_latitude ?? 'NULL',
                'customer_longitude' => $order->customer_longitude ?? 'NULL',
            ]);

            Log::info('✅ Order created for payment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'restaurant_id' => $order->restaurant_id,
                'shop_id' => $order->shop_id,
                'total' => $order->total,
                'payment_status' => $order->payment_status,
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

            Log::info('🌐 Sending Noon Payment Request', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $request->total,
                'api_url' => config('noon.api_url'),
            ]);

            // Send request to Noon
            $response = \Illuminate\Support\Facades\Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Key ' . $authString,
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $noonRequestData);

            Log::info('📡 Noon Payment Response Received', [
                'order_id' => $order->id,
                'status_code' => $response->status(),
                'response_data' => $response->json(),
            ]);

            if ($response->failed()) {
                Log::error('❌ Noon Payment Request Failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                    'request_data' => $noonRequestData,
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
                Log::error('❌ Noon Payment: checkout URL missing', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from payment gateway',
                    'details' => $data
                ], 500);
            }

            $checkoutUrl = $data['result']['checkoutData']['postUrl'];

            Log::info('✅ Noon Payment Success - Redirecting to checkout', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'checkout_url' => $checkoutUrl,
                'noon_order_id' => $data['result']['order']['id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment Initiation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error initiating payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
