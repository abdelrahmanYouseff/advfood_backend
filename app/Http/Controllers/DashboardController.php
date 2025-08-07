<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_restaurants' => Restaurant::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::where('status', 'delivered')->whereDate('created_at', today())->sum('total'),
        ];

        $recent_orders = Order::with(['user', 'restaurant'])
            ->latest()
            ->take(5)
            ->get();

        $top_restaurants = Restaurant::withCount('orders')
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
