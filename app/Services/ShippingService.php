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
        try {
            if (empty($this->apiBaseUrl) || empty($this->apiKey)) {
                Log::error('❌ Shipping API credentials missing!', [
                    'api_url' => $this->apiBaseUrl ?: 'NOT_SET',
                    'api_key_exists' => !empty($this->apiKey),
                    'message' => 'Please check SHIPPING_API_URL and SHIPPING_API_KEY in .env file',
                ]);
                return null;
            }

            $orderObj = is_array($order) ? (object) $order : $order;
            $orderIdString = (string) ($orderObj->order_number ?? $orderObj->id ?? '');
            $shopIdString = isset($orderObj->shop_id) ? (string) $orderObj->shop_id : null;

            Log::info('🚀 Starting shipping order creation', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
                'customer_name' => $orderObj->delivery_name ?? null,
                'total' => $orderObj->total ?? null,
            ]);

            if ($orderIdString === '' || empty($shopIdString)) {
                Log::error('❌ Shipping order creation aborted', [
                    'reason' => 'Missing order_id or shop_id',
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderIdString ?: 'EMPTY',
                    'shop_id' => $shopIdString ?: 'EMPTY',
                    'message' => 'Order must have order_number and shop_id',
                ]);
                return null;
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

            $payload = [
                'id' => $orderIdString,
                'shop_id' => $shopIdString,
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

            $url = $this->buildUrl($this->endpoints['create']);

            // Log the request details
            Log::info('📤 Sending order to shipping company', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
                'url' => $url,
                'payload' => $payload,
            ]);

            $request = Http::timeout(30)
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
                    'order_number' => $orderIdString,
                    'shop_id' => $shopIdString,
                    'http_status' => $statusCode,
                    'url' => $url,
                    'error_message' => $responseJson['message'] ?? $responseJson['error'] ?? 'Unknown error',
                    'errors' => $responseJson['errors'] ?? null,
                    'full_response_body' => $responseBody,
                    'full_response_json' => $responseJson,
                    'request_payload' => $payload,
                ]);

                // More detailed error logging
                if ($statusCode === 422) {
                    Log::error('🔴 Validation Error (422) - Details:', [
                        'validation_errors' => $responseJson['errors'] ?? 'No specific errors provided',
                        'message' => $responseJson['message'] ?? 'Validation failed',
                    ]);
                } elseif ($statusCode === 401) {
                    Log::error('🔴 Authentication Error (401) - Invalid API Token');
                } elseif ($statusCode === 404) {
                    Log::error('🔴 Not Found (404) - Invalid endpoint or order not found');
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
        } catch (\Throwable $e) {
            Log::error('💥 Exception during shipping order creation', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString ?? 'UNKNOWN',
                'shop_id' => $shopIdString ?? 'UNKNOWN',
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


