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
        $filter = request()->get('zyda_filter', 'pending');
        
        // Show Zyda orders based on status filter
        $zydaQuery = ZydaOrder::query();
        
        if ($filter === 'received') {
            $zydaQuery->where('status', 'received');
        } else {
            // Default: show pending orders (status = pending or null)
            $zydaQuery->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status');
            });
        }
        
        $zyda_orders = $zydaQuery
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // Summary for both pending and received
        $zyda_summary = [
            'pending_count' => ZydaOrder::where(function($q) {
                $q->where('status', 'pending')->orWhereNull('status');
            })->count(),
            'received_count' => ZydaOrder::where('status', 'received')->count(),
            'pending_total' => ZydaOrder::where(function($q) {
                $q->where('status', 'pending')->orWhereNull('status');
            })->sum('total_amount'),
            'received_total' => ZydaOrder::where('status', 'received')->sum('total_amount'),
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
