<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['user', 'restaurant'])->latest()->get();

        return Inertia::render('Orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['user', 'restaurant', 'orderItems.menuItem'])->find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        // Get shipping details if available
        $shippingOrder = null;
        if (!empty($order->dsp_order_id)) {
            $shippingOrder = DB::table('shipping_orders')
                ->where('dsp_order_id', $order->dsp_order_id)
                ->first();
        }

        // Calculate totals from order items
        $itemsSubtotal = $order->orderItems->sum('subtotal');
        $itemsCount = $order->orderItems->sum('quantity');

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'shippingOrder' => $shippingOrder,
            'calculations' => [
                'items_subtotal' => $itemsSubtotal,
                'items_count' => $itemsCount,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
