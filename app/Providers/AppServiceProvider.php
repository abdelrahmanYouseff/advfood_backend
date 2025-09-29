<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\LinkOrder;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share pending orders count with all pages
        Inertia::share([
            'orders' => function () {
                return Order::with(['user', 'restaurant'])->get();
            },
            'linkOrders' => function () {
                return LinkOrder::with(['restaurant'])->get();
            }
        ]);
    }
}
