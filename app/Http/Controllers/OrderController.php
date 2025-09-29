<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['user', 'restaurant', 'orderItems.menuItem'])->latest()->get();

        // Add calculated fields for each order
        $orders = $orders->map(function ($order) {
            $order->setAttribute('items_count', $order->orderItems->sum('quantity'));
            $order->setAttribute('items_subtotal', $order->orderItems->sum('subtotal'));
            return $order;
        });

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

    /**
     * Accept an order
     */
    public function accept(string $id)
    {
        $order = Order::findOrFail($id);

        // Update order status to confirmed and turn off sound
        $order->update([
            'status' => 'confirmed',
            'shipping_status' => 'Confirmed',
            'sound' => false
        ]);

        // Create invoice for the accepted order
        $invoice = $this->createInvoiceForOrder($order);

        if ($invoice) {
            return redirect()->back()->with('success', 'Order accepted and invoice created successfully!');
        } else {
            return redirect()->back()->with('success', 'Order accepted, but failed to create invoice.');
        }
    }

    /**
     * Create invoice for an order
     */
    private function createInvoiceForOrder(Order $order)
    {
        try {
            $invoice = new Invoice();
            $invoice->order_id = $order->id;
            $invoice->user_id = $order->user_id;
            $invoice->restaurant_id = $order->restaurant_id;
            $invoice->subtotal = $order->subtotal;
            $invoice->delivery_fee = $order->delivery_fee;
            $invoice->tax = $order->tax;
            $invoice->total = $order->total;
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->due_date = now(); // Due immediately since it's paid
            $invoice->notes = 'Invoice for order: ' . $order->order_number;
            $invoice->save();

            return $invoice;
        } catch (\Exception $e) {
            Log::error('Failed to create invoice for order ' . $order->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
