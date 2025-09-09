<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\PointsService;

class MobileAppController extends Controller
{
    /**
     * Get user points balance
     */
    public function getUserPoints(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Return points from local database
            return response()->json([
                'success' => true,
                'message' => 'Points retrieved successfully',
                'data' => [
                    'points_balance' => $user->points ?? 0,
                    'tier' => $user->points_tier ?? 'bronze',
                    'customer_id' => $user->point_customer_id,
                    'last_updated' => $user->updated_at
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
     * Update user points from external system
     */
    public function updateUserPoints(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!$user->point_customer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not registered in points system'
                ], 400);
            }

            $pointsService = new PointsService();
            $updated = $pointsService->updateUserPointsLocally($user->id);

            if ($updated) {
                $user->refresh(); // Refresh user data
                return response()->json([
                    'success' => true,
                    'message' => 'Points updated successfully',
                    'data' => [
                        'points_balance' => $user->points,
                        'tier' => $user->points_tier,
                        'customer_id' => $user->point_customer_id,
                        'last_updated' => $user->updated_at
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update points from external system'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating points',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
