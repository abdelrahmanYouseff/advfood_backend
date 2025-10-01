<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestLinkController extends Controller
{
    /**
     * Display the restaurants link page (Linktree style)
     */
    public function index(Request $request)
    {
        $restaurants = Restaurant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $order = null;
        if ($request->has('order_id')) {
            $order = \App\Models\Order::with(['restaurant', 'orderItems.menuItem'])->find($request->get('order_id'));
        }

        return view('rest-link', compact('restaurants', 'order'));
    }

    public function show($id)
    {
        $restaurant = Restaurant::where('is_active', true)->findOrFail($id);
        $menuItems = $restaurant->menuItems()
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

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

            return response()->json([
                'success' => true,
                'message' => 'Order saved successfully',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
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

            // Use restaurant_id as shop_id for shipping
            $shopId = (string) $request->restaurant_id;

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
                    "paymentAction" => "AUTHORIZE"
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

            \Illuminate\Support\Facades\Log::info('Noon Payment Request', [
                'order_id' => $order->id,
                'request' => $noonRequestData,
                'response' => $response->json()
            ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('Noon Payment Failed', [
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
                \Illuminate\Support\Facades\Log::error('Noon Payment: checkout URL missing', [
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

            \Illuminate\Support\Facades\Log::info('Noon Payment Success', [
                'order_id' => $order->id,
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
