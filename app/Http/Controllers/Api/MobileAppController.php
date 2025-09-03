<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class MobileAppController extends Controller
{
    /**
     * Get user points balance from external point system
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

            // Find customer by email in external system
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl . '/customers', [
                    'email' => $user->email
                ]);

            if ($response->successful()) {
                $customers = $response->json();
                
                // Find the customer with matching email
                $customerData = null;
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
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found in points system',
                        'data' => [
                            'user_id' => $user->id,
                            'points_balance' => 0,
                            'tier' => 'bronze',
                            'email' => $user->email,
                            'name' => $user->name
                        ]
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve points from external system',
                    'error' => 'External API returned status: ' . $response->status()
                ], 500);
            }

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
