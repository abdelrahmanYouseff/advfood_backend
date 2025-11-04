<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    protected $apiBaseUrl;
    protected $apiKey;
    protected $endpoints;
    protected $sendAsForm;
    protected $cancelMethod;

    public function __construct()
    {
        $this->apiBaseUrl = Config::get('services.shipping.url');
        $this->apiKey = Config::get('services.shipping.key');
        $this->endpoints = Config::get('services.shipping.endpoints', [
            'create' => '/orders',
            'status' => '/orders/{id}',
            'cancel' => '/orders/{id}',
        ]);
        $this->sendAsForm = (bool) Config::get('services.shipping.send_as_form', false);
        $this->cancelMethod = strtolower((string) Config::get('services.shipping.cancel_method', 'delete'));
    }

    public function createOrder($order)
    {
        Log::info('📦 SHIPPINGSERVICE::createOrder CALLED', [
            'order_type' => gettype($order),
            'order_id' => is_object($order) ? ($order->id ?? 'N/A') : (is_array($order) ? ($order['id'] ?? 'N/A') : 'N/A'),
            'environment' => config('app.env'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            Log::info('🔍 STEP 1: Checking API credentials', [
                'api_url_exists' => !empty($this->apiBaseUrl),
                'api_url' => $this->apiBaseUrl ?: 'NOT_SET',
                'api_key_exists' => !empty($this->apiKey),
                'api_key_length' => strlen($this->apiKey ?? ''),
            ]);

            if (empty($this->apiBaseUrl) || empty($this->apiKey)) {
                Log::error('❌ Shipping API credentials missing!', [
                    'api_url' => $this->apiBaseUrl ?: 'NOT_SET',
                    'api_key_exists' => !empty($this->apiKey),
                    'message' => 'Please check SHIPPING_API_URL and SHIPPING_API_KEY in .env file',
                    'environment' => config('app.env'),
                ]);
                return null;
            }

            Log::info('✅ API credentials OK');

            $orderObj = is_array($order) ? (object) $order : $order;

            // Ensure we use order_number (the unique identifier) not the internal id
            // This is the order_number that appears in the system (e.g., ORD-20251104-02EEA5)
            $orderIdString = (string) ($orderObj->order_number ?? $orderObj->id ?? '');

            Log::info('🔍 Order identifier for shipping', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'order_number_exists' => !empty($orderObj->order_number),
                'id_fallback_used' => empty($orderObj->order_number) && !empty($orderObj->id),
                'final_order_id_string' => $orderIdString,
                'order_id_string_type' => gettype($orderIdString),
            ]);

            // Get shop_id - try from order, then from restaurant, then use default
            $shopIdString = isset($orderObj->shop_id) ? (string) $orderObj->shop_id : null;

            // If shop_id is not set, try to get it from restaurant
            if (empty($shopIdString) && isset($orderObj->restaurant_id)) {
                try {
                    $restaurant = \App\Models\Restaurant::find($orderObj->restaurant_id);
                    if ($restaurant && !empty($restaurant->shop_id)) {
                        $shopIdString = (string) $restaurant->shop_id;
                        Log::info('🔍 Got shop_id from restaurant', [
                            'restaurant_id' => $orderObj->restaurant_id,
                            'shop_id' => $shopIdString,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not fetch restaurant for shop_id', [
                        'restaurant_id' => $orderObj->restaurant_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to default shop_id if still empty (should not happen if restaurant has shop_id)
            if (empty($shopIdString)) {
                $shopIdString = '11183'; // Default fallback
                Log::warning('⚠️ Using default shop_id (restaurant shop_id not found)', [
                    'shop_id' => $shopIdString,
                    'order_id' => $orderObj->id ?? null,
                    'restaurant_id' => $orderObj->restaurant_id ?? null,
                ]);
            }

            Log::info('🚀 Starting shipping order creation', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
                'shop_id_type' => gettype($shopIdString),
                'customer_name' => $orderObj->delivery_name ?? null,
                'total' => $orderObj->total ?? null,
                'restaurant_id' => $orderObj->restaurant_id ?? null,
            ]);

            // Validate that we have order_number (the unique identifier used throughout the system)
            if (empty($orderIdString)) {
                Log::error('❌ Shipping order creation aborted - Missing order_number', [
                    'reason' => 'order_number is required but is empty',
                    'order_id' => $orderObj->id ?? null,
                    'order_number_exists' => !empty($orderObj->order_number ?? null),
                    'order_number_value' => $orderObj->order_number ?? 'MISSING',
                    'id_fallback_available' => !empty($orderObj->id ?? null),
                    'restaurant_id' => $orderObj->restaurant_id ?? null,
                    'message' => 'Order must have order_number (e.g., ORD-20251104-02EEA5) to send to shipping company',
                ]);
                return null;
            }

            // Validate shop_id
            if (empty($shopIdString)) {
                Log::error('❌ Shipping order creation aborted - Missing shop_id', [
                    'reason' => 'shop_id is required but is empty',
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderIdString,
                    'restaurant_id' => $orderObj->restaurant_id ?? null,
                    'message' => 'Order must have shop_id to send to shipping company',
                ]);
                return null;
            }

            Log::info('✅ Validation passed - Ready to send to shipping', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
                'order_number_format' => 'ORD-YYYYMMDD-XXXXXX',
            ]);

            // Validate shop_id format - try different formats
            $shopIdForApi = $shopIdString;
            if (is_numeric($shopIdString)) {
                // Clean the string first
                $shopIdForApi = trim((string) $shopIdString);

                // Some APIs expect integer, try that if string fails
                // We'll log which format is being used
                Log::info('🔍 Shop ID format check', [
                    'original' => $shopIdString,
                    'formatted' => $shopIdForApi,
                    'is_numeric' => is_numeric($shopIdString),
                    'as_integer' => (int) $shopIdString,
                ]);
            }

            $latitude = isset($orderObj->latitude) ? $orderObj->latitude : null;
            $longitude = isset($orderObj->longitude) ? $orderObj->longitude : null;

            // Make phone unique by appending order ID
            $uniquePhone = $orderObj->delivery_phone ?? null;
            if ($uniquePhone) {
                // Remove any existing suffix and add new one
                $uniquePhone = preg_replace('/#\d+$/', '', $uniquePhone);
                $uniquePhone .= '#' . ($orderObj->id ?? time());
            }

            // Make email unique by appending order ID
            $uniqueEmail = 'order' . ($orderObj->id ?? time()) . '@advfood.local';

            // Payload with order_number as the unique identifier
            // The 'id' field will contain the order_number (e.g., ORD-20251104-02EEA5)
            // This is the same order_number that appears in the system and is tracked everywhere
            $payload = [
                'id' => $orderIdString, // This is the order_number (ORD-20251104-02EEA5)
                'shop_id' => $shopIdForApi,
                'delivery_details' => [
                    'name' => $orderObj->delivery_name ?? null,
                    'phone' => $uniquePhone,
                    'email' => $uniqueEmail,
                    'coordinate' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                    'address' => $orderObj->delivery_address ?? null,
                ],
                'order' => [
                    'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                    'total' => (float) ($orderObj->total ?? 0),
                    'notes' => $orderObj->special_instructions ?? null,
                ],
            ];

            Log::info('📋 Shipping payload prepared with order_number', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'payload_id_field' => $payload['id'], // This should be the order_number
                'payload_id_type' => gettype($payload['id']),
                'shop_id' => $shopIdForApi,
                'customer_name' => $orderObj->delivery_name ?? null,
                'total' => $payload['order']['total'],
            ]);

            $url = $this->buildUrl($this->endpoints['create']);

            // Log the request details with emphasis on order_number
            Log::info('📤 Sending order to shipping company (using order_number)', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'order_number_sent_in_payload' => $payload['id'], // This is what gets sent as 'id'
                'shop_id' => $shopIdForApi,
                'shop_id_original' => $shopIdString,
                'url' => $url,
                'api_base_url' => $this->apiBaseUrl,
                'api_key_exists' => !empty($this->apiKey),
                'api_key_length' => strlen($this->apiKey ?? ''),
                'api_key_prefix' => substr($this->apiKey ?? '', 0, 10) . '...',
                'payload' => $payload,
                'payload_id_field_value' => $payload['id'], // Explicitly show what's being sent
                'environment' => config('app.env'),
                'server_ip' => request()->server('SERVER_ADDR') ?? 'unknown',
            ]);

            $request = Http::timeout(30)
                ->retry(3, 100) // Retry 3 times with 100ms delay
                ->withOptions([
                    'verify' => env('SHIPPING_API_VERIFY_SSL', true), // Allow disabling SSL verification if needed
                    'http_errors' => false, // Don't throw exceptions on HTTP errors
                ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ]);

            $response = $this->sendAsForm
                ? $request->asForm()->post($url, $this->flattenArray($payload))
                : $request->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);

            // Log the response details
            if (!$response || $response->failed()) {
                $responseBody = $response ? $response->body() : null;
                $responseJson = $response ? $response->json() : null;
                $statusCode = $response ? $response->status() : 'NO_RESPONSE';

                Log::error('❌ Failed to send order to shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'order_number_sent' => $orderIdString, // The order_number that was sent
                    'shop_id' => $shopIdString,
                    'http_status' => $statusCode,
                    'url' => $url,
                    'error_message' => $responseJson['message'] ?? $responseJson['error'] ?? 'Unknown error',
                    'errors' => $responseJson['errors'] ?? null,
                    'full_response_body' => $responseBody,
                    'full_response_json' => $responseJson,
                    'request_payload' => $payload,
                    'payload_id_field' => $payload['id'], // Show what was sent as 'id'
                ]);

                // More detailed error logging
                if ($statusCode === 422) {
                    Log::error('🔴 Validation Error (422) - Details:', [
                        'validation_errors' => $responseJson['errors'] ?? 'No specific errors provided',
                        'message' => $responseJson['message'] ?? 'Validation failed',
                        'shop_id_sent' => $shopIdForApi,
                        'shop_id_type' => gettype($shopIdForApi),
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderObj->order_number ?? 'MISSING',
                        'order_number_sent' => $orderIdString, // The order_number sent in payload
                        'full_response' => $responseJson,
                        'suggestion' => 'Please verify shop_id and order_number format are correct in shipping company system',
                    ]);

                    // If "Invalid shop" error, log additional details
                    if (isset($responseJson['message']) && stripos($responseJson['message'], 'shop') !== false) {
                        Log::error('⚠️ SHOP_ID VALIDATION ISSUE', [
                            'shop_id_used' => $shopIdForApi,
                            'restaurant_id' => $orderObj->restaurant_id ?? null,
                            'order_number' => $orderIdString,
                            'action_required' => 'Verify shop_id in shipping company dashboard',
                            'current_shop_id_source' => 'From order.shop_id or restaurant.shop_id',
                        ]);
                    }
                } elseif ($statusCode === 401) {
                    Log::error('🔴 Authentication Error (401) - Invalid API Token', [
                        'api_key_length' => strlen($this->apiKey ?? ''),
                        'api_key_prefix' => substr($this->apiKey ?? '', 0, 20) . '...',
                    ]);
                } elseif ($statusCode === 404) {
                    Log::error('🔴 Not Found (404) - Invalid endpoint or order not found', [
                        'url' => $url,
                        'endpoint' => $this->endpoints['create'],
                    ]);
                } elseif ($statusCode === 500) {
                    Log::error('🔴 Server Error (500) - Shipping company server error');
                }

                return null;
            }

            $data = $response->json();

            // Log successful response
            Log::info('✅ Shipping API Response Received', [
                'http_status' => $response->status(),
                'full_response' => $data,
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
            ]);

            $dspOrderId = $data['dsp_order_id'] ?? $data['data']['dsp_order_id'] ?? $data['id'] ?? null;
            $shippingStatus = $data['status'] ?? $data['data']['status'] ?? 'New Order';

            // If no dsp_order_id from provider, generate one starting from 00020
            if (empty($dspOrderId)) {
                $today = date('Ymd');
                $latestShipping = DB::table('shipping_orders')
                    ->where('dsp_order_id', 'like', "ORD-{$today}-%")
                    ->orderBy('dsp_order_id', 'desc')
                    ->first();

                if ($latestShipping) {
                    $lastNumber = (int) substr($latestShipping->dsp_order_id, -5);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 20; // Start from 00020
                }

                $dspOrderId = 'ORD-' . $today . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            }

            $row = [
                'order_id' => $orderObj->id,
                'shop_id' => $orderObj->shop_id ?? null,
                'dsp_order_id' => $dspOrderId,
                'shipping_status' => $shippingStatus,
                'recipient_name' => $orderObj->delivery_name ?? '',
                'recipient_phone' => $orderObj->delivery_phone ?? '',
                'recipient_address' => $orderObj->delivery_address ?? '',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'driver_name' => null,
                'driver_phone' => null,
                'driver_latitude' => null,
                'driver_longitude' => null,
                'total' => (float) ($orderObj->total ?? 0),
                'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                'notes' => $orderObj->special_instructions ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('shipping_orders')->insert($row);

            // Log final success with all details
            Log::info('🎉 Order successfully sent to shipping company and saved!', [
                'order_id' => $orderObj->id,
                'order_number' => $orderIdString,
                'dsp_order_id' => $dspOrderId,
                'shipping_status' => $shippingStatus,
                'shop_id' => $shopIdString,
                'customer' => [
                    'name' => $orderObj->delivery_name ?? '',
                    'phone' => $orderObj->delivery_phone ?? '',
                    'address' => $orderObj->delivery_address ?? '',
                ],
                'order_details' => [
                    'total' => (float) ($orderObj->total ?? 0),
                    'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                    'notes' => $orderObj->special_instructions ?? null,
                ],
            ]);

            return $row;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Handle connection errors (network issues, DNS, etc.)
            Log::error('🔴 Connection Exception - Cannot reach shipping API', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString ?? 'UNKNOWN',
                'shop_id' => $shopIdString ?? 'UNKNOWN',
                'api_url' => $this->apiBaseUrl ?? 'NOT_SET',
                'exception_message' => $e->getMessage(),
                'exception_code' => $e->getCode(),
                'suggestion' => 'Check network connectivity, DNS resolution, firewall, and API URL on server',
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('💥 Exception during shipping order creation', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString ?? 'UNKNOWN',
                'shop_id' => $shopIdString ?? 'UNKNOWN',
                'api_url' => $this->apiBaseUrl ?? 'NOT_SET',
                'api_key_exists' => !empty($this->apiKey),
                'exception_message' => $e->getMessage(),
                'exception_code' => $e->getCode(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function getOrderStatus($shippingOrderId)
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->apiKey) || empty($shippingOrderId)) {
                return null;
            }

            $url = $this->buildUrl($this->endpoints['status'], ['id' => $shippingOrderId]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (!$response || $response->failed()) {
                Log::warning('Failed to get shipping order status', [
                    'dsp_order_id' => $shippingOrderId,
                    'status' => $response ? $response->status() : null,
                    'body' => $response ? $response->body() : null,
                    'url' => $url,
                ]);
                return null;
            }

            $data = $response->json();

            $clientOrderId = $data['id'] ?? ($data['data']['id'] ?? null);
            $status = $data['status'] ?? ($data['data']['status'] ?? null);
            $dspId = $data['dsp_order_id'] ?? ($data['data']['dsp_order_id'] ?? $shippingOrderId);
            $driver = $data['driver'] ?? ($data['data']['driver'] ?? null);
            $driverName = is_array($driver) ? ($driver['name'] ?? null) : null;
            $driverPhone = is_array($driver) ? ($driver['phone'] ?? null) : null;
            $driverLat = is_array($driver) ? ($driver['location']['latitude'] ?? null) : null;
            $driverLng = is_array($driver) ? ($driver['location']['longitude'] ?? null) : null;

            // Upsert into shipping_orders
            $existing = DB::table('shipping_orders')->where('dsp_order_id', $dspId)->first();
            if (!$existing) {
                $orderRow = null;
                if (!empty($clientOrderId)) {
                    $orderRow = DB::table('orders')->where('order_number', $clientOrderId)->first();
                }
                if (!$orderRow) {
                    $orderRow = DB::table('orders')->where('dsp_order_id', $dspId)->first();
                }
                DB::table('shipping_orders')->insert([
                    'order_id' => $orderRow->id ?? null,
                    'shop_id' => $orderRow->shop_id ?? null,
                    'dsp_order_id' => (string) $dspId,
                    'shipping_status' => $status,
                    'recipient_name' => $orderRow->delivery_name ?? '',
                    'recipient_phone' => $orderRow->delivery_phone ?? '',
                    'recipient_address' => $orderRow->delivery_address ?? '',
                    'latitude' => $orderRow->latitude ?? null,
                    'longitude' => $orderRow->longitude ?? null,
                    'driver_name' => $driverName,
                    'driver_phone' => $driverPhone,
                    'driver_latitude' => $driverLat,
                    'driver_longitude' => $driverLng,
                    'total' => (float) ($orderRow->total ?? 0),
                    'payment_type' => $this->mapPaymentType($orderRow->payment_method ?? null),
                    'notes' => $orderRow->special_instructions ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('shipping_orders')
                    ->where('dsp_order_id', (string) $dspId)
                    ->update(array_filter([
                        'shipping_status' => $status,
                        'driver_name' => $driverName,
                        'driver_phone' => $driverPhone,
                        'driver_latitude' => $driverLat,
                        'driver_longitude' => $driverLng,
                        'updated_at' => now(),
                    ], fn($v) => !is_null($v)));
            }

            // Also update orders table if we can match
            $orderUpdate = array_filter([
                'dsp_order_id' => (string) $dspId,
                'shipping_status' => $status,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'driver_latitude' => $driverLat,
                'driver_longitude' => $driverLng,
                'updated_at' => now(),
            ], fn($v) => !is_null($v));

            if (!empty($clientOrderId)) {
                DB::table('orders')->where('order_number', $clientOrderId)->update($orderUpdate);
            }
            DB::table('orders')->where('dsp_order_id', (string) $dspId)->update($orderUpdate);

            return $data;
        } catch (\Throwable $e) {
            Log::error('Exception while fetching shipping order status', [
                'dsp_order_id' => $shippingOrderId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function handleWebhook(Request $request): void
    {
        try {
            $payload = $request->all();
            $dspOrderId = $payload['dsp_order_id'] ?? $payload['order_id'] ?? $payload['id'] ?? null;
            if (!$dspOrderId) { return; }

            $updates = array_filter([
                'shipping_status' => $payload['status'] ?? null,
                'driver_name' => $payload['driver']['name'] ?? null,
                'driver_phone' => $payload['driver']['phone'] ?? null,
                'driver_latitude' => $payload['driver']['location']['latitude'] ?? null,
                'driver_longitude' => $payload['driver']['location']['longitude'] ?? null,
                'updated_at' => now(),
            ], fn($v) => !is_null($v));

            if (!empty($updates)) {
                DB::table('shipping_orders')->where('dsp_order_id', $dspOrderId)->update($updates);
                // mirror to orders if exists
                DB::table('orders')->where('dsp_order_id', $dspOrderId)->update($updates);
            }
        } catch (\Throwable $e) {
            Log::error('Exception while handling shipping webhook', ['message' => $e->getMessage()]);
        }
    }

    public function cancelOrder(string $shippingOrderId)
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->apiKey) || empty($shippingOrderId)) {
                return false;
            }

            $url = $this->buildUrl($this->endpoints['cancel'], ['id' => $shippingOrderId]);

            $request = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            $response = match ($this->cancelMethod) {
                'post' => $request->post($url),
                default => $request->delete($url),
            };

            if ($response && ($response->status() === 202 || $response->successful())) {
                DB::table('shipping_orders')
                    ->where('dsp_order_id', (string) $shippingOrderId)
                    ->update([
                        'shipping_status' => 'cancelled',
                        'updated_at' => now(),
                    ]);
                DB::table('orders')
                    ->where('dsp_order_id', (string) $shippingOrderId)
                    ->update([
                        'shipping_status' => 'cancelled',
                        'updated_at' => now(),
                    ]);
                return true;
            }

            // Handle specific error cases
            if ($response) {
                $responseData = $response->json();
                $message = $responseData['message'] ?? 'Cancellation failed';

                // Check for "already in transit" or similar messages
                if (str_contains(strtolower($message), 'in transit') ||
                    str_contains(strtolower($message), 'picked') ||
                    str_contains(strtolower($message), 'cannot cancel')) {

                    Log::info('Order cancellation rejected - already in transit', [
                        'dsp_order_id' => $shippingOrderId,
                        'message' => $message,
                    ]);

                    return [
                        'status_code' => $response->status(),
                        'message' => $message,
                        'error_type' => 'already_in_transit',
                        'provider_response' => $responseData
                    ];
                }

                return [
                    'status_code' => $response->status(),
                    'message' => $message,
                    'error_type' => 'cancellation_failed',
                    'provider_response' => $responseData
                ];
            }

            Log::warning('Cancel shipping order failed', [
                'dsp_order_id' => $shippingOrderId,
                'status' => $response ? $response->status() : null,
                'body' => $response ? $response->body() : null,
                'url' => $url,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Exception while cancelling shipping order', [
                'dsp_order_id' => $shippingOrderId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function mapPaymentType($paymentMethod): int
    {
        $normalized = is_string($paymentMethod) ? strtolower($paymentMethod) : $paymentMethod;
        if ($normalized === 'cash' || $normalized === 1) { return 1; }
        if ($normalized === 'machine' || $normalized === 10) { return 10; }
        return 0;
    }

    protected function buildUrl(string $endpointTemplate, array $params = []): string
    {
        $path = $endpointTemplate;
        foreach ($params as $key => $value) { $path = str_replace('{' . $key . '}', urlencode($value), $path); }
        return rtrim($this->apiBaseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) { $result += $this->flattenArray($value, $newKey); }
            else { $result[$newKey] = $value; }
        }
        return $result;
    }
}


