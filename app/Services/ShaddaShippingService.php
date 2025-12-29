<?php

namespace App\Services;

use App\Contracts\ShippingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shadda Shipping Service
 *
 * This service handles all interactions with Shadda shipping provider API.
 * It is completely separate from the leajlak ShippingService to allow easy removal if needed.
 */
class ShaddaShippingService implements ShippingServiceInterface
{
    protected $apiBaseUrl;
    protected $clientId;
    protected $secretKey;
    protected $endpoints;
    protected $webhookSecret;

    public function __construct()
    {
        $this->apiBaseUrl = Config::get('services.shadda.url');
        $this->clientId = Config::get('services.shadda.client_id');
        $this->secretKey = Config::get('services.shadda.secret_key');
        $this->webhookSecret = Config::get('services.shadda.webhook_secret');
        $this->endpoints = Config::get('services.shadda.endpoints', [
            'create' => '/CreateOrder',
            'status' => '/GetOrder/{orderId}',
            'cancel' => '/CancelOrder',
            'branches' => '/GetIntegratedBranches', // Get list of integrated branches
        ]);
    }

    public function createOrder($order): ?array
    {
        Log::info('📦 SHADDASHIPPINGSERVICE::createOrder CALLED', [
            'order_type' => gettype($order),
            'order_id' => is_object($order) ? ($order->id ?? 'N/A') : (is_array($order) ? ($order['id'] ?? 'N/A') : 'N/A'),
            'environment' => config('app.env'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            Log::info('🔍 STEP 1: Checking Shadda API credentials', [
                'api_url_exists' => !empty($this->apiBaseUrl),
                'api_url' => $this->apiBaseUrl ?: 'NOT_SET',
                'client_id_exists' => !empty($this->clientId),
                'secret_key_exists' => !empty($this->secretKey),
                'client_id_length' => strlen($this->clientId ?? ''),
                'secret_key_length' => strlen($this->secretKey ?? ''),
            ]);

            if (empty($this->apiBaseUrl) || empty($this->clientId) || empty($this->secretKey)) {
                Log::error('❌ Shadda API credentials missing!', [
                    'api_url' => $this->apiBaseUrl ?: 'NOT_SET',
                    'client_id_exists' => !empty($this->clientId),
                    'secret_key_exists' => !empty($this->secretKey),
                    'message' => 'Please check SHADDA_API_URL, SHADDA_CLIENT_ID, and SHADDA_SECRET_KEY in .env file',
                    'environment' => config('app.env'),
                ]);
                return null;
            }

            Log::info('✅ API credentials OK');

            $orderObj = is_array($order) ? (object) $order : $order;

            // IMPORTANT: We MUST use order_number (e.g., ORD-20251104-D80175) NOT the internal id
            // The shipping company expects order_number, not the database id
            if (empty($orderObj->order_number)) {
                Log::error('❌ CRITICAL: order_number is missing! Cannot send to shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number_exists' => false,
                    'order_object_keys' => is_object($orderObj) ? array_keys(get_object_vars($orderObj)) : 'N/A',
                    'message' => 'Order must have order_number (e.g., ORD-20251104-D80175) to send to shipping company. Internal id cannot be used.',
                ]);
                return null;
            }

            // Use ONLY order_number, never use id as fallback
            $orderIdString = (string) $orderObj->order_number;

            Log::info('🔍 Order identifier for shipping (using order_number ONLY)', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'order_number_type' => gettype($orderIdString),
                'confirmation' => 'Using order_number (NOT id) for shipping company',
            ]);

            // For Shadda, use shop_id from order as branchId
            // Get shop_id from order, or from restaurant if not set in order
            $shopIdString = $orderObj->shop_id ?? null;
            if (empty($shopIdString)) {
                // Fallback: get shop_id from restaurant
                $restaurant = is_object($orderObj) && isset($orderObj->restaurant_id)
                    ? \App\Models\Restaurant::find($orderObj->restaurant_id)
                    : null;
                $shopIdString = $restaurant?->shop_id ?? null;
                $shopIdSource = 'restaurant_shop_id';
            } else {
                $shopIdSource = 'order_shop_id';
            }

            // If still empty, use default 116 (fallback)
            if (empty($shopIdString)) {
                $shopIdString = '116';
                $shopIdSource = 'default_fallback';
                Log::warning('⚠️ Using default branchId 116 (shop_id not found in order or restaurant)', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderIdString,
                ]);
            }

            Log::info('✅ Using shop_id as Shadda branchId', [
                'order_id' => $orderObj->id ?? null,
                'branchId' => $shopIdString,
                'source' => $shopIdSource,
                'order_shop_id' => $orderObj->shop_id ?? 'NULL',
                'restaurant_id' => $orderObj->restaurant_id ?? 'NULL',
                'note' => 'shop_id from order/restaurant is used as branchId for Shadda API',
            ]);

            Log::info('🚀 Starting shipping order creation', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
                'shop_id_type' => gettype($shopIdString),
                'shop_id_source' => $shopIdSource,
                'restaurant_id' => $orderObj->restaurant_id ?? null,
                'order_shop_id' => $orderObj->shop_id ?? 'MISSING',
                'customer_name' => $orderObj->delivery_name ?? null,
                'total' => $orderObj->total ?? null,
            ]);

            // Double-check: order_number must exist (we already checked above, but this is a safety check)
            if (empty($orderIdString) || empty($orderObj->order_number)) {
                Log::error('❌ Shipping order creation aborted - Missing order_number (CRITICAL)', [
                    'reason' => 'order_number is required but is empty or missing',
                    'order_id' => $orderObj->id ?? null,
                    'order_number_exists' => !empty($orderObj->order_number ?? null),
                    'order_number_value' => $orderObj->order_number ?? 'MISSING',
                    'order_id_string' => $orderIdString ?: 'EMPTY',
                    'restaurant_id' => $orderObj->restaurant_id ?? null,
                    'message' => 'Order MUST have order_number (e.g., ORD-20251104-D80175) to send to shipping company. Internal database id cannot be used.',
                    'note' => 'We ONLY use order_number, never the internal id field',
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

            // Use customer_latitude and customer_longitude from order
            // These are the coordinates set by the customer when they select their location
            $latitude = isset($orderObj->customer_latitude) && !empty($orderObj->customer_latitude)
                ? (float) $orderObj->customer_latitude
                : null;
            $longitude = isset($orderObj->customer_longitude) && !empty($orderObj->customer_longitude)
                ? (float) $orderObj->customer_longitude
                : null;

            Log::info('📍 Customer location coordinates', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'customer_latitude' => $latitude,
                'customer_longitude' => $longitude,
                'has_coordinates' => !is_null($latitude) && !is_null($longitude),
                'latitude_type' => gettype($latitude),
                'longitude_type' => gettype($longitude),
            ]);

            // Validate required fields before building payload
            $missingFields = [];
            if (empty($orderObj->delivery_name)) {
                $missingFields[] = 'delivery_name';
            }
            if (empty($orderObj->delivery_phone)) {
                $missingFields[] = 'delivery_phone';
            }
            if (empty($orderObj->delivery_address)) {
                $missingFields[] = 'delivery_address';
            }
            if (is_null($latitude) || is_null($longitude)) {
                $missingFields[] = 'coordinates';
            }

            if (!empty($missingFields)) {
                Log::error('❌ Missing required fields for shipping API', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderIdString,
                    'missing_fields' => $missingFields,
                    'delivery_name' => $orderObj->delivery_name ?? 'MISSING',
                    'delivery_phone' => $orderObj->delivery_phone ?? 'MISSING',
                    'delivery_address' => $orderObj->delivery_address ?? 'MISSING',
                    'customer_latitude' => $orderObj->customer_latitude ?? 'MISSING',
                    'customer_longitude' => $orderObj->customer_longitude ?? 'MISSING',
                ]);

                // Still try to send, but log the warning
            }

            // Use phone number as-is (without appending order ID)
            // Remove any existing suffix if present (from previous orders)
            $uniquePhone = $orderObj->delivery_phone ?? null;
            if ($uniquePhone) {
                // Remove any existing suffix (e.g., #66, #123)
                $uniquePhone = preg_replace('/#\d+$/', '', $uniquePhone);
                // Clean phone number - remove any extra spaces or characters
                $uniquePhone = trim($uniquePhone);
            }

            // Make email unique by appending order ID
            $uniqueEmail = 'order' . ($orderObj->id ?? time()) . '@advfood.local';

            // Payload with order_number as the unique identifier
            // IMPORTANT: The 'id' field MUST contain order_number (e.g., ORD-20251104-D80175)
            // We NEVER use the internal database id - only order_number
            // This is the same order_number that appears in the system and is tracked everywhere

            // Build coordinate object - only include if both lat and lng are available
            // IMPORTANT: Some shipping APIs require coordinates, so we need to ensure they're valid
            $coordinate = null;
            if (!is_null($latitude) && !is_null($longitude) &&
                $latitude >= -90 && $latitude <= 90 &&
                $longitude >= -180 && $longitude <= 180) {
                $coordinate = [
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                ];
            }

            // Build payload according to Shadda API documentation
            // Required fields: branchId, orderId, deliveryPhone, paymentMethod, paymentAmount
            // Optional but recommended: deliveryAddress, deliveryLatitude, deliveryLongitude, pickupDatetime

            // Map payment method to Shadda format (cash, card, swipemachine)
            $paymentMethod = $this->mapPaymentMethodToShadda($orderObj->payment_method ?? null);

            // Build payload in Shadda format (flat structure, not nested)
            $payload = [
                'branchId' => (int) $shopIdForApi, // Must be number
                'orderId' => $orderIdString, // This is the order_number
                'deliveryPhone' => $uniquePhone ?? $orderObj->delivery_phone ?? '',
                'paymentMethod' => $paymentMethod, // Must be 'cash', 'card', or 'swipemachine'
                'paymentAmount' => round((float) ($orderObj->total ?? 0), 2), // Must have max 2 decimals
            ];

            // Add delivery address if available (required if no coordinates)
            if (!empty($orderObj->delivery_address)) {
                $payload['deliveryAddress'] = $orderObj->delivery_address;
            }

            // Add coordinates if available (as strings per Shadda API - required if no address)
            if (!is_null($latitude) && !is_null($longitude)) {
                $payload['deliveryLatitude'] = (string) $latitude;
                $payload['deliveryLongitude'] = (string) $longitude;
            }

            // Add pickup datetime if scheduled (format: YYYY-MM-DD HH:MM:SS)
            if (!empty($orderObj->scheduled_for)) {
                $payload['pickupDatetime'] = date('Y-m-d H:i:s', strtotime($orderObj->scheduled_for));
            }

            // Log payload before sending to help debug
            Log::info('📋 Final payload being sent to Shadda API', [
                'payload' => $payload,
                'has_coordinates' => isset($payload['deliveryLatitude']) && isset($payload['deliveryLongitude']),
                'has_address' => isset($payload['deliveryAddress']),
                'delivery_phone' => $payload['deliveryPhone'],
                'payment_method' => $payload['paymentMethod'],
                'payment_amount' => $payload['paymentAmount'],
            ]);

            Log::info('📋 Shadda payload prepared', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'orderId' => $payload['orderId'],
                'branchId' => $payload['branchId'],
                'shop_id_source' => $shopIdSource,
            ]);

            $url = $this->buildUrl($this->endpoints['create']);

            // Log the request details
            Log::info('📤 Sending order to Shadda API', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'orderId_in_payload' => $payload['orderId'],
                'branchId' => $payload['branchId'],
                'url' => $url,
                'api_base_url' => $this->apiBaseUrl,
            ]);

            $response = null;
            $responseBody = null;
            $responseJson = null;

            try {
                Log::info('🔄 Attempting to send request to Shadda shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'url' => $url,
                    'method' => 'json',
                    'payload_size' => strlen(json_encode($payload)),
                    'payload_keys' => array_keys($payload),
                    'payload_json' => json_encode($payload),
                ]);

                // Build request with proper JSON encoding
                // Use withBody() to explicitly set JSON body
                $jsonPayload = json_encode($payload);
                Log::info('📤 JSON Payload to send', [
                    'payload_length' => strlen($jsonPayload),
                    'payload_preview' => substr($jsonPayload, 0, 200),
                ]);

                $response = Http::timeout(30)
                    ->retry(3, 100)
                    ->withOptions([
                        'verify' => env('SHADDA_API_VERIFY_SSL', true),
                        'http_errors' => false,
                    ])
                    ->withHeaders([
                        'client-id' => $this->clientId,
                        'Authorization' => 'Bearer ' . $this->secretKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->withBody($jsonPayload, 'application/json')
                    ->post($url);

                // Log immediate response details
                $responseBody = $response ? $response->body() : null;
                $responseJson = $response ? $response->json() : null;

                Log::info('📡 Shipping API Response Received (immediate)', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'response_exists' => !is_null($response),
                    'response_type' => gettype($response),
                    'http_status' => $response ? $response->status() : 'NO_RESPONSE',
                    'response_successful' => $response ? $response->successful() : false,
                    'response_failed' => $response ? $response->failed() : true,
                    'response_body_full' => $responseBody, // Log full response body
                    'response_json' => $responseJson, // Log parsed JSON
                    'url' => $url,
                    'payload_sent' => $payload, // Log what was sent
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('🔴 Connection Exception while sending to shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'url' => $url,
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('🔴 Exception while sending to shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'url' => $url,
                    'exception_type' => get_class($e),
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                    'exception_trace' => $e->getTraceAsString(),
                ]);
                return null;
            }

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
                        'shop_id_original' => $shopIdString,
                        'shop_id_type' => gettype($shopIdForApi),
                        'shop_id_source' => $shopIdSource,
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderObj->order_number ?? 'MISSING',
                        'order_number_sent' => $orderIdString, // The order_number sent in payload
                        'restaurant_id' => $orderObj->restaurant_id ?? null,
                        'order_shop_id' => $orderObj->shop_id ?? 'MISSING',
                        'full_response' => $responseJson,
                        'suggestion' => 'Please verify shop_id is correct in restaurant table and registered in shipping company system',
                        'action_required' => 'Check restaurant.shop_id in database and verify it matches shipping company records',
                    ]);

                    // If "Invalid shop" error, log additional details
                    if (isset($responseJson['message']) && stripos($responseJson['message'], 'shop') !== false) {
                        Log::error('🚨 CRITICAL: SHOP_ID VALIDATION ISSUE - "Invalid shop" error', [
                            'shop_id_used' => $shopIdForApi,
                            'shop_id_original' => $shopIdString,
                            'shop_id_source' => $shopIdSource,
                            'restaurant_id' => $orderObj->restaurant_id ?? null,
                            'order_shop_id' => $orderObj->shop_id ?? 'MISSING',
                            'order_number' => $orderIdString,
                            'order_id' => $orderObj->id ?? null,
                            'action_required' => [
                                '1' => 'Verify shop_id in restaurant table matches shipping company records',
                                '2' => 'Check if shop_id is registered in shipping company dashboard',
                                '3' => 'Ensure shop_id format is correct (should be string like "11183", "11184", "11185")',
                                '4' => 'Verify restaurant name and shop_id mapping in shipping company system',
                            ],
                            'current_shop_id_source' => $shopIdSource,
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
                    Log::error('🔴 Server Error (500) - Shipping company server error', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderObj->order_number ?? 'MISSING',
                        'full_response' => $responseJson,
                    ]);
                } else {
                    Log::error('🔴 Unknown HTTP Error Status', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderObj->order_number ?? 'MISSING',
                        'http_status' => $statusCode,
                        'full_response' => $responseJson,
                        'response_body' => $responseBody,
                    ]);
                }

                // CRITICAL: Log detailed error before returning null
                Log::error('🛑 ShippingService::createOrder() returning NULL due to failed response', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'http_status' => $statusCode,
                    'reason' => 'HTTP request failed or returned error status',
                    'url' => $url,
                    'response_body' => $responseBody,
                    'response_json' => $responseJson,
                    'payload_sent' => $payload,
                    'shop_id_used' => $shopIdForApi,
                    'delivery_details' => $payload['delivery_details'] ?? 'MISSING',
                    'order_details' => $payload['order'] ?? 'MISSING',
                ]);

                // Log the specific validation errors from shipping company for 422 errors
                if ($statusCode === 422) {
                    Log::error('🔴 VALIDATION ERROR (422) FROM SHIPPING COMPANY:', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderIdString,
                        'shop_id' => $shopIdForApi,
                        'errors' => $responseJson['errors'] ?? 'No errors array provided',
                        'message' => $responseJson['message'] ?? 'Validation failed',
                        'full_response' => $responseJson,
                        'payload_sent' => $payload,
                        'check_fields' => [
                            'branchId' => $payload['branchId'] ?? 'MISSING',
                            'orderId' => $payload['orderId'] ?? 'MISSING',
                            'deliveryPhone' => $payload['deliveryPhone'] ?? 'MISSING',
                            'deliveryAddress' => $payload['deliveryAddress'] ?? 'MISSING',
                            'has_coordinates' => isset($payload['deliveryLatitude']) && isset($payload['deliveryLongitude']),
                            'paymentMethod' => $payload['paymentMethod'] ?? 'MISSING',
                            'paymentAmount' => $payload['paymentAmount'] ?? 'MISSING',
                        ],
                        'diagnosis' => 'Check the errors array above to see which fields are invalid',
                    ]);
                } elseif ($statusCode === 401) {
                    Log::error('🔴 AUTHENTICATION ERROR (401) - Invalid API Key', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderIdString,
                        'api_key_length' => strlen($this->apiKey ?? ''),
                        'api_key_prefix' => substr($this->apiKey ?? '', 0, 20) . '...',
                    ]);
                } elseif ($statusCode === 404) {
                    Log::error('🔴 NOT FOUND (404) - Invalid endpoint', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderIdString,
                        'url' => $url,
                        'endpoint' => $this->endpoints['create'],
                    ]);
                } else {
                    Log::error('🔴 UNKNOWN ERROR from shipping company', [
                        'order_id' => $orderObj->id ?? null,
                        'order_number' => $orderIdString,
                        'http_status' => $statusCode,
                        'response_body' => $responseBody,
                        'response_json' => $responseJson,
                    ]);
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

            // Shadda API returns: {"data": "The operation completed successfully"}
            // We use the orderId we sent as the dsp_order_id
            $responseData = $data['data'] ?? $data;
            $dspOrderId = $payload['orderId']; // Use the orderId we sent to Shadda
            $shippingStatus = 'New Order'; // Default status (10 = New per Shadda docs)

            // After successful order creation, call Get Order Status to get initial driver info
            // This ensures we have the latest status and driver information
            try {
                Log::info('🔄 Calling Get Order Status after order creation', [
                    'dsp_order_id' => $dspOrderId,
                ]);
                // Call after a short delay to allow Shadda to process the order
                sleep(1);
                $this->getOrderStatus($dspOrderId);
            } catch (\Exception $e) {
                Log::warning('⚠️ Failed to call Get Order Status after order creation', [
                    'dsp_order_id' => $dspOrderId,
                    'error' => $e->getMessage(),
                    'note' => 'This is not critical - webhook will update driver info when available',
                ]);
            }

            // Only insert into shipping_orders table if order_id exists (Order is already in database)
            // For temporary orders (not yet saved), shipping_orders will be inserted when Order is saved
            if (!empty($orderObj->id)) {
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

                // Try to insert into shipping_orders table
                try {
                    DB::table('shipping_orders')->insert($row);
                    Log::info('✅ Shipping order saved to shipping_orders table', [
                        'order_id' => $orderObj->id ?? null,
                        'dsp_order_id' => $dspOrderId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Failed to insert into shipping_orders table (may already exist)', [
                        'order_id' => $orderObj->id ?? null,
                        'dsp_order_id' => $dspOrderId,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('ℹ️ Skipping shipping_orders insert - Order not yet in database (temporary order)', [
                    'order_number' => $orderIdString,
                    'dsp_order_id' => $dspOrderId,
                    'note' => 'shipping_orders will be inserted when Order is saved to database',
                ]);
            }

            // Log final success with all details
            Log::info('🎉 Order successfully sent to shipping company!', [
                'order_id' => $orderObj->id ?? null,
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
                'note' => 'Returning dsp_order_id to Order Model boot method for saving',
            ]);

            // IMPORTANT: Return array with dsp_order_id so Order Model boot method can save it
            // This ensures dsp_order_id is saved in orders table
            return [
                'dsp_order_id' => $dspOrderId,
                'shipping_status' => $shippingStatus,
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderIdString,
                'shop_id' => $shopIdString,
            ];
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
            Log::error('🛑 ShippingService::createOrder() returning NULL due to ConnectionException', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'UNKNOWN',
                'exception_type' => 'ConnectionException',
                'exception_message' => $e->getMessage(),
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
            Log::error('🛑 ShippingService::createOrder() returning NULL due to Exception', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'UNKNOWN',
                'exception_type' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);
            return null;
        }
    }

    public function getOrderStatus(string $shippingOrderId): ?array
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->clientId) || empty($this->secretKey) || empty($shippingOrderId)) {
                return null;
            }

            // Use GetOrder endpoint with orderId parameter
            $url = $this->buildUrl($this->endpoints['status'], ['orderId' => $shippingOrderId]);

            Log::info('📡 Calling Shadda GetOrder API', [
                'dsp_order_id' => $shippingOrderId,
                'url' => $url,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'client-id' => $this->clientId,
                    'Authorization' => 'Bearer ' . $this->secretKey,
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

            // Log full response for debugging
            Log::info('📡 Shadda GetOrder API Response', [
                'dsp_order_id' => $shippingOrderId,
                'full_response' => $data,
                'response_structure' => [
                    'has_data_key' => isset($data['data']),
                    'data_type' => isset($data['data']) ? gettype($data['data']) : 'N/A',
                    'top_level_keys' => array_keys($data),
                ],
            ]);

            $responseData = $data['data'] ?? $data;

            // Extract order ID - Shadda returns the orderId we sent
            $clientOrderId = $shippingOrderId; // The orderId we sent is what we get back
            $statusCode = $responseData['statusId'] ?? null; // statusId is the numeric status
            $statusDesc = $responseData['statusDesc'] ?? null; // statusDesc is the text description
            $status = $statusDesc ?? $this->mapStatusCodeToText($statusCode);
            $dspId = $shippingOrderId; // Use the orderId we sent

            // Extract driver information from Shadda API response format
            // According to docs: driverName, driverMobile, driverLatitude, driverLongitude
            $driverName = $responseData['driverName'] ?? null;
            $driverPhone = $responseData['driverMobile'] ?? null;
            $driverLat = $responseData['driverLatitude'] ?? null;
            $driverLng = $responseData['driverLongitude'] ?? null;

            // Log driver extraction
            Log::info('🔍 Extracting driver information from GetOrder response', [
                'dsp_order_id' => $shippingOrderId,
                'driverName' => $driverName,
                'driverMobile' => $driverPhone,
                'driverLatitude' => $driverLat,
                'driverLongitude' => $driverLng,
                'statusId' => $statusCode,
                'statusDesc' => $statusDesc,
            ]);

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
                    'driver_latitude' => $driverLat ? (float) $driverLat : null,
                    'driver_longitude' => $driverLng ? (float) $driverLng : null,
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
                        'driver_latitude' => $driverLat ? (float) $driverLat : null,
                        'driver_longitude' => $driverLng ? (float) $driverLng : null,
                        'updated_at' => now(),
                    ], fn($v) => !is_null($v)));
            }

            // Also update orders table if we can match
            $orderUpdate = array_filter([
                'dsp_order_id' => (string) $dspId,
                'shipping_status' => $status,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'driver_latitude' => $driverLat ? (float) $driverLat : null,
                'driver_longitude' => $driverLng ? (float) $driverLng : null,
                'updated_at' => now(),
                'shipping_provider' => 'shadda',
            ], fn($v) => !is_null($v));

            if (!empty($clientOrderId)) {
                DB::table('orders')->where('order_number', $clientOrderId)->update($orderUpdate);
            }
            DB::table('orders')->where('dsp_order_id', (string) $dspId)->update($orderUpdate);

            Log::info('✅ Order and driver information updated from GetOrder API', [
                'dsp_order_id' => $dspId,
                'status' => $status,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
            ]);

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
            // Verify webhook secret if configured
            if (!empty($this->webhookSecret)) {
                $signature = $request->header('X-Shadda-Signature') ?? $request->header('Signature');
                // Add signature verification logic here if needed
            }

            $payload = $request->all();

            // Log full webhook payload for debugging
            Log::info('📥 Shadda Webhook Received', [
                'full_payload' => $payload,
                'payload_structure' => [
                    'has_data_key' => isset($payload['data']),
                    'top_level_keys' => array_keys($payload),
                ],
            ]);

            $responseData = $payload['data'] ?? $payload;

            // Extract order ID - Shadda may send orderId (the one we sent) or a different ID
            $dspOrderId = $responseData['orderId'] ?? $responseData['order_id'] ?? $responseData['id'] ?? $payload['orderId'] ?? $payload['order_id'] ?? $payload['id'] ?? null;
            if (!$dspOrderId) {
                Log::warning('Shadda webhook received without order ID', ['payload' => $payload]);
                return;
            }

            $statusCode = $responseData['status'] ?? $responseData['statusCode'] ?? $payload['status'] ?? null;
            $status = $this->mapStatusCodeToText($statusCode);

            // Extract driver information - try multiple possible formats
            $driver = $responseData['driver'] ?? $payload['driver'] ?? null;

            Log::info('🔍 Extracting driver from webhook', [
                'dsp_order_id' => $dspOrderId,
                'driver_found' => !is_null($driver),
                'driver_type' => gettype($driver),
                'driver_keys' => is_array($driver) ? array_keys($driver) : 'N/A',
            ]);

            $driverName = null;
            $driverPhone = null;
            $driverLat = null;
            $driverLng = null;

            if (is_array($driver)) {
                $driverName = $driver['name'] ?? $driver['driverName'] ?? null;
                $driverPhone = $driver['phone'] ?? $driver['driverPhone'] ?? $driver['mobile'] ?? null;
                $driverLat = $driver['latitude'] ?? $driver['location']['latitude'] ?? $driver['lat'] ?? null;
                $driverLng = $driver['longitude'] ?? $driver['location']['longitude'] ?? $driver['lng'] ?? $driver['lon'] ?? null;
            }

            $updates = array_filter([
                'shipping_status' => $status,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'driver_latitude' => $driverLat,
                'driver_longitude' => $driverLng,
                'updated_at' => now(),
            ], fn($v) => !is_null($v));

            if (!empty($updates)) {
                DB::table('shipping_orders')->where('dsp_order_id', $dspOrderId)->update($updates);
                // mirror to orders if exists
                DB::table('orders')->where('dsp_order_id', $dspOrderId)->update($updates);

                Log::info('✅ Shadda webhook updates applied', [
                    'dsp_order_id' => $dspOrderId,
                    'updates_count' => count($updates),
                    'updates' => $updates,
                ]);
            } else {
                Log::warning('⚠️ Shadda webhook received but no updates to apply', [
                    'dsp_order_id' => $dspOrderId,
                    'status_code' => $statusCode,
                    'driver_found' => !is_null($driver),
                ]);
            }

            // Also call Get Order Status API to get latest information (including driver)
            // This ensures we have the most up-to-date data even if webhook format is different
            try {
                Log::info('🔄 Calling Get Order Status API after webhook', [
                    'dsp_order_id' => $dspOrderId,
                ]);
                $this->getOrderStatus($dspOrderId);
            } catch (\Exception $e) {
                Log::warning('⚠️ Failed to call Get Order Status after webhook', [
                    'dsp_order_id' => $dspOrderId,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('✅ Shadda webhook processed successfully', [
                'dsp_order_id' => $dspOrderId,
                'status' => $status,
                'status_code' => $statusCode,
                'updates_applied' => !empty($updates),
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'updates' => $updates,
            ]);
        } catch (\Throwable $e) {
            Log::error('Exception while handling Shadda webhook', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);
        }
    }

    public function cancelOrder(string $shippingOrderId)
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->clientId) || empty($this->secretKey) || empty($shippingOrderId)) {
                return false;
            }

            $url = $this->buildUrl($this->endpoints['cancel'], ['id' => $shippingOrderId]);

            $request = Http::timeout(30)->withHeaders([
                'client-id' => $this->clientId,
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // Shadda uses POST for cancellation
            $response = $request->post($url);

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

    /**
     * Map payment method to Shadda payment type
     */
    protected function mapPaymentType($paymentMethod): int
    {
        $normalized = is_string($paymentMethod) ? strtolower($paymentMethod) : $paymentMethod;
        if ($normalized === 'cash' || $normalized === 1) { return 1; }
        if ($normalized === 'card' || $normalized === 'online' || $normalized === 10) { return 10; }
        return 0;
    }

    /**
     * Map payment method to Shadda format
     * Shadda accepts: 'cash', 'card', or 'swipemachine'
     *
     * NOTE: Always returns 'card' as per user requirement
     */
    protected function mapPaymentMethodToShadda($paymentMethod): string
    {
        // Always return 'card' for all orders sent to Shadda (as per user requirement)
        return 'card';
    }

    /**
     * Map Shadda status code to text status
     * According to Shadda documentation:
     * 10 = New, 1 = Accepted, 2 = On the way to pickup, 3 = Reach pickup,
     * 4 = On the way to delivery, 5 = Arrived to delivery, 6 = Completed, 7 = Canceled
     */
    protected function mapStatusCodeToText($statusCode): ?string
    {
        if ($statusCode === null) {
            return null;
        }

        $statusMap = [
            10 => 'New',
            1 => 'Accepted',
            2 => 'On the way to the pickup location',
            3 => 'Reach pickup location',
            4 => 'On the way to the delivery location',
            5 => 'Arrived to the delivery location',
            6 => 'Completed',
            7 => 'Canceled',
        ];

        // If it's already a string, return as is
        if (is_string($statusCode)) {
            return $statusCode;
        }

        return $statusMap[$statusCode] ?? 'Unknown';
    }

    protected function buildUrl(string $endpointTemplate, array $params = []): string
    {
        $path = $endpointTemplate;
        foreach ($params as $key => $value) { $path = str_replace('{' . $key . '}', urlencode($value), $path); }
        return rtrim($this->apiBaseUrl, '/') . '/' . ltrim($path, '/');
    }

}


