<?php

namespace App\Services;

use App\Contracts\ShippingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Leajlak Shipping Service
 * 
 * This service handles all interactions with Leajlak shipping provider API.
 * This is the original shipping service - do not modify logic to maintain compatibility.
 */
class ShippingService implements ShippingServiceInterface
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

    public function createOrder($order): ?array
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

            // Get shop_id - PRIORITY: Use shop_id from order first (especially for Zyda orders with fixed shop_id = 210)
            // Only fallback to restaurant.shop_id if order doesn't have shop_id
            $shopIdString = null;
            $shopIdSource = 'unknown';

            // PRIORITY 1: Use shop_id from order (this is correct for Zyda orders with fixed shop_id = 210)
            if (!empty($orderObj->shop_id)) {
                $shopIdString = (string) $orderObj->shop_id;
                $shopIdSource = 'order.shop_id';
                Log::info('✅ Using shop_id from order (PRIORITY)', [
                    'order_id' => $orderObj->id ?? null,
                    'order_shop_id' => $shopIdString,
                    'source' => $orderObj->source ?? 'unknown',
                    'note' => 'Using order.shop_id (especially important for Zyda orders with fixed shop_id = 210)',
                ]);
            }
            // PRIORITY 2: Fallback to restaurant.shop_id only if order doesn't have shop_id
            elseif (isset($orderObj->restaurant_id)) {
                try {
                    $restaurant = \App\Models\Restaurant::find($orderObj->restaurant_id);
                    if ($restaurant) {
                        if (!empty($restaurant->shop_id)) {
                            $shopIdString = (string) $restaurant->shop_id;
                            $shopIdSource = 'restaurant.shop_id (fallback)';
                            Log::info('✅ Using shop_id from restaurant (fallback)', [
                                'restaurant_id' => $orderObj->restaurant_id,
                                'restaurant_name' => $restaurant->name ?? 'N/A',
                                'shop_id' => $shopIdString,
                                'shop_id_type' => gettype($shopIdString),
                            ]);
                        } else {
                            Log::warning('⚠️ Restaurant exists but shop_id is empty', [
                                'restaurant_id' => $orderObj->restaurant_id,
                                'restaurant_name' => $restaurant->name ?? 'N/A',
                                'order_shop_id' => $orderObj->shop_id ?? 'MISSING',
                            ]);
                        }
                    } else {
                        Log::warning('⚠️ Restaurant not found', [
                            'restaurant_id' => $orderObj->restaurant_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error fetching restaurant for shop_id', [
                        'restaurant_id' => $orderObj->restaurant_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Final fallback to default shop_id (should not happen if data is correct)
            if (empty($shopIdString)) {
                $shopIdString = '210'; // Default fallback (Gather Us)
                $shopIdSource = 'default_fallback';
                Log::error('❌ CRITICAL: Using default shop_id (restaurant and order shop_id not found)', [
                    'shop_id' => $shopIdString,
                    'order_id' => $orderObj->id ?? null,
                    'restaurant_id' => $orderObj->restaurant_id ?? null,
                    'order_shop_id' => $orderObj->shop_id ?? 'MISSING',
                    'message' => 'This should not happen! Please ensure restaurant has shop_id set in database.',
                ]);
            }

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

            // Build delivery_details - ensure all required fields are present
            $deliveryDetails = [
                'name' => $orderObj->delivery_name ?? '',
                'phone' => $uniquePhone ?? '',
                    'email' => $uniqueEmail,
            ];

            // Only add coordinate if it's valid
            if (!is_null($coordinate)) {
                $deliveryDetails['coordinate'] = $coordinate;
            }

            // Add address if available
            if (!empty($orderObj->delivery_address)) {
                $deliveryDetails['address'] = $orderObj->delivery_address;
            }

            // Build order details
            $orderDetails = [
                    'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                    'total' => (float) ($orderObj->total ?? 0),
            ];

            // Add notes if available
            if (!empty($orderObj->special_instructions)) {
                $orderDetails['notes'] = $orderObj->special_instructions;
            }

            $payload = [
                'id' => $orderIdString, // This is the order_number (e.g., ORD-20251104-D80175) - NOT the database id
                'shop_id' => $shopIdForApi,
                'delivery_details' => $deliveryDetails,
                'order' => $orderDetails,
            ];

            // Log payload before sending to help debug
            Log::info('📋 Final payload being sent to shipping company', [
                'payload' => $payload,
                'has_coordinate' => !is_null($coordinate),
                'coordinate' => $coordinate,
                'delivery_name' => $deliveryDetails['name'],
                'delivery_phone' => $deliveryDetails['phone'],
                'delivery_address' => $deliveryDetails['address'] ?? 'NOT SET',
            ]);

            Log::info('📋 Shipping payload prepared with order_number (NOT id)', [
                'order_id' => $orderObj->id ?? null,
                'order_number' => $orderObj->order_number ?? 'MISSING',
                'payload_id_field' => $payload['id'], // This is the order_number (e.g., ORD-20251104-D80175)
                'payload_id_type' => gettype($payload['id']),
                'confirmation' => 'Payload uses order_number, NOT database id',
                'shop_id' => $shopIdForApi,
                'customer_name' => $orderObj->delivery_name ?? null,
                'total' => $payload['order']['total'],
            ]);

            $url = $this->buildUrl($this->endpoints['create']);

            // Log the request details with emphasis on order_number (NOT id)
            Log::info('📤 Sending order to shipping company (using order_number ONLY)', [
                'order_id' => $orderObj->id ?? null, // Internal database id (for reference only, NOT sent)
                'order_number' => $orderObj->order_number ?? 'MISSING', // This is what we send
                'order_number_sent_in_payload' => $payload['id'], // This is order_number (e.g., ORD-20251104-D80175)
                'confirmation' => 'Shipping company will receive order_number, NOT database id',
                'shop_id' => $shopIdForApi,
                'shop_id_original' => $shopIdString,
                'url' => $url,
                'api_base_url' => $this->apiBaseUrl,
                'api_key_exists' => !empty($this->apiKey),
                'api_key_length' => strlen($this->apiKey ?? ''),
                'api_key_prefix' => substr($this->apiKey ?? '', 0, 10) . '...',
                'payload' => $payload,
                'payload_id_field_value' => $payload['id'], // This is order_number (ORD-20251104-D80175)
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

            $response = null;
            $responseBody = null;
            $responseJson = null;
            
            try {
                Log::info('🔄 Attempting to send request to shipping company', [
                    'order_id' => $orderObj->id ?? null,
                    'order_number' => $orderObj->order_number ?? 'MISSING',
                    'url' => $url,
                    'method' => $this->sendAsForm ? 'form' : 'json',
                ]);
                
                $response = $this->sendAsForm
                    ? $request->asForm()->post($url, $this->flattenArray($payload))
                    : $request->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);

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
                                '3' => 'Ensure shop_id format is correct (should be string like "210", "211", "212")',
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
                            'shop_id' => $shopIdForApi,
                            'delivery_name' => $payload['delivery_details']['name'] ?? 'MISSING',
                            'delivery_phone' => $payload['delivery_details']['phone'] ?? 'MISSING',
                            'delivery_address' => $payload['delivery_details']['address'] ?? 'MISSING',
                            'has_coordinate' => isset($payload['delivery_details']['coordinate']),
                            'coordinate' => $payload['delivery_details']['coordinate'] ?? 'MISSING',
                            'total' => $payload['order']['total'] ?? 'MISSING',
                            'payment_type' => $payload['order']['payment_type'] ?? 'MISSING',
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


