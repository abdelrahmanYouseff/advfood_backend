<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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
                ->post($this->apiUrl . '/customers/register', $customerData);

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

    /**
     * Update user points locally from external system
     */
    public function updateUserPointsLocally($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user || !$user->point_customer_id) {
                return false;
            }

            $pointsData = $this->getCustomerPoints($user->point_customer_id);

            if ($pointsData && isset($pointsData['data'])) {
                $user->update([
                    'points' => $pointsData['data']['points_balance'] ?? 0,
                    'points_tier' => $pointsData['data']['tier'] ?? 'bronze'
                ]);

                Log::info('User points updated locally', [
                    'user_id' => $userId,
                    'points' => $pointsData['data']['points_balance'] ?? 0,
                    'tier' => $pointsData['data']['tier'] ?? 'bronze'
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Exception while updating user points locally', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync all users points from external system
     */
    public function syncAllUsersPoints()
    {
        try {
            $users = User::whereNotNull('point_customer_id')->get();
            $updatedCount = 0;

            foreach ($users as $user) {
                if ($this->updateUserPointsLocally($user->id)) {
                    $updatedCount++;
                }
            }

            Log::info('Bulk points sync completed', [
                'total_users' => $users->count(),
                'updated_users' => $updatedCount
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            Log::error('Exception during bulk points sync', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
