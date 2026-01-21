<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class BranchService
{
    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in kilometers.
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        // Convert degrees to radians
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        // Haversine formula
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Find the nearest branch to customer coordinates.
     *
     * @param float|null $customerLat
     * @param float|null $customerLon
     * @return Branch|null
     */
    public static function findNearestBranch(?float $customerLat, ?float $customerLon): ?Branch
    {
        // If coordinates are missing, return null
        if ($customerLat === null || $customerLon === null) {
            Log::info('📍 Cannot find nearest branch: customer coordinates missing', [
                'customer_latitude' => $customerLat,
                'customer_longitude' => $customerLon,
            ]);
            return null;
        }

        // Get all active branches with coordinates
        $branches = Branch::where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($branches->isEmpty()) {
            Log::warning('⚠️ No active branches with coordinates found');
            return null;
        }

        $nearestBranch = null;
        $shortestDistance = null;

        foreach ($branches as $branch) {
            $distance = self::calculateDistance(
                $customerLat,
                $customerLon,
                (float) $branch->latitude,
                (float) $branch->longitude
            );

            if ($shortestDistance === null || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestBranch = $branch;
            }

            Log::info('📍 Branch distance calculated', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'distance_km' => $distance,
                'customer_lat' => $customerLat,
                'customer_lon' => $customerLon,
                'branch_lat' => $branch->latitude,
                'branch_lon' => $branch->longitude,
            ]);
        }

        if ($nearestBranch) {
            Log::info('✅ Nearest branch found', [
                'branch_id' => $nearestBranch->id,
                'branch_name' => $nearestBranch->name,
                'distance_km' => $shortestDistance,
            ]);
        }

        return $nearestBranch;
    }
}
