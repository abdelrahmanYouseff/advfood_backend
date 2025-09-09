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

            // Check if user has point_customer_id
            if (!$user->point_customer_id) {
                return response()->json([
                    'success' => true,
                    'message' => 'User not registered in points system',
                    'data' => [
                        'points_balance' => 0,
                        'tier' => 'bronze',
                        'note' => 'User not registered in points system'
                    ]
                ]);
            }

            // Get points from external system
            $pointsService = new PointsService();
            $pointsData = $pointsService->getCustomerPoints($user->point_customer_id);

            if ($pointsData) {
                return response()->json([
                    'success' => true,
                    'message' => 'Points retrieved successfully',
                    'data' => [
                        'points_balance' => $pointsData['data']['points_balance'] ?? 0,
                        'tier' => $pointsData['data']['tier'] ?? 'bronze',
                        'customer_id' => $user->point_customer_id
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Points system unavailable',
                    'data' => [
                        'points_balance' => 0,
                        'tier' => 'bronze',
                        'customer_id' => $user->point_customer_id,
                        'note' => 'Points system temporarily unavailable'
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving points',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
