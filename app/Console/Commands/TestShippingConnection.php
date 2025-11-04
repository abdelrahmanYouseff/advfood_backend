<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestShippingConnection extends Command
{
    protected $signature = 'shipping:test-connection';
    protected $description = 'Test connection to shipping company API';

    public function handle()
    {
        $this->info('🔍 Testing Shipping API Connection...');
        $this->newLine();

        // Check configuration
        $apiUrl = Config::get('services.shipping.url');
        $apiKey = Config::get('services.shipping.key');

        $this->info('📋 Configuration:');
        $this->line('  API URL: ' . ($apiUrl ?: '❌ NOT SET'));
        $this->line('  API Key: ' . ($apiKey ? '✅ Set (length: ' . strlen($apiKey) . ')' : '❌ NOT SET'));
        $this->newLine();

        if (empty($apiUrl) || empty($apiKey)) {
            $this->error('❌ Missing configuration! Please check your .env file:');
            $this->line('  - SHIPPING_API_URL');
            $this->line('  - SHIPPING_API_KEY');
            return 1;
        }

        // Test connection
        $this->info('🌐 Testing connection...');
        try {
            $testUrl = rtrim($apiUrl, '/') . '/orders';

            $this->line('  URL: ' . $testUrl);
            $this->line('  Method: POST');
            $this->line('  Headers: Authorization: Bearer [API_KEY]');
            $this->newLine();

            $testPayload = [
                'id' => 'TEST-' . time(),
                'shop_id' => '11183',
                'delivery_details' => [
                    'name' => 'Test User',
                    'phone' => '0500000000#999',
                    'email' => 'test@example.com',
                    'coordinate' => [
                        'latitude' => 24.7136,
                        'longitude' => 46.6753,
                    ],
                    'address' => 'Test Address',
                ],
                'order' => [
                    'payment_type' => 0,
                    'total' => 100.00,
                    'notes' => 'Test order from command',
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($testUrl, $testPayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseJson = $response->json();

            $this->info('📡 Response:');
            $this->line('  Status Code: ' . $statusCode);

            if ($response->successful()) {
                $this->info('  ✅ SUCCESS! Connection is working.');
                $this->line('  Response: ' . json_encode($responseJson, JSON_PRETTY_PRINT));
            } else {
                $this->error('  ❌ FAILED!');
                $this->line('  Response Body: ' . $responseBody);

                if ($statusCode === 401) {
                    $this->error('  🔴 Authentication Error - Check your API key');
                } elseif ($statusCode === 422) {
                    $this->error('  🔴 Validation Error - Check your payload');
                    if (isset($responseJson['errors'])) {
                        $this->line('  Errors: ' . json_encode($responseJson['errors'], JSON_PRETTY_PRINT));
                    }
                } elseif ($statusCode === 404) {
                    $this->error('  🔴 Not Found - Check your API URL and endpoint');
                } else {
                    $this->error('  🔴 Unexpected Error');
                }
            }

            // Log to file
            Log::info('Shipping API Test Connection', [
                'api_url' => $apiUrl,
                'api_key_length' => strlen($apiKey),
                'status_code' => $statusCode,
                'response' => $responseJson,
                'request_payload' => $testPayload,
            ]);

            return $response->successful() ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred:');
            $this->line('  Message: ' . $e->getMessage());
            $this->line('  File: ' . $e->getFile() . ':' . $e->getLine());

            Log::error('Shipping API Test Connection Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}

