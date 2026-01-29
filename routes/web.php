<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryTripController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\WebhookLogController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OnlineCustomerController;
use App\Http\Controllers\RestLinkController;
use App\Http\Controllers\LinkOrderController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZydaSyncController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestNoonController;
use App\Http\Controllers\ShippingController;
use Inertia\Inertia;

// Fines route - Delete all fines (public route for easy access - must be before other routes)
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
        \Illuminate\Support\Facades\Log::error('Error deleting fines', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف البيانات: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Public landing page for mobile application download (iOS)
Route::get('/mobile-application', function () {
    return view('mobile-application');
})->name('mobile-application');

// Public restaurant links page (Linktree style)
Route::get('/rest-link', [RestLinkController::class, 'index'])->name('rest-link');

// Tant Bakiza specific page
Route::get('/tant-bakiza', [RestLinkController::class, 'tantBakiza'])->name('tant-bakiza');

// Individual restaurant menu page
Route::get('/restaurant/{id}', [RestLinkController::class, 'show'])->name('restaurant.menu');

// Checkout pages
Route::get('/checkout/customer-details', [RestLinkController::class, 'customerDetails'])->name('checkout.customer-details');
Route::get('/checkout/payment', [RestLinkController::class, 'payment'])->name('checkout.payment');
Route::post('/checkout/save-order', [RestLinkController::class, 'saveOrder'])->name('checkout.save-order');
Route::post('/checkout/initiate-payment', [RestLinkController::class, 'initiatePayment'])->name('checkout.initiate-payment');

Route::middleware(['checkauth', 'verified_or_branch'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);

    // Branches
    Route::resource('branches', BranchController::class);
    Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');

    // Restaurants
    Route::resource('restaurants', RestaurantController::class);


    // Menu Items
    Route::resource('menu-items', MenuItemController::class);
    // Allow POST for updates with file uploads (Inertia converts PUT to POST with _method)
    Route::post('menu-items/{menuItem}', [MenuItemController::class, 'update'])->where('menuItem', '[0-9]+');

    // Orders
    Route::resource('orders', OrderController::class);
    Route::patch('orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/resend-shipping', [OrderController::class, 'resendToShipping'])->name('orders.resend-shipping');
    Route::post('orders/create-test', [OrderController::class, 'createTestOrder'])->name('orders.create-test');
    Route::delete('orders/delete-test', [OrderController::class, 'deleteTestOrders'])->name('orders.delete-test');
    Route::post('orders/sync-zyda', ZydaSyncController::class)->name('orders.sync-zyda');

    Route::get('online-customers', [OnlineCustomerController::class, 'index'])->name('online-customers.index');
    Route::get('online-customers/export', [OnlineCustomerController::class, 'export'])->name('online-customers.export');

    // Invoices
    Route::resource('invoices', InvoiceController::class);

    // Link Orders
    Route::resource('link-orders', LinkOrderController::class)->only(['index', 'show']);
    Route::post('link-orders/{linkOrder}/update-status', [LinkOrderController::class, 'updateStatus'])->name('link-orders.update-status');

    // Ads
    Route::resource('ads', AdController::class);
    Route::post('/ads/{ad}/toggle-status', [AdController::class, 'toggleStatus'])->name('ads.toggle-status');

    // Delivery Trips
    Route::resource('delivery-trips', DeliveryTripController::class);
    Route::patch('delivery-trips/{deliveryTrip}/start', [DeliveryTripController::class, 'start'])->name('delivery-trips.start');
    Route::patch('delivery-trips/{deliveryTrip}/complete', [DeliveryTripController::class, 'complete'])->name('delivery-trips.complete');
    Route::patch('delivery-trips/{deliveryTrip}/orders/{order}/update-status', [DeliveryTripController::class, 'updateOrderStatus'])->name('delivery-trips.update-order-status');

    // Logs - عرض الـ logs
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::post('logs/clear', [LogController::class, 'clear'])->name('logs.clear');
    Route::get('logs/download', [LogController::class, 'download'])->name('logs.download');

    // Webhooks - عرض الـ webhooks المستلمة
    Route::get('webhooks', [WebhookLogController::class, 'index'])->name('webhooks.index');
});

// Route to serve Tant Bakiza restaurant image (must be before other routes)
Route::get('/images/tant-bakiza-logo.png', function() {
    // Try multiple possible paths
    $paths = [
        public_path('images/Screenshot 1447-06-12 at 4.33.48 PM.png'),
        base_path('public/images/Screenshot 1447-06-12 at 4.33.48 PM.png'),
        storage_path('app/public/tant-bakiza-logo.png'),
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            \Log::info('Tant Bakiza image found at: ' . $path);
            return response()->file($path, ['Content-Type' => 'image/png']);
        }
    }

    \Log::error('Tant Bakiza image not found. Tried paths: ' . implode(', ', $paths));
    abort(404, 'Image not found');
})->name('images.tant-bakiza');

// Route to serve Delawa menu image (public route)
Route::get('/menu/delawa', function() {
    $imageUrl = asset('images/delawa-menu-portrait.jpeg');
    
    return response()->make('
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta property="og:title" content="قائمة الطعام - ديلاوا">
    <meta property="og:image" content="' . $imageUrl . '">
    <title>قائمة الطعام - ديلاوا</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }
        img {
            width: 100vw;
            height: 100vh;
            object-fit: contain;
            display: block;
        }
    </style>
</head>
<body>
    <img src="' . $imageUrl . '" alt="قائمة الطعام - ديلاوا">
</body>
</html>
    ', 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('menu.delawa');

// Public webhook routes for shipping providers (no authentication required)
Route::post('/webhook/shipping/shadda', [ShippingController::class, 'handleShaddaWebhook'])->name('webhook.shadda');



Route::get('/pay', [TestNoonController::class, 'createPayment'])->name('payment.create');
Route::get('/payment-success', [TestNoonController::class, 'success'])->name('payment.success');
Route::get('/payment-failed', [TestNoonController::class, 'fail'])->name('payment.fail');

// روابط إضافية لاختبار وتشخيص مشكلة نون
Route::get('/noon/status', [TestNoonController::class, 'checkApiStatus'])->name('noon.status');
Route::get('/noon/test', [TestNoonController::class, 'testConnection'])->name('noon.test');
Route::get('/noon/quick', [TestNoonController::class, 'quickTest'])->name('noon.quick');
Route::get('/noon/headers', [TestNoonController::class, 'testHeaders'])->name('noon.headers');
Route::get('/noon/final', [TestNoonController::class, 'finalTest'])->name('noon.final');
Route::get('/noon/newkey', [TestNoonController::class, 'testNewApiKey'])->name('noon.newkey');
Route::get('/noon/quicknew', [TestNoonController::class, 'quickNewKeyTest'])->name('noon.quicknew');
Route::get('/noon/envtest', [TestNoonController::class, 'finalEnvTest'])->name('noon.envtest');
Route::get('/noon/config', [TestNoonController::class, 'testWithConfig'])->name('noon.config');
Route::get('/noon/direct', [TestNoonController::class, 'finalDirectTest'])->name('noon.direct');
Route::get('/noon/envconfig', [TestNoonController::class, 'finalEnvConfigTest'])->name('noon.envconfig');
Route::get('/noon/auth', [TestNoonController::class, 'testAuthHeader'])->name('noon.auth');
Route::get('/noon/support', [TestNoonController::class, 'generateSupportTicket'])->name('noon.support');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
