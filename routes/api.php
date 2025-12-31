<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileAppController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\SimpleOrderController;
use App\Http\Controllers\Api\ZydaOrderController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\WhatsappMsgController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\GenericWebhookController;
use App\Http\Controllers\WebhookLogController;

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

Route::post('/webhook', [PaymentWebhookController::class, 'handleNoon']);

// Generic webhook for receiving any type of data
Route::post('/webhook/generic', [GenericWebhookController::class, 'handle']);

// WhatsApp messages API (receive deliver_order + location)
Route::post('/whatsapp/messages', [WhatsappMsgController::class, 'store']);
Route::delete('/whatsapp/messages/{id}', [WhatsappMsgController::class, 'destroy']);

// API endpoint to get webhooks as JSON
Route::get('/webhooks/logs', [WebhookLogController::class, 'api']);

// Public routes
Route::get('/restaurants', [RestaurantController::class, 'index']);

// Public location routes
Route::get('/locations', [LocationController::class, 'index']);
Route::post('/locations', [LocationController::class, 'store']);
Route::get('/locations/{id}', [LocationController::class, 'show']);
Route::put('/locations/{id}', [LocationController::class, 'update']);
Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
Route::post('/locations/{id}/set-default', [LocationController::class, 'setDefault']);
Route::get('/locations-default', [LocationController::class, 'getDefault']);

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


// Authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Points routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/points', [AuthController::class, 'getPoints']);
});

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

    // Admin menu items management (protected routes)
    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
    Route::post('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);

    // User management routes (protected)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Public Ads API routes
Route::get('/ads', [AdController::class, 'index']);
Route::get('/ads/featured', [AdController::class, 'getFeatured']);
Route::get('/ads/type/{type}', [AdController::class, 'getByType']);
Route::get('/ads/{ad}', [AdController::class, 'show']);
Route::post('/ads/{ad}/click', [AdController::class, 'incrementClicks']);

// Public user deletion (no authentication required)
Route::post('/delete-user/{id}', [UserController::class, 'deleteByEmail']);

// Public Points API routes
Route::get('/points/customer/{pointCustomerId}', [MobileAppController::class, 'getPointsByCustomerId']);

// Mobile App Payment API routes
Route::post('/mobile/payment/checkout-url', [MobileAppController::class, 'getPaymentCheckoutUrl']);
Route::get('/mobile/orders', [MobileAppController::class, 'getUserOrders']);
Route::post('/zyda/orders', [ZydaOrderController::class, 'store']);
Route::patch('/zyda/orders/{id}/location', [ZydaOrderController::class, 'updateLocation']);
Route::delete('/zyda/orders/{id}', [ZydaOrderController::class, 'destroy']);

// Direct points API by customer ID
Route::get('/points/{pointCustomerId}', function($pointCustomerId) {
    try {
        // Validate customer ID
        if (!is_numeric($pointCustomerId) || $pointCustomerId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer ID',
                'points_balance' => 0
            ], 400);
        }

        // Try to get points from points system
        $pointsService = new \App\Services\PointsService();
        $pointsData = $pointsService->getCustomerPoints($pointCustomerId);

        $pointsBalance = 0;
        $source = 'default';

        if ($pointsData !== null) {
            // Extract points balance from response
            if (isset($pointsData['data']['points_balance'])) {
                $pointsBalance = $pointsData['data']['points_balance'];
                $source = 'points_system';
            } elseif (isset($pointsData['points_balance'])) {
                $pointsBalance = $pointsData['points_balance'];
                $source = 'points_system';
            }
        } else {
            // If points system is not available, return default points
            $pointsBalance = 0;
            $source = 'default';
            Log::warning('Points system not available, returning default points', [
                'customer_id' => $pointCustomerId
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Points retrieved successfully',
            'data' => [
                'customer_id' => $pointCustomerId,
                'points_balance' => $pointsBalance,
                'source' => $source
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error retrieving points by customer ID', [
            'customer_id' => $pointCustomerId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error retrieving points',
            'error' => $e->getMessage(),
            'points_balance' => 0
        ], 500);
    }
});

// Orders API routes
Route::apiResource('orders', OrderController::class);
Route::get('/users/{userId}/orders', [OrderController::class, 'getUserOrders']);
Route::get('/users/{userId}/orders/stats', [OrderController::class, 'getUserOrdersStats']);

// Order Items API routes
Route::get('/orders/{orderId}/items', [OrderItemController::class, 'index']);
Route::post('/order-items', [OrderItemController::class, 'store']);
Route::post('/order-items/multiple', [OrderItemController::class, 'addMultiple']);
Route::get('/order-items/{id}', [OrderItemController::class, 'show']);
Route::put('/order-items/{id}', [OrderItemController::class, 'update']);
Route::patch('/order-items/{id}', [OrderItemController::class, 'update']);
Route::delete('/order-items/{id}', [OrderItemController::class, 'destroy']);

// Simple Order API (as per your requirements)
Route::post('/simple-orders', [SimpleOrderController::class, 'store']);
Route::get('/simple-orders/{id}', [SimpleOrderController::class, 'show']);

// Shipping routes
Route::post('/shipping/webhook', [ShippingController::class, 'handleWebhook']); // Leajlak webhook
Route::post('/shipping/shadda/webhook', [ShippingController::class, 'handleShaddaWebhook']); // Shadda webhook
Route::post('/create-order', [ShippingController::class, 'createOrder']);
Route::get('/shipping/status/{dspOrderId}', [ShippingController::class, 'getStatus']);
Route::post('/shipping/cancel/{dspOrderId}', [ShippingController::class, 'cancel']);

// Fines routes - Delete all fines
Route::match(['GET', 'DELETE', 'POST'], '/fines', function() {
    try {
        // Check if table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('fines')) {
            return response()->json([
                'success' => false,
                'message' => 'جدول الغرامات غير موجود في قاعدة البيانات'
            ], 404);
        }

        // Get count before deletion
        $count = \Illuminate\Support\Facades\DB::table('fines')->count();

        if ($count === 0) {
            return response()->json([
                'success' => true,
                'message' => 'جدول الغرامات فارغ بالفعل',
                'deleted_count' => 0
            ], 200);
        }

        // Delete all records
        $deleted = \Illuminate\Support\Facades\DB::table('fines')->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف {$deleted} سجل بنجاح من جدول الغرامات",
            'deleted_count' => $deleted
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error deleting fines', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف البيانات: ' . $e->getMessage()
        ], 500);
    }
});

// Order tracking API for chatbot
Route::get('/order/{id}', function($id) {
    try {
        $order = \App\Models\LinkOrder::with('restaurant')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'full_name' => $order->full_name,
                'phone_number' => $order->phone_number,
                'total' => $order->total,
                'cart_items' => $order->cart_items,
                'restaurant' => $order->restaurant->name,
                'created_at' => $order->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error'
        ], 500);
    }
});
