<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    /**
     * Display a listing of active ads.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Ad::query();

            // Filter by type if provided
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by position if provided
            if ($request->has('position') && $request->position) {
                $query->where('position', $request->position);
            }

            // Filter by active status (default: only active ads)
            $isActive = $request->get('is_active', true);
            $query->where('is_active', $isActive);

            // Sort by sort_order and created_at
            $query->orderBy('sort_order')->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 10);
            $ads = $query->paginate($perPage);

            // Transform ads to include full image URLs
            $ads->getCollection()->transform(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'image' => $ad->image ? url('storage/' . $ad->image) : null,
                    'image_path' => $ad->image,
                    'link' => $ad->link,
                    'type' => $ad->type,
                    'position' => $ad->position,
                    'is_active' => $ad->is_active,
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                    'clicks_count' => $ad->clicks_count,
                    'views_count' => $ad->views_count,
                    'sort_order' => $ad->sort_order,
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Ads retrieved successfully',
                'data' => $ads,
                'total' => $ads->total(),
                'per_page' => $ads->perPage(),
                'current_page' => $ads->currentPage(),
                'last_page' => $ads->lastPage(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified ad.
     */
    public function show(Ad $ad): JsonResponse
    {
        try {
            // Increment views count
            $ad->increment('views_count');

            $adData = [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'image' => $ad->image ? url('storage/' . $ad->image) : null,
                'image_path' => $ad->image,
                'link' => $ad->link,
                'type' => $ad->type,
                'position' => $ad->position,
                'is_active' => $ad->is_active,
                'start_date' => $ad->start_date,
                'end_date' => $ad->end_date,
                'clicks_count' => $ad->clicks_count,
                'views_count' => $ad->views_count + 1,
                'sort_order' => $ad->sort_order,
                'created_at' => $ad->created_at,
                'updated_at' => $ad->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Ad retrieved successfully',
                'data' => $adData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ads by type.
     */
    public function getByType(Request $request, string $type): JsonResponse
    {
        try {
            $validTypes = ['banner', 'popup', 'sidebar'];

            if (!in_array($type, $validTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ad type. Valid types: ' . implode(', ', $validTypes)
                ], 400);
            }

            $ads = Ad::where('type', $type)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc')
                    ->get();

            // Transform ads to include full image URLs
            $ads = $ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'image' => $ad->image ? url('storage/' . $ad->image) : null,
                    'image_path' => $ad->image,
                    'link' => $ad->link,
                    'type' => $ad->type,
                    'position' => $ad->position,
                    'is_active' => $ad->is_active,
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                    'clicks_count' => $ad->clicks_count,
                    'views_count' => $ad->views_count,
                    'sort_order' => $ad->sort_order,
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "{$type} ads retrieved successfully",
                'data' => $ads,
                'count' => $ads->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ads by type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured/currently active ads.
     */
    public function getFeatured(Request $request): JsonResponse
    {
        try {
            $ads = Ad::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc')
                    ->limit($request->get('limit', 5))
                    ->get();

            // Transform ads to include full image URLs
            $ads = $ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'image' => $ad->image ? url('storage/' . $ad->image) : null,
                    'image_path' => $ad->image,
                    'link' => $ad->link,
                    'type' => $ad->type,
                    'position' => $ad->position,
                    'is_active' => $ad->is_active,
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                    'clicks_count' => $ad->clicks_count,
                    'views_count' => $ad->views_count,
                    'sort_order' => $ad->sort_order,
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Featured ads retrieved successfully',
                'data' => $ads,
                'count' => $ads->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving featured ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Increment clicks count for an ad.
     */
    public function incrementClicks(Ad $ad): JsonResponse
    {
        try {
            $ad->increment('clicks_count');

            return response()->json([
                'success' => true,
                'message' => 'Clicks count incremented successfully',
                'data' => [
                    'id' => $ad->id,
                    'clicks_count' => $ad->clicks_count + 1
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error incrementing clicks count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
