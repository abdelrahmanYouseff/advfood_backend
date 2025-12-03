<?php

use App\Http\Controllers\AdController;
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
use Inertia\Inertia;

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);

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
