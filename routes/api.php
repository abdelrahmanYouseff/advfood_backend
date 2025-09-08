<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileAppController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::get('/restaurants', [RestaurantController::class, 'index']);

// Simple API to get restaurant items by restaurant ID
Route::get('/restaurant/{id}/items', function($id) {
    try {
        $restaurant = \App\Models\Restaurant::find($id);
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم غير موجود'
            ], 404);
        }

        $items = \App\Models\MenuItem::where('restaurant_id', $id)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'description' => $restaurant->description
            ],
            'items_count' => $items->count(),
            'items' => $items
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في الخادم'
        ], 500);
    }
});

// Public menu items routes
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/menu-items/featured', [MenuItemController::class, 'getFeatured']);
Route::get('/restaurants/{restaurant}/menu-items', [MenuItemController::class, 'getByRestaurant']);
Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);

// Points by email (public endpoint - no authentication required)
Route::get('/points-by-email', [MobileAppController::class, 'getPointsByEmail']);

// Authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected mobile app routes
Route::middleware('auth:sanctum')->group(function () {
    // Test authentication
    Route::get('/test-auth', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authentication working',
            'user' => $request->user()
        ]);
    });

    // User points and profile
    Route::get('/user/points', [MobileAppController::class, 'getUserPoints']);
    Route::get('/user/profile', [MobileAppController::class, 'getUserProfile']);

    // Location management
    Route::apiResource('locations', LocationController::class);
    Route::post('/locations/{location}/set-default', [LocationController::class, 'setDefault']);
    Route::get('/locations-default', [LocationController::class, 'getDefault']);

    // Admin menu items management (protected routes)
    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
    Route::post('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
});
