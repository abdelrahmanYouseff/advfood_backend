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
        $user = request()->user();
        Log::info('📋 Orders page accessed', [
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
        ]);

        // Only show orders that have been successfully paid
        $orders = Order::with(['user', 'restaurant', 'orderItems.menuItem'])
            ->where('payment_status', 'paid')
            ->latest()
            ->get();

        // Add calculated fields for each order
        $orders = $orders->map(function ($order) {
            $order->setAttribute('items_count', $order->orderItems->sum('quantity'));
            $order->setAttribute('items_subtotal', $order->orderItems->sum('subtotal'));
            return $order;
        });

        // Calculate statistics
        // New orders: orders that haven't been accepted yet (status is pending OR shipping_status is New Order)
        $totalNewOrders = Order::where('payment_status', 'paid')
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('shipping_status', 'New Order');
            })
            ->where('status', '!=', 'confirmed') // Exclude confirmed orders
            ->count();

        // Closed orders: orders that are delivered or cancelled
        // Check multiple conditions to ensure we catch all closed orders
        // An order is considered closed if:
        // 1. status is 'delivered' or 'cancelled' (any case)
        // 2. shipping_status is 'Delivered' or 'Cancelled' (any case)
        // 3. delivered_at is not null (has delivery timestamp)
        $totalClosedOrders = Order::where('payment_status', 'paid')
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Check status field (case insensitive)
                    $q->whereRaw('LOWER(status) = ?', ['delivered'])
                      ->orWhereRaw('LOWER(status) = ?', ['cancelled']);
                })
                ->orWhere(function ($q) {
                    // Check shipping_status field (case insensitive)
                    $q->whereRaw('LOWER(shipping_status) = ?', ['delivered'])
                      ->orWhereRaw('LOWER(shipping_status) = ?', ['cancelled']);
                })
                ->orWhereNotNull('delivered_at'); // Has delivery timestamp
            })
            ->count();

        // Debug: Get sample of closed orders to verify logic
        $sampleClosedOrders = Order::where('payment_status', 'paid')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw('LOWER(status) = ?', ['delivered'])
                      ->orWhereRaw('LOWER(status) = ?', ['cancelled']);
                })
                ->orWhere(function ($q) {
                    $q->whereRaw('LOWER(shipping_status) = ?', ['delivered'])
                      ->orWhereRaw('LOWER(shipping_status) = ?', ['cancelled']);
                })
                ->orWhereNotNull('delivered_at');
            })
            ->select('id', 'order_number', 'status', 'shipping_status', 'delivered_at')
            ->get()
            ->toArray();

        Log::info('📋 Orders loaded with statistics', [
            'orders_count' => $orders->count(),
            'total_new_orders' => $totalNewOrders,
            'total_closed_orders' => $totalClosedOrders,
            'sample_closed_orders' => $sampleClosedOrders,
        ]);

        return Inertia::render('Orders', [
            'orders' => $orders,
            'statistics' => [
                'total_new_orders' => $totalNewOrders,
                'total_closed_orders' => $totalClosedOrders,
            ],
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
     * Create a test order for testing announcements
     */
    public function createTestOrder()
    {
        try {
            // Get first user and restaurant
            $user = \App\Models\User::first();
            $restaurant = \App\Models\Restaurant::first();

            if (!$user || !$restaurant) {
                return redirect()->back()->with('error', 'يجب وجود مستخدم ومطعم واحد على الأقل في قاعدة البيانات');
            }

            // Generate order number
            $orderNumber = 'TEST-' . time();

            // Get shop_id from restaurant
            $shopId = $restaurant->shop_id ?? (string) $restaurant->id;

            // Create test order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'shop_id' => $shopId, // Required for shipping
                'status' => 'pending',
                'shipping_status' => 'New Order',
                'subtotal' => 50.00,
                'delivery_fee' => 10.00,
                'tax' => 5.00,
                'total' => 65.00,
                'delivery_address' => 'مبنى 123، الطابق 2، شقة 5، شارع الملك فهد، الرياض',
                'delivery_phone' => '0501234567',
                'delivery_name' => 'عميل تجريبي',
                'payment_method' => 'online',
                'payment_status' => 'paid', // Must be paid to show in orders list and trigger shipping
                'sound' => true,
            ]);

            // Create a test order item
            $menuItem = \App\Models\MenuItem::where('restaurant_id', $restaurant->id)->first();
            if ($menuItem) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'price' => 25.00,
                    'quantity' => 2,
                    'subtotal' => 50.00,
                ]);
            }

            return redirect()->route('orders.index')->with('success', 'تم إنشاء طلب تجريبي بنجاح!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating test order: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
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
     * Update order status
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivering,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        
        // Map status to shipping_status
        $statusMap = [
            'pending' => 'New Order',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'delivering' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        $updateData = [
            'status' => $request->status,
            'shipping_status' => $statusMap[$request->status] ?? 'New Order',
        ];

        // If status is delivered, set delivered_at
        if ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        // If status is confirmed, turn off sound
        if ($request->status === 'confirmed') {
            $updateData['sound'] = false;
        }

        $order->update($updateData);

        Log::info('✅ Order status updated', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'new_status' => $order->status,
            'new_shipping_status' => $order->shipping_status,
        ]);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح!');
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
        $user = request()->user();
        Log::info('✅ ORDER ACCEPT ACTION', [
            'order_id' => $id,
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
        ]);

        $order = Order::findOrFail($id);

        Log::info('📦 Order found for accept', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_status' => $order->status,
            'current_shipping_status' => $order->shipping_status,
        ]);

        // Update order status to confirmed and turn off sound
        $order->update([
            'status' => 'confirmed',
            'shipping_status' => 'Confirmed',
            'sound' => false
        ]);

        Log::info('✅ Order status updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
            'new_shipping_status' => $order->shipping_status,
        ]);

        // Create invoice for the accepted order
        $invoice = $this->createInvoiceForOrder($order);

        if ($invoice) {
            Log::info('✅ Invoice created for accepted order', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
            ]);
            return redirect()->back()->with('success', 'Order accepted and invoice created successfully!');
        } else {
            Log::warning('⚠️ Failed to create invoice for accepted order', [
                'order_id' => $order->id,
            ]);
            return redirect()->back()->with('success', 'Order accepted, but failed to create invoice.');
        }
    }

    /**
     * Create invoice for an order
     */
    private function createInvoiceForOrder(Order $order)
    {
        try {
            // Check if invoice already exists for this order
            $existingInvoice = Invoice::where('order_id', $order->id)->first();
            
            if ($existingInvoice) {
                Log::info('Invoice already exists for order (accept action)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_id' => $existingInvoice->id,
                    'invoice_number' => $existingInvoice->invoice_number,
                ]);
                return $existingInvoice;
            }

            // Use the Order model's createInvoice method to ensure consistency
            return $order->createInvoice();
        } catch (\Exception $e) {
            Log::error('Failed to create invoice for order ' . $order->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
