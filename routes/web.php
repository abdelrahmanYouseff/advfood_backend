<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryTripController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RestLinkController;
use App\Http\Controllers\LinkOrderController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Public restaurant links page (Linktree style)
Route::get('/rest-link', [RestLinkController::class, 'index'])->name('rest-link');

// Individual restaurant menu page
Route::get('/restaurant/{id}', [RestLinkController::class, 'show'])->name('restaurant.menu');

// Checkout pages
Route::get('/checkout/customer-details', [RestLinkController::class, 'customerDetails'])->name('checkout.customer-details');
Route::get('/checkout/payment', [RestLinkController::class, 'payment'])->name('checkout.payment');
Route::post('/checkout/save-order', [RestLinkController::class, 'saveOrder'])->name('checkout.save-order');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);

    // Restaurants
    Route::resource('restaurants', RestaurantController::class);


    // Menu Items
    Route::resource('menu-items', MenuItemController::class);

    // Orders
    Route::resource('orders', OrderController::class);
    Route::patch('orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');

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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
