<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PointsService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.external_api.key');
        $this->apiUrl = config('services.external_api.url');
    }

    /**
     * Create a new customer in the points system
     */
    public function createCustomer($userData)
    {
        try {
            if (empty($this->apiKey)) {
                Log::warning('External API key not configured');
                return null;
            }

            $customerData = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone_number'] ?? null,
                'tier' => 'bronze',
                'points_balance' => 0,
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/customers', $customerData);

            if ($response && $response->successful()) {
                $responseData = $response->json();
                
                Log::info('Customer created in points system', [
                    'user_email' => $userData['email'],
                    'response' => $responseData
                ]);

                return $responseData['data']['id'] ?? $responseData['id'] ?? null;
            } else {
                Log::error('Failed to create customer in points system', [
                    'user_email' => $userData['email'],
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Exception while creating customer in points system', [
                'user_email' => $userData['email'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get customer points balance
     */
    public function getCustomerPoints($customerId)
    {
        try {
            if (empty($this->apiKey) || empty($customerId)) {
                return null;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($this->apiUrl . '/customers/' . $customerId . '/balance');

            if ($response && $response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception while getting customer points', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if customer exists by email
     */
    public function findCustomerByEmail($email)
    {
        try {
            if (empty($this->apiKey)) {
                return null;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($this->apiUrl . '/customers?email=' . urlencode($email));

            if ($response && $response->successful()) {
                $customers = $response->json();
                
                if (isset($customers['data']) && is_array($customers['data'])) {
                    foreach ($customers['data'] as $customer) {
                        if (isset($customer['email']) && $customer['email'] === $email) {
                            return $customer['id'];
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception while finding customer by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
