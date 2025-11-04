<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        Log::info('📊 Dashboard accessed', [
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
        ]);

        $stats = [
            'total_restaurants' => Restaurant::count(),
            'total_orders' => Order::where('payment_status', 'paid')->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->where('payment_status', 'paid')->count(),
            'today_orders' => Order::whereDate('created_at', today())->where('payment_status', 'paid')->count(),
            'today_revenue' => Invoice::where('status', 'paid')->whereDate('created_at', today())->sum('total'),
        ];

        $recent_orders = Order::with(['user', 'restaurant'])
            ->where('payment_status', 'paid')
            ->latest()
            ->take(5)
            ->get();

        $top_restaurants = Restaurant::withCount(['orders' => function ($query) {
                $query->where('payment_status', 'paid');
            }])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'top_restaurants' => $top_restaurants,
        ]);
    }
}
