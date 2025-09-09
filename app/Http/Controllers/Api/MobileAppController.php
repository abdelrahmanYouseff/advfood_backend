<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAppController extends Controller
{
    /**
     * Test authentication
     */
    public function testAuth(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Authentication working',
            'user' => $request->user()
        ]);
    }

    /**
     * Get user points by point_customer_id
     */
    public function getPointsByCustomerId(Request $request, $pointCustomerId)
    {
        try {
            if (empty($pointCustomerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Point customer ID is required'
                ], 400);
            }

            $pointsService = new PointsService();
            $pointsData = $pointsService->getCustomerPoints($pointCustomerId);

            if ($pointsData && isset($pointsData['status']) && $pointsData['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Points retrieved successfully',
                    'data' => [
                        'customer_id' => $pointCustomerId,
                        'points_balance' => $pointsData['data']['points_balance'] ?? 0,
                        'tier' => $pointsData['data']['tier'] ?? 'bronze',
                        'total_earned' => $pointsData['data']['total_earned'] ?? 0,
                        'total_redeemed' => $pointsData['data']['total_redeemed'] ?? 0,
                        'name' => $pointsData['data']['name'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or unable to retrieve points',
                    'data' => [
                        'customer_id' => $pointCustomerId,
                        'points_balance' => 0,
                        'tier' => 'bronze'
                    ]
                ], 404);
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
