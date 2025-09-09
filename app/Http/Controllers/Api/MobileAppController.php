<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileAppController extends Controller
{
    /**
     * Get user points balance from external point systemm
     */
    public function getUserPoints(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get API credentials
            $apiKey = config('services.external_api.key');
            $apiUrl = config('services.external_api.url');

            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'External API not configured'
                ], 500);
            }

            // Try to find customer by ID first, then by email search
            $response = null;
            $customerData = null;

            // First try to get customer by ID if we have external_id stored
            // For now, try to search for customer by email
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl . '/customers');

            if ($response && $response->successful()) {
                $customers = $response->json();

                // Find the customer with matching email
                if (isset($customers['data']) && is_array($customers['data'])) {
                    foreach ($customers['data'] as $customer) {
                        if (isset($customer['email']) && $customer['email'] === $user->email) {
                            $customerData = $customer;
                            break;
                        }
                    }
                }

                if ($customerData) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Points retrieved successfully',
                        'data' => [
                            'user_id' => $user->id,
                            'customer_id' => $customerData['id'] ?? null,
                            'points_balance' => $customerData['points_balance'] ?? 0,
                            'tier' => $customerData['tier'] ?? 'bronze',
                            'email' => $user->email,
                            'name' => $user->name
                        ]
                    ]);
                }
            }

            // If external API fails or user not found, return default values
            return response()->json([
                'success' => true,
                'message' => 'Points retrieved (external system unavailable)',
                'data' => [
                    'user_id' => $user->id,
                    'customer_id' => null,
                    'points_balance' => 0,
                    'tier' => 'bronze',
                    'email' => $user->email,
                    'name' => $user->name,
                    'note' => 'External points system temporarily unavailable'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user points by email (public endpoint)
     */
    public function getPointsByEmail(Request $request)
    {
        try {
            // Validate email parameter
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->input('email');

            // Get API credentials
            $apiKey = config('services.external_api.key');
            $apiUrl = config('services.external_api.url');

            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'External API not configured'
                ], 500);
            }

            // Since the external API doesn't support email search,
            // we'll return a message indicating that email lookup is not supported
            return response()->json([
                'success' => true,
                'message' => 'Email lookup not supported by external system',
                'data' => [
                    'email' => $email,
                    'name' => null,
                    'points_balance' => 0,
                    'tier' => 'bronze',
                    'note' => 'External points system does not support email-based lookup. Please contact support.'
                ]
            ]);

            // If we found the customer ID, get their balance
            if ($customerId) {
                $balanceResponse = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->get($apiUrl . '/customers/' . $customerId . '/balance');

                if ($balanceResponse && $balanceResponse->successful()) {
                    $balanceData = $balanceResponse->json();

                    // Debug: Log the response
                    Log::info('Balance API Response:', $balanceData);

                    return response()->json([
                        'success' => true,
                        'message' => 'Points retrieved successfully',
                        'data' => [
                            'email' => $email,
                            'name' => $customerData['name'] ?? null,
                            'points_balance' => $balanceData['data']['points_balance'] ?? 0,
                            'tier' => $balanceData['data']['tier'] ?? 'bronze'
                        ]
                    ]);
                }
            }

            // If external API fails or user not found, return default values
            return response()->json([
                'success' => true,
                'message' => 'Points retrieved (external system unavailable)',
                'data' => [
                    'email' => $email,
                    'name' => null,
                    'points_balance' => 0,
                    'tier' => 'bronze',
                    'note' => 'External points system temporarily unavailable'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile with points
     */
    public function getUserProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get points data
            $pointsResponse = $this->getUserPoints($request);
            $pointsData = json_decode($pointsResponse->getContent(), true);

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'address' => $user->address,
                        'country' => $user->country,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ],
                    'points' => $pointsData['data'] ?? [
                        'points_balance' => 0,
                        'tier' => 'bronze'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
