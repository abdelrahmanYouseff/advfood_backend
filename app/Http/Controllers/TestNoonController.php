<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestNoonController extends Controller
{
    public function createPayment()
    {
        // التحقق من وجود متغيرات البيئة
        if (!config('noon.api_key') || !config('noon.api_url')) {
            Log::error('Noon API credentials not configured');
            return response()->json([
                'error' => 'Payment configuration error',
                'message' => 'API credentials not configured'
            ], 500);
        }

        // إعداد البيانات للطلب - تنسيق JSON صحيح
        $requestData = [
            "apiOperation" => "INITIATE",
            "order" => [
                "amount" => 1,
                "currency" => "SAR",
                "reference" => "ORDER-" . now()->timestamp,
                "name" => "Payment order",
                "category" => "pay"  // Category "pay" as per Noon configuration for adv_food
            ],
            "configuration" => [
                "returnUrl" => config('noon.success_url', route('payment.success')),
                "paymentAction" => "SALE"
            ]
        ];

        // إنشاء Authorization header حسب وثائق نون
        $businessId = config('noon.business_id', 'adv_food');
        $applicationId = config('noon.application_id', 'adv-food');
        $apiKey = config('noon.api_key');
        $authString = base64_encode($businessId . '.' . $applicationId . ':' . $apiKey);

        // تسجيل البيانات المرسلة للتتبع
        Log::info('Noon Payment Request', [
            'url' => 'https://api-test.sa.noonpayments.com/payment/v1/order',
            'business_id' => $businessId,
            'application_id' => $applicationId,
            'api_key' => $apiKey ? 'SET' : 'NOT SET',
            'auth_string' => $authString,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $authString
            ],
            'data' => $requestData
        ]);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Key ' . $authString,
            ];

            Log::info('Sending request to Noon', [
                'url' => 'https://api-test.sa.noonpayments.com/payment/v1/order',
                'headers' => $headers,
                'auth_string' => $authString,
                'request_data' => $requestData
            ]);

            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders($headers)
                ->post('https://api-test.sa.noonpayments.com/payment/v1/order', $requestData);

            // تسجيل الاستجابة
            Log::info('Noon Payment Response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'failed' => $response->failed()
            ]);

            if ($response->failed()) {
                Log::error('Noon Payment Failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'request_data' => $requestData
                ]);

                return response()->json([
                    'error' => 'Payment request failed',
                    'status_code' => $response->status(),
                    'details' => $response->json(),
                    'request_data' => $requestData // للتتبع
                ], $response->status());
            }

            $data = $response->json();

            // التحقق من نجاح العملية والحصول على رابط الدفع
            if (!isset($data['result']['checkoutData']['postUrl'])) {
                Log::error('Noon Payment: checkout URL missing', ['response' => $data]);
                return response()->json([
                    'error' => 'Invalid response from payment gateway',
                    'details' => $data
                ], 500);
            }

            $checkoutUrl = $data['result']['checkoutData']['postUrl'];
            Log::info('Noon Payment Success', [
                'checkout_url' => $checkoutUrl,
                'order_id' => $data['result']['order']['id'] ?? null,
                'order_status' => $data['result']['order']['status'] ?? null
            ]);

            return redirect()->away($checkoutUrl);

        } catch (\Exception $e) {
            Log::error('Noon Payment Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData
            ]);

            return response()->json([
                'error' => 'Payment request exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function success(Request $request)
    {
        if ($request->boolean('preview')) {
            return view('payment-success');
        }

        $payload = $request->all();
        $paymentSuccessful = $this->isPaymentSuccessful($payload);

        \Illuminate\Support\Facades\Log::info('💰 PAYMENT REDIRECT CALLBACK RECEIVED', [
            'request_all_params' => $request->all(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'ip' => $request->ip(),
            'environment' => config('app.env'),
            'timestamp' => now()->toDateTimeString(),
            'payment_successful_in_redirect' => $paymentSuccessful,
        ]);

        $orderId = $request->get('order_id');
        $order = null;

        // Try to find order by order_id first
        if ($orderId) {
            \Illuminate\Support\Facades\Log::info('🔍 STEP 1: Searching for order by order_id', [
                'order_id' => $orderId,
            ]);
            $order = \App\Models\Order::find($orderId);
            if ($order) {
                \Illuminate\Support\Facades\Log::info('✅ Order found by order_id', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                ]);
            }
        }

        // If not found, try to find by order_number from Noon response
        if (!$order && $request->has('orderNumber')) {
            \Illuminate\Support\Facades\Log::info('🔍 STEP 2: Searching for order by order_number', [
                'order_number' => $request->get('orderNumber'),
            ]);
            $order = \App\Models\Order::where('order_number', $request->get('orderNumber'))->first();
            if ($order) {
                \Illuminate\Support\Facades\Log::info('✅ Order found by order_number', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                ]);
            }
        }

        // If still not found, get the most recent pending order for the user (fallback)
        if (!$order) {
            \Illuminate\Support\Facades\Log::warning('⚠️ STEP 3: Order not found, trying fallback (most recent pending)', [
                'order_id_param' => $orderId,
                'request_params' => $request->all(),
            ]);
            // Try to get the most recent pending order
            $order = \App\Models\Order::where('payment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($order) {
                \Illuminate\Support\Facades\Log::info('✅ Order found by fallback (most recent pending)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                ]);
            }
        }

        if ($order) {
            \Illuminate\Support\Facades\Log::info('📦 ORDER FOUND - Starting payment status update process', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_payment_status' => $order->payment_status,
                'current_status' => $order->status,
                'current_shop_id' => $order->shop_id ?? 'MISSING',
                'restaurant_id' => $order->restaurant_id,
            ]);

            // ✅ IMPORTANT:
            // Do NOT mark order as paid or confirmed unless payment is really successful.
            // Noon may redirect here even when the payment failed or was cancelled.
            if (!$paymentSuccessful) {
                \Illuminate\Support\Facades\Log::warning('⚠️ Payment redirect indicates payment NOT successful. Keeping / marking order as failed.', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'current_payment_status' => $order->payment_status,
                    'callback_payload' => $payload,
                ]);

                // Optionally mark as failed/cancelled (only if still pending)
                if ($order->payment_status === 'pending') {
                    $order->payment_status = 'failed';
                    if ($order->status !== 'cancelled') {
                        $order->status = 'cancelled';
                    }
                    $order->save();
                }

                // Show proper failure page to the customer
                return response()->view('payment-failed', ['orderId' => $order->id]);
            }

            // Only update if payment_status is still pending (avoid duplicate processing)
            if ($order->payment_status === 'pending') {
                $shouldWaitForWebhook = $request->boolean('wait_for_webhook', false);
                if ($shouldWaitForWebhook) {
                    \Illuminate\Support\Facades\Log::info('⌛ Payment success redirect received - waiting for webhook confirmation (explicit request)', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ]);

                    return redirect()->route('rest-link', [
                        'order_id' => $order->id,
                        'payment_status' => 'pending_verification',
                    ]);
                }

                \Illuminate\Support\Facades\Log::info('✅ Payment status is pending, proceeding with update');
                // Ensure shop_id is set before updating payment status
                if (empty($order->shop_id) && !empty($order->restaurant_id)) {
                    $restaurant = \App\Models\Restaurant::find($order->restaurant_id);
                    if ($restaurant && !empty($restaurant->shop_id)) {
                        $order->shop_id = $restaurant->shop_id;
                        \Illuminate\Support\Facades\Log::info('🔍 Set shop_id from restaurant before payment update', [
                            'order_id' => $order->id,
                            'restaurant_id' => $order->restaurant_id,
                            'shop_id' => $restaurant->shop_id,
                        ]);
                    } else {
                        // Fallback to default shop_id
                        $order->shop_id = '11183';
                        \Illuminate\Support\Facades\Log::warning('⚠️ Using default shop_id for order', [
                            'order_id' => $order->id,
                            'restaurant_id' => $order->restaurant_id,
                            'shop_id' => '11183',
                        ]);
                    }
                }

                // Extract order reference from Noon response
                // Noon may send order reference in different formats:
                // 1. orderReference from callback parameters
                // 2. order.id from Noon response
                // 3. orderId from callback parameters
                // 4. merchantOrderReference from callback parameters
                // 5. Check in nested arrays (result.order.id, result.order.reference, etc.)
                $orderReference = null;
                
                // Log all request parameters to help debug
                \Illuminate\Support\Facades\Log::info('🔍 Extracting order reference from Noon callback', [
                    'all_params' => $request->all(),
                    'has_orderReference' => $request->has('orderReference'),
                    'has_orderId' => $request->has('orderId'),
                    'has_merchantOrderReference' => $request->has('merchantOrderReference'),
                ]);
                
                // Try to get from request parameters (Noon callback)
                if ($request->has('orderReference')) {
                    $orderReference = $request->get('orderReference');
                } elseif ($request->has('orderId')) {
                    $orderReference = $request->get('orderId');
                } elseif ($request->has('merchantOrderReference')) {
                    $orderReference = $request->get('merchantOrderReference');
                } elseif ($request->has('order.id')) {
                    $orderReference = $request->get('order.id');
                }
                
                // If still not found, check nested arrays
                if (!$orderReference && $request->has('result')) {
                    $result = $request->get('result');
                    if (is_array($result)) {
                        if (isset($result['order']['id'])) {
                            $orderReference = $result['order']['id'];
                        } elseif (isset($result['order']['reference'])) {
                            $orderReference = $result['order']['reference'];
                        } elseif (isset($result['orderId'])) {
                            $orderReference = $result['orderId'];
                        }
                    }
                }
                
                \Illuminate\Support\Facades\Log::info('📝 Order reference extracted', [
                    'order_reference' => $orderReference,
                    'order_id' => $order->id,
                ]);
                
                $order->payment_status = 'paid';
                $order->status = 'confirmed'; // Update status to confirmed
                if ($orderReference) {
                    $order->payment_order_reference = $orderReference;
                }
                $order->save();

                \Illuminate\Support\Facades\Log::info('✅ Payment successful for order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                    'restaurant_id' => $order->restaurant_id,
                    'total' => $order->total,
                    'payment_status' => $order->payment_status,
                    'environment' => config('app.env'),
                ]);

                // Send to shipping company automatically
                // Note: The Order model's updated event should handle this automatically,
                // but we'll also try here as a backup if the event didn't trigger
                try {
                    // Refresh order to ensure we have latest data (including dsp_order_id from event)
                    $order->refresh();

                    // Only send if not already sent by the Order model's updated event
                    if (empty($order->dsp_order_id)) {
                        \Illuminate\Support\Facades\Log::info('🚀 Attempting to send order to shipping company (manual backup)', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'shop_id' => $order->shop_id ?? 'MISSING',
                            'has_dsp_order_id' => false,
                        ]);

                        $shippingService = new \App\Services\ShippingService();
                        $shippingResult = $shippingService->createOrder($order);
                    } else {
                        \Illuminate\Support\Facades\Log::info('✅ Order already sent to shipping company via event listener', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id' => $order->dsp_order_id,
                        ]);
                        $shippingResult = null; // Already sent, skip manual send
                    }

                    if ($shippingResult) {
                        \Illuminate\Support\Facades\Log::info('✅ Order sent to shipping company successfully', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id' => $shippingResult['dsp_order_id'] ?? null,
                            'shipping_status' => $shippingResult['shipping_status'] ?? null,
                            'customer_name' => $order->delivery_name,
                            'customer_phone' => $order->delivery_phone,
                            'customer_address' => $order->delivery_address
                        ]);

                        // Update order with shipping information
                        if (isset($shippingResult['dsp_order_id'])) {
                            $order->dsp_order_id = $shippingResult['dsp_order_id'];
                            $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
                            $order->save();
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('⚠️ Failed to send order to shipping company', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'reason' => 'Shipping service returned null'
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('❌ Error sending order to shipping company', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('⚠️ Order already processed - payment_status is NOT pending', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'dsp_order_id' => $order->dsp_order_id ?? 'MISSING',
                    'shipping_status' => $order->shipping_status ?? 'MISSING',
                ]);
            }

            return response()->view('payment-success', ['orderId' => $order->id]);
        } else {
            \Illuminate\Support\Facades\Log::error('❌ NO ORDER FOUND - Cannot process payment success', [
                'order_id_param' => $orderId,
                'request_params' => $request->all(),
            ]);

            // If we don't have an order, we should not show a "payment successful" screen.
            return response()->view('payment-failed');
        }
    }

    /**
     * Determine if payment is actually successful based on Noon redirect payload.
     * This uses the same logic as the webhook handler to avoid false positives.
     */
    private function isPaymentSuccessful(array $payload): bool
    {
        $successValues = [
            'success',
            'succeeded',
            'successful',
            'paid',
            'completed',
            'captured',
            'approved',
            'authorized',
        ];

        $statusCandidates = [
            data_get($payload, 'event'),
            data_get($payload, 'eventType'),
            data_get($payload, 'event.type'),
            data_get($payload, 'status'),
            data_get($payload, 'paymentStatus'),
            data_get($payload, 'orderStatus'),
            data_get($payload, 'transactionStatus'),
            data_get($payload, 'result.status'),
            data_get($payload, 'result.order.status'),
        ];

        foreach ($statusCandidates as $status) {
            if (is_string($status) && in_array(strtolower($status), $successValues, true)) {
                return true;
            }
        }

        $resultCode = (string) data_get($payload, 'resultCode', data_get($payload, 'responseCode'));
        if (in_array($resultCode, ['000', '0000', '00'], true)) {
            return true;
        }

        return false;
    }

    public function fail()
    {
        return view('payment-failed');
    }

    /**
     * التحقق من حالة API نون
     */
    public function checkApiStatus()
    {
        try {
            // محاولة الاتصال بـ API نون للتحقق من الحالة
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->get(config('noon.api_url') . '/health');

            return response()->json([
                'api_status' => $response->status(),
                'api_response' => $response->json(),
                'api_url' => config('noon.api_url'),
                'api_key_exists' => !empty(config('noon.api_key')),
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'API connection failed',
                'message' => $e->getMessage(),
                'api_url' => config('noon.api_url'),
                'api_key_exists' => !empty(config('noon.api_key')),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * اختبار بسيط للاتصال مع نون
     */
    public function testConnection()
    {
        $testData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00, // مبلغ صغير للاختبار
            ],
            'merchantOrderReference' => 'TEST-' . time(),
            'customer' => [
                'email' => 'test@noon.com',
                'name' => 'Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'), // ✅ إضافة applicationId
            'businessId' => config('noon.business_id', 'adv_food'), // ✅ إضافة businessId
            'description' => 'Connection test',
        ];

        Log::info('Noon Connection Test', ['data' => $testData]);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $testData);

            return response()->json([
                'test_status' => $response->status(),
                'test_response' => $response->json(),
                'test_data_sent' => $testData,
                'activity_id' => $response->json()['activityId'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_error' => $e->getMessage(),
                'test_data_sent' => $testData
            ], 500);
        }
    }

    /**
     * اختبار سريع بمبلغ صغير (1 ريال)
     */
    public function quickTest()
    {
        $requestData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00, // مبلغ صغير جداً للاختبار
            ],
            'merchantOrderReference' => 'QUICK-TEST-' . time(),
            'customer' => [
                'email' => 'test@noon.com',
                'name' => 'Quick Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'description' => 'Quick test with 1 SAR',
        ];

        Log::info('Noon Quick Test', ['data' => $requestData]);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Quick Test (1 SAR)',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Quick Test (1 SAR)',
                'error' => $e->getMessage(),
                'request_data' => $requestData
            ], 500);
        }
    }

    /**
     * اختبار الـ headers المرسلة
     */
    public function testHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Api-Key' => config('noon.api_key'),
        ];

        return response()->json([
            'headers_to_send' => $headers,
            'api_key_exists' => !empty(config('noon.api_key')),
            'api_key_length' => strlen(config('noon.api_key', '')),
            'api_url' => config('noon.api_url'),
            'application_id' => config('noon.application_id'),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * الاختبار النهائي مع جميع البيانات الصحيحة
     */
    public function finalTest()
    {
        $requestData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'FINAL-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Final Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'businessId' => config('noon.business_id', 'adv_food'),
            'description' => 'Final test with correct credentials',
            'category' => 'pay',
            'channel' => 'web'
        ];

        Log::info('Noon Final Test', [
            'url' => config('noon.api_url') . '/payment/v1/order',
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'application_id' => config('noon.application_id'),
            'business_id' => config('noon.business_id'),
            'data' => $requestData
        ]);

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Final Test with Correct Credentials',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'credentials' => [
                    'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Final Test with Correct Credentials',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'credentials' => [
                    'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ], 500);
        }
    }

    /**
     * اختبار API Key الجديد
     */
    public function testNewApiKey()
    {
        $requestData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'NEW-KEY-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'New API Key Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'businessId' => config('noon.business_id', 'adv_food'),
            'description' => 'Test with new API key',
            'category' => 'pay',
            'channel' => 'web'
        ];

        Log::info('Noon New API Key Test', [
            'url' => config('noon.api_url') . '/payment/v1/order',
            'new_api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'api_key_length' => strlen(config('noon.api_key', '')),
            'application_id' => config('noon.application_id'),
            'business_id' => config('noon.business_id'),
            'data' => $requestData
        ]);

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'New API Key Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'credentials' => [
                    'new_api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'api_key_length' => strlen(config('noon.api_key', '')),
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'New API Key Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'credentials' => [
                    'new_api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'api_key_length' => strlen(config('noon.api_key', '')),
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ], 500);
        }
    }

    /**
     * اختبار سريع مع API Key الجديد
     */
    public function quickNewKeyTest()
    {
        $requestData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'QUICK-NEW-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Quick Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'businessId' => config('noon.business_id', 'adv_food'),
            'description' => 'Quick test with new API key',
        ];

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Quick New API Key Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'credentials' => [
                    'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'api_key_length' => strlen(config('noon.api_key', '')),
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Quick New API Key Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'credentials' => [
                    'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
                    'api_key_length' => strlen(config('noon.api_key', '')),
                    'application_id' => config('noon.application_id'),
                    'business_id' => config('noon.business_id'),
                    'api_url' => config('noon.api_url')
                ]
            ], 500);
        }
    }

    /**
     * الاختبار النهائي مع متغيرات البيئة الصحيحة
     */
    public function finalEnvTest()
    {
        // التحقق من متغيرات البيئة
        $envCheck = [
            'NOON_API_KEY' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'NOON_API_URL' => config('noon.api_url') ?: 'NOT SET',
            'NOON_APPLICATION_ID' => config('noon.application_id') ?: 'NOT SET',
            'NOON_BUSINESS_ID' => config('noon.business_id') ?: 'NOT SET',
        ];

        if (!config('noon.api_key') || !config('noon.api_url')) {
            return response()->json([
                'error' => 'Environment variables not loaded',
                'env_check' => $envCheck,
                'message' => 'NOON_API_KEY or NOON_API_URL is not set'
            ], 500);
        }

        $requestData = [
            'amount' => [
                'currency' => 'SAR',
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'ENV-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Environment Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'businessId' => config('noon.business_id', 'adv_food'),
            'description' => 'Final test with correct environment variables',
        ];

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Final Environment Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'env_check' => $envCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Final Environment Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'env_check' => $envCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order'
            ], 500);
        }
    }

    /**
     * اختبار مع config بدلاً من env
     */
    public function testWithConfig()
    {
        // التحقق من config
        $configCheck = [
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'api_url' => config('noon.api_url') ?: 'NOT SET',
            'application_id' => config('noon.application_id') ?: 'NOT SET',
            'business_id' => config('noon.business_id') ?: 'NOT SET',
            'success_url' => config('noon.success_url') ?: 'NOT SET',
            'failure_url' => config('noon.failure_url') ?: 'NOT SET',
        ];

        if (!config('noon.api_key') || !config('noon.api_url')) {
            return response()->json([
                'error' => 'Noon config not loaded',
                'config_check' => $configCheck,
                'message' => 'noon.api_key or noon.api_url is not set'
            ], 500);
        }

        $requestData = [
            'amount' => [
                'currency' => config('noon.defaults.currency', 'SAR'),
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'CONFIG-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Config Test User',
            ],
            'successUrl' => config('noon.success_url', route('payment.success')),
            'failureUrl' => config('noon.failure_url', route('payment.fail')),
            'applicationId' => config('noon.application_id', 'adv-food'),
            'businessId' => config('noon.business_id', 'adv_food'),
            'description' => 'Test with config instead of env',
            'category' => config('noon.defaults.category', 'pay'),
            'channel' => config('noon.defaults.channel', 'web')
        ];

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Config Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Config Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order'
            ], 500);
        }
    }

    /**
     * اختبار نهائي مع القيم المباشرة في config
     */
    public function finalDirectTest()
    {
        // التحقق من config
        $configCheck = [
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'api_url' => config('noon.api_url') ?: 'NOT SET',
            'application_id' => config('noon.application_id') ?: 'NOT SET',
            'business_id' => config('noon.business_id') ?: 'NOT SET',
            'success_url' => config('noon.success_url') ?: 'NOT SET',
            'failure_url' => config('noon.failure_url') ?: 'NOT SET',
        ];

        $requestData = [
            'amount' => [
                'currency' => config('noon.defaults.currency', 'SAR'),
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'DIRECT-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Direct Config Test User',
            ],
            'successUrl' => config('noon.success_url'),
            'failureUrl' => config('noon.failure_url'),
            'applicationId' => config('noon.application_id'),
            'businessId' => config('noon.business_id'),
            'description' => 'Final test with direct config values',
            'category' => config('noon.defaults.category', 'pay'),
            'channel' => config('noon.defaults.channel', 'web')
        ];

        Log::info('Noon Direct Config Test', [
            'url' => config('noon.api_url') . '/payment/v1/order',
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'application_id' => config('noon.application_id'),
            'business_id' => config('noon.business_id'),
            'data' => $requestData
        ]);

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Final Direct Config Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order',
                'message' => 'Using direct config values - no env dependency'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Final Direct Config Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order',
                'message' => 'Using direct config values - no env dependency'
            ], 500);
        }
    }

    /**
     * اختبار نهائي مع config يقرأ من .env
     */
    public function finalEnvConfigTest()
    {
        // التحقق من config
        $configCheck = [
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'api_url' => config('noon.api_url') ?: 'NOT SET',
            'application_id' => config('noon.application_id') ?: 'NOT SET',
            'business_id' => config('noon.business_id') ?: 'NOT SET',
            'success_url' => config('noon.success_url') ?: 'NOT SET',
            'failure_url' => config('noon.failure_url') ?: 'NOT SET',
        ];

        $requestData = [
            'amount' => [
                'currency' => config('noon.defaults.currency', 'SAR'),
                'value' => 1.00,
            ],
            'merchantOrderReference' => 'ENV-CONFIG-TEST-' . time(),
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Env Config Test User',
            ],
            'successUrl' => config('noon.success_url'),
            'failureUrl' => config('noon.failure_url'),
            'applicationId' => config('noon.application_id'),
            'businessId' => config('noon.business_id'),
            'description' => 'Final test with config reading from .env',
            'category' => config('noon.defaults.category', 'pay'),
            'channel' => config('noon.defaults.channel', 'web')
        ];

        Log::info('Noon Env Config Test', [
            'url' => config('noon.api_url') . '/payment/v1/order',
            'api_key' => config('noon.api_key') ? 'SET' : 'NOT SET',
            'application_id' => config('noon.application_id'),
            'business_id' => config('noon.business_id'),
            'data' => $requestData
        ]);

        try {
            $response = Http::timeout(config('noon.defaults.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => config('noon.api_key'),
                ])
                ->post(config('noon.api_url') . '/payment/v1/order', $requestData);

            return response()->json([
                'test_type' => 'Final Env Config Test',
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $requestData,
                'activity_id' => $response->json()['activityId'] ?? null,
                'success' => $response->successful(),
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order',
                'message' => 'Using config reading from .env file'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_type' => 'Final Env Config Test',
                'error' => $e->getMessage(),
                'request_data' => $requestData,
                'config_check' => $configCheck,
                'full_url' => config('noon.api_url') . '/payment/v1/order',
                'message' => 'Using config reading from .env file'
            ], 500);
        }
    }

    /**
     * اختبار Authorization header مباشرة
     */
    public function testAuthHeader()
    {
        $businessId = config('noon.business_id', 'adv_food');
        $applicationId = config('noon.application_id', 'adv-food');
        $apiKey = config('noon.api_key');
        $authString = base64_encode($businessId . '.' . $applicationId . ':' . $apiKey);

        return response()->json([
            'business_id' => $businessId,
            'application_id' => $applicationId,
            'api_key' => $apiKey ? 'EXISTS' : 'MISSING',
            'auth_string' => $authString,
            'decoded_auth' => base64_decode($authString),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $authString
            ],
            'test_url' => 'https://api-test.sa.noonpayments.com/payment/v1/order'
        ]);
    }

    /**
     * إنشاء نموذج اتصال مع دعم نون
     */
    public function generateSupportTicket()
    {
        $ticketData = [
            'issue_type' => 'API Integration',
            'error_code' => '500 Internal Server Error',
            'api_endpoint' => config('noon.api_url') . '/payment/v1/order',
            'request_format' => [
                'amount' => ['currency' => 'SAR', 'value' => 1.00],
                'merchantOrderReference' => 'ORDER-' . now()->timestamp,
                'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
                'successUrl' => config('noon.success_url', route('payment.success')),
                'failureUrl' => config('noon.failure_url', route('payment.fail')),
                'applicationId' => config('noon.application_id', 'adv-food')
            ],
            'environment' => 'test',
            'timestamp' => now()->toDateTimeString(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION
        ];

        return response()->json([
            'support_ticket_data' => $ticketData,
            'message' => 'Use this data when contacting Noon support',
            'contact_info' => [
                'email' => 'support@noonpayments.com',
                'subject' => 'API Integration Issue - 500 Internal Server Error',
                'priority' => 'High'
            ]
        ]);
    }
}
