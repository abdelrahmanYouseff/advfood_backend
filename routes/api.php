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
