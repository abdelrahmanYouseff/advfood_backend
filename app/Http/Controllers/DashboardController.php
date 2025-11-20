<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Invoice;
use App\Models\ZydaOrder;
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

        // Get filter from request (pending or received)
        // pending = order_id is null (not linked to an order yet)
        // received = order_id is not null (already linked to an order)
        $filter = request()->get('zyda_filter', 'pending');
        
        // Show Zyda orders based on order_id filter
        $zydaQuery = ZydaOrder::query();
        
        if ($filter === 'received') {
            $zydaQuery->whereNotNull('order_id');
        } else {
            // Default: show pending orders (order_id is null - not linked yet)
            $zydaQuery->whereNull('order_id');
        }
        
        $zyda_orders = $zydaQuery
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // Summary for both pending and received
        $zyda_summary = [
            'pending_count' => ZydaOrder::whereNull('order_id')->count(),
            'received_count' => ZydaOrder::whereNotNull('order_id')->count(),
            'pending_total' => ZydaOrder::whereNull('order_id')->sum('total_amount'),
            'received_total' => ZydaOrder::whereNotNull('order_id')->sum('total_amount'),
            'current_filter' => $filter,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'top_restaurants' => $top_restaurants,
            'zyda_orders' => $zyda_orders,
            'zyda_summary' => $zyda_summary,
        ]);
    }
}
