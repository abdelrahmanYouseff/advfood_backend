<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Invoice;
use App\Models\ZydaOrder;
use App\Models\WhatsappMsg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Check if user is a branch or regular user
        $user = Auth::guard('web')->user();
        $branch = Auth::guard('branches')->user();
        $isBranch = $branch !== null;
        $branchId = $branch?->id;

        Log::info('📊 Dashboard accessed', [
            'type' => $isBranch ? 'branch' : 'user',
            'id' => $isBranch ? $branchId : $user?->id,
            'name' => $isBranch ? $branch?->name : $user?->name,
        ]);

        // Initialize queries
        $orderQuery = Order::query();
        $invoiceQuery = Invoice::query();
        $zydaOrderQuery = ZydaOrder::query();

        // Apply branch filtering if a branch is logged in
        if ($isBranch && $branchId) {
            $orderQuery->where('branch_id', $branchId);
            $invoiceQuery->whereHas('order', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
            $zydaOrderQuery->whereHas('order', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $stats = [
            'total_restaurants' => Restaurant::count(), // Same restaurants for all branches
            'total_orders' => (clone $orderQuery)->where('payment_status', 'paid')->count(),
            'total_revenue' => (clone $invoiceQuery)->where('status', 'paid')->sum('total'),
            'pending_orders' => (clone $orderQuery)->where('status', 'pending')->where('payment_status', 'paid')->count(),
            'today_orders' => (clone $orderQuery)->whereDate('created_at', today())->where('payment_status', 'paid')->count(),
            'today_revenue' => (clone $invoiceQuery)->where('status', 'paid')->whereDate('created_at', today())->sum('total'),
        ];

        $recent_orders = (clone $orderQuery)
            ->with(['user', 'restaurant'])
            ->where('payment_status', 'paid')
            ->latest()
            ->take(5)
            ->get();

        $top_restaurants = Restaurant::withCount(['orders' => function ($query) use ($isBranch, $branchId) {
                $query->where('payment_status', 'paid');
                if ($isBranch && $branchId) {
                    $query->where('branch_id', $branchId);
                }
            }])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        // Get filter from request (pending or received)
        // pending = order_id is null (not linked to an order yet)
        // received = order_id is not null (already linked to an order)
        $filter = request()->get('zyda_filter', 'pending');
        
        // Show Zyda orders based on order_id filter
        $zydaQuery = clone $zydaOrderQuery;
        
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

        // Summary for both pending and received (with branch filtering)
        $zyda_summary = [
            'pending_count' => (clone $zydaOrderQuery)->whereNull('order_id')->count(),
            'received_count' => (clone $zydaOrderQuery)->whereNotNull('order_id')->count(),
            'pending_total' => (clone $zydaOrderQuery)->whereNull('order_id')->sum('total_amount'),
            'received_total' => (clone $zydaOrderQuery)->whereNotNull('order_id')->sum('total_amount'),
            'current_filter' => $filter,
        ];

        // Get WhatsApp messages (latest 50) - no branch filtering for now
        $whatsapp_messages = WhatsappMsg::orderByDesc('created_at')
            ->take(50)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'top_restaurants' => $top_restaurants,
            'zyda_orders' => $zyda_orders,
            'zyda_summary' => $zyda_summary,
            'whatsapp_messages' => $whatsapp_messages,
        ]);
    }
}
