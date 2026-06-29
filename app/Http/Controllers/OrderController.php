<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Support\OrderItemOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Check if user is a branch or regular user
        $user = Auth::guard('web')->user();
        $branch = Auth::guard('branches')->user();
        $isBranch = $branch !== null;
        $branchId = $branch?->id;

        Log::info('📋 Orders page accessed', [
            'type' => $isBranch ? 'branch' : 'user',
            'id' => $isBranch ? $branchId : $user?->id,
            'name' => $isBranch ? $branch?->name : $user?->name,
        ]);

        // Only show orders that have been successfully paid
        // AND (either not scheduled OR scheduled_for <= now)
        $orderQuery = Order::with(['user', 'restaurant', 'orderItems.menuItem', 'zydaOrder'])
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('scheduled_for')
                      ->orWhere('scheduled_for', '<=', now());
            });

        // Apply branch filtering if a branch is logged in
        if ($isBranch && $branchId) {
            $orderQuery->where('branch_id', $branchId);
        }

        $orders = $orderQuery->latest()->get();

        // Add calculated fields for each order
        $orders = $orders->map(function ($order) {
            $order->setAttribute('items_count', $order->orderItems->sum('quantity'));
            $order->setAttribute('items_subtotal', $order->orderItems->sum('subtotal'));
            return $order;
        });

        // Split orders into open (current) and closed (delivered / cancelled)
        $closedOrders = $orders->filter(function ($order) {
            $status = strtolower($order->status ?? '');
            $shippingStatus = strtolower($order->shipping_status ?? '');

            return in_array($status, ['delivered', 'cancelled'], true)
                || in_array($shippingStatus, ['delivered', 'cancelled'], true)
                || !is_null($order->delivered_at);
        });

        $openOrders = $orders->reject(function ($order) use ($closedOrders) {
            return $closedOrders->contains('id', $order->id);
        });

        // Calculate statistics
        // New orders: orders that haven't been accepted yet (status is pending OR shipping_status is New Order)
        $statsQuery = Order::where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('scheduled_for')
                      ->orWhere('scheduled_for', '<=', now());
            });

        // Apply branch filtering if a branch is logged in
        if ($isBranch && $branchId) {
            $statsQuery->where('branch_id', $branchId);
        }

        $totalNewOrders = (clone $statsQuery)
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
        $totalClosedOrders = (clone $statsQuery)
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
        $sampleClosedOrders = (clone $statsQuery)
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
            'open_orders_count' => $openOrders->count(),
            'closed_orders_count' => $closedOrders->count(),
            'total_new_orders' => $totalNewOrders,
            'total_closed_orders' => $totalClosedOrders,
            'sample_closed_orders' => $sampleClosedOrders,
        ]);

        return Inertia::render('Orders', [
            // Current / open orders (in-progress)
            'orders' => $openOrders->values(),
            // Closed orders (delivered / cancelled) – shown in a separate section
            'closed_orders' => $closedOrders->values(),
            'statistics' => [
                'total_new_orders' => $totalNewOrders,
                'total_closed_orders' => $totalClosedOrders,
            ],
            'is_branch_user' => $isBranch,
            'branches' => $isBranch
                ? []
                : Branch::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = \App\Models\User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $restaurants = \App\Models\Restaurant::select('id', 'name', 'shop_id')
            ->orderBy('name')
            ->get();

        $statusOptions = [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'تم التأكيد',
            'preparing' => 'قيد التحضير',
            'ready' => 'جاهز للاستلام',
            'delivering' => 'جاري التوصيل',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
        ];

        $paymentStatusOptions = [
            'pending' => 'قيد الانتظار',
            'paid' => 'مدفوع',
            'failed' => 'فشل الدفع',
        ];

        $paymentMethodOptions = [
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'online' => 'دفع إلكتروني',
        ];

        return Inertia::render('Orders/Create', [
            'users' => $users,
            'restaurants' => $restaurants,
            'statusOptions' => $statusOptions,
            'paymentStatusOptions' => $paymentStatusOptions,
            'paymentMethodOptions' => $paymentMethodOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'restaurant_id' => 'required|exists:restaurants,id',
            'delivery_name' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string|max:1000',
            'sound' => 'nullable|boolean',
            // Optional Google Maps / location link to extract coordinates for shipping
            'location_link' => 'nullable|string|max:2048',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id'          => 'required|exists:menu_items,id',
            'items.*.item_name'             => 'required|string|max:255',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.price'                 => 'required|numeric|min:0',
            'items.*.item_options'          => 'sometimes|nullable|array',
            'items.*.item_options.*.name'   => 'required_with:items.*.item_options|string|max:255',
            'items.*.item_options.*.quantity' => 'required_with:items.*.item_options|integer|min:1',
            // Scheduling / execution time (اختياري)
            'execution_type' => 'nullable|in:now,scheduled',
            'scheduled_for' => 'nullable|required_if:execution_type,scheduled|date|after:now',
        ]);

        $restaurant = \App\Models\Restaurant::findOrFail($validated['restaurant_id']);
        $shopId = $restaurant->shop_id ?? '210'; // Default: Gather Us

        $itemsCollection = collect($validated['items'])->map(function ($item) {
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            return [
                'menu_item_id' => (int) $item['menu_item_id'],
                'item_name'    => $item['item_name'],
                'quantity'     => $quantity,
                'price'        => $price,
                'subtotal'     => round($price * $quantity, 2),
                'item_options' => OrderItemOptions::fromPayload($item),
            ];
        });

        $deliveryFee = isset($validated['delivery_fee']) ? (float) $validated['delivery_fee'] : 0;
        $tax = isset($validated['tax']) ? (float) $validated['tax'] : 0;
        $subtotal = $itemsCollection->sum('subtotal');
        $total = round($subtotal + $deliveryFee + $tax, 2);

        $status = 'pending';
        $paymentStatus = 'paid';
        $paymentMethod = 'online';
        $statusMap = [
            'pending' => 'New Order',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'delivering' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        // Determine scheduled time (if any)
        $scheduledFor = null;
        if (($validated['execution_type'] ?? 'now') === 'scheduled' && !empty($validated['scheduled_for'] ?? null)) {
            $scheduledFor = $validated['scheduled_for'];
        }

        // Extract coordinates from optional Google Maps / location link
        $customerLatitude = null;
        $customerLongitude = null;
        if (!empty($validated['location_link'] ?? null)) {
            $coords = $this->parseCoordinatesFromLocation($validated['location_link']);
            if ($coords) {
                $customerLatitude = $coords['latitude'];
                $customerLongitude = $coords['longitude'];
            }
        }

        // Find nearest branch based on customer coordinates
        $branch = null;
        if ($customerLatitude !== null && $customerLongitude !== null) {
            $branch = \App\Services\BranchService::findNearestBranch($customerLatitude, $customerLongitude);
        }

        // Get shop_id from branch_restaurant_shop_ids if branch is found
        if ($branch) {
            $shopId = \App\Models\BranchRestaurantShopId::getShopId($branch->id, $validated['restaurant_id']) ?? $shopId;
        }

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'user_id' => $validated['user_id'],
            'restaurant_id' => $validated['restaurant_id'],
            'branch_id' => $branch?->id,
            'shop_id' => $shopId,
            'status' => $status,
            'shipping_status' => $statusMap[$status] ?? 'New Order',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'total' => $total,
            'delivery_address' => $validated['delivery_address'],
            'delivery_phone' => $validated['delivery_phone'],
            'delivery_name' => $validated['delivery_name'],
            'special_instructions' => $validated['special_instructions'] ?? null,
            'customer_latitude' => $customerLatitude,
            'customer_longitude' => $customerLongitude,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'source' => 'internal',
            'sound' => (bool) ($validated['sound'] ?? false),
            'scheduled_for' => $scheduledFor,
        ]);

        $order->orderItems()->createMany(
            $itemsCollection->map(function ($item) {
                return [
                    'menu_item_id' => $item['menu_item_id'],
                    'item_name'    => $item['item_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $item['subtotal'],
                    'item_options' => OrderItemOptions::fromPayload($item),
                ];
            })->toArray()
        );

        return redirect()
            ->route('orders.index')
            ->with('success', 'تم إنشاء الطلب بنجاح (رقم الطلب: ' . $order->order_number . ')');
    }

    /**
     * Create a test order for kitchen / orders flow testing.
     */
    public function createTestOrder(Request $request)
    {
        try {
            $loggedInBranch = Auth::guard('branches')->user();
            $user = \App\Models\User::first();

            if (!$user) {
                return redirect()->back()->with('error', 'يجب وجود مستخدم واحد على الأقل في قاعدة البيانات');
            }

            if ($loggedInBranch) {
                $branch = $loggedInBranch;
            } else {
                $validated = $request->validate([
                    'branch_id' => 'required|exists:branches,id',
                ]);
                $branch = Branch::where('status', 'active')->find($validated['branch_id']);

                if (!$branch) {
                    return redirect()->back()->with('error', 'الفرع المحدد غير نشط أو غير موجود');
                }
            }

            $mapping = \App\Models\BranchRestaurantShopId::where('branch_id', $branch->id)->first();
            $restaurant = $mapping
                ? \App\Models\Restaurant::find($mapping->restaurant_id)
                : \App\Models\Restaurant::first();
            $branchId = $branch->id;
            $shopId = $mapping?->shop_id;
            $customerLatitude = 24.7136;
            $customerLongitude = 46.6753;

            if ($branch->latitude && $branch->longitude) {
                $customerLatitude = (float) $branch->latitude + 0.001;
                $customerLongitude = (float) $branch->longitude + 0.001;
            }

            if (!$restaurant) {
                return redirect()->back()->with('error', 'يجب وجود مطعم واحد على الأقل في قاعدة البيانات');
            }

            $shopId = $shopId ?? $restaurant->shop_id ?? (string) $restaurant->id;

            $menuItem = \App\Models\MenuItem::where('restaurant_id', $restaurant->id)->first();
            if (!$menuItem) {
                return redirect()->back()->with('error', 'يجب وجود منتج واحد على الأقل في المطعم لإنشاء طلب تجريبي');
            }

            $orderNumber = 'TEST-' . now()->format('YmdHis');

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branchId,
                'shop_id' => $shopId,
                'status' => 'pending',
                'source' => 'test',
                'shipping_status' => 'New Order',
                'subtotal' => 50.00,
                'delivery_fee' => 10.00,
                'tax' => 5.00,
                'total' => 65.00,
                'delivery_address' => 'Test address - 123 King Fahd Road, Riyadh',
                'delivery_phone' => '0500000000',
                'delivery_name' => 'Test Customer',
                'customer_latitude' => $customerLatitude,
                'customer_longitude' => $customerLongitude,
                'special_instructions' => 'Test order - طلب تجريبي',
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'sound' => true,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'price' => 25.00,
                'quantity' => 2,
                'subtotal' => 50.00,
            ]);

            Log::info('Test order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
                'restaurant_id' => $restaurant->id,
                'created_by' => $loggedInBranch ? 'branch' : 'admin',
            ]);

            return redirect()->route('orders.index')->with('success', 'تم إنشاء طلب تجريبي بنجاح!');
        } catch (\Exception $e) {
            Log::error('Error creating test order: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['user', 'restaurant', 'branch', 'orderItems.menuItem'])->find($id);

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
        
        // Prevent updating status if order is already delivered
        if ($order->status === 'delivered' || 
            $order->shipping_status === 'Delivered' || 
            !empty($order->delivered_at)) {
            return redirect()->back()
                ->with('error', 'لا يمكن تعديل حالة الطلب بعد التسليم!');
        }
        
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

        // If status is confirmed OR preparing, turn off sound
        if ($request->status === 'confirmed' || $request->status === 'preparing') {
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
        $order = Order::findOrFail($id);
        
        // Delete related order items
        $order->orderItems()->delete();
        
        // Delete the order
        $order->delete();
        
        Log::info('Order deleted', [
            'order_id' => $id,
            'order_number' => $order->order_number,
        ]);
        
        return redirect()->route('orders.index')->with('success', 'تم حذف الطلب بنجاح!');
    }

    /**
     * Delete all test orders
     */
    public function deleteTestOrders()
    {
        try {
            $testOrders = Order::where(function ($query) {
                $query->where('source', 'test')
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('order_number', 'like', 'TEST-%')
                            ->where('source', 'internal');
                    });
            })->get();
            
            $count = $testOrders->count();
            
            if ($count === 0) {
                return redirect()->route('orders.index')->with('info', 'لا توجد طلبات تجريبية للحذف.');
            }
            
            // Delete order items for each test order
            foreach ($testOrders as $order) {
                $order->orderItems()->delete();
            }
            
            $deleted = Order::where(function ($query) {
                $query->where('source', 'test')
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('order_number', 'like', 'TEST-%')
                            ->where('source', 'internal');
                    });
            })->delete();
            
            Log::info('Test orders deleted', [
                'count' => $deleted,
            ]);
            
            return redirect()->route('orders.index')->with('success', "تم حذف {$count} طلب تجريبي بنجاح!");
        } catch (\Exception $e) {
            Log::error('Error deleting test orders: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الطلبات التجريبية: ' . $e->getMessage());
        }
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
            // This will automatically save order_reference if it exists in the order
            return $order->createInvoice();
        } catch (\Exception $e) {
            Log::error('Failed to create invoice for order ' . $order->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Resend order to shipping company
     */
    public function resendToShipping(string $id)
    {
        $order = Order::findOrFail($id);

        Log::info('🔄 Manual resend order to shipping company', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'shop_id' => $order->shop_id,
            'current_dsp_order_id' => $order->dsp_order_id,
        ]);

        // Check if order already has dsp_order_id
        if (!empty($order->dsp_order_id)) {
            return redirect()->back()
                ->with('info', 'الطلب تم إرساله بالفعل إلى شركة الشحن (DSP Order ID: ' . $order->dsp_order_id . ')');
        }

        if (!empty($order->is_branch_pickup)) {
            return redirect()->back()
                ->with('info', 'هذا طلب استلام من الفرع ولا يُرسل إلى شركة الشحن.');
        }

        // Check if order has shop_id
        if (empty($order->shop_id)) {
            return redirect()->back()
                ->with('error', 'الطلب لا يحتوي على shop_id. يرجى التأكد من إعدادات المطعم.');
        }

        try {
            $shippingService = new \App\Services\ShippingService();
            $shippingResult = $shippingService->createOrder($order);

            if ($shippingResult && isset($shippingResult['dsp_order_id'])) {
                // Update order with shipping information
                $order->dsp_order_id = $shippingResult['dsp_order_id'];
                $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
                $order->save();

                Log::info('✅ Order resent to shipping company successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'dsp_order_id' => $order->dsp_order_id,
                    'shipping_status' => $order->shipping_status,
                ]);

                return redirect()->back()
                    ->with('success', 'تم إرسال الطلب إلى شركة الشحن بنجاح! (DSP Order ID: ' . $order->dsp_order_id . ')');
            } else {
                Log::warning('⚠️ Failed to resend order to shipping company', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'reason' => 'Shipping service returned null or no dsp_order_id',
                ]);

                return redirect()->back()
                    ->with('error', 'فشل إرسال الطلب إلى شركة الشحن. يرجى مراجعة السجلات (storage/logs/laravel.log) للتفاصيل.');
            }
        } catch (\Exception $e) {
            Log::error('❌ Error resending order to shipping company', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage());
        }
    }

    protected function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', today())->count() + 1;
        return 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Parse a Google Maps (or any) location string to extract coordinates.
     * Supports:
     * - Full Google Maps URLs with q=lat,lng
     * - URLs with @lat,lng in path
     * - Plain "lat,lng" text
     */
    protected function parseCoordinatesFromLocation(?string $location): ?array
    {
        if (empty($location)) {
            return null;
        }

        $trimmed = trim($location);

        // 1) Try to match "lat,lng" anywhere in the string
        if (preg_match('/([-+]?\\d+\\.?\\d*),\\s*([-+]?\\d+\\.?\\d*)/', $trimmed, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
        }

        // 2) If it's a URL, try to parse query params and @lat,lng in path
        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            // From q=lat,lng
            $query = parse_url($trimmed, PHP_URL_QUERY);
            if ($query) {
                parse_str($query, $params);
                if (!empty($params['q']) && is_string($params['q'])) {
                    if (preg_match('/([-+]?\\d+\\.?\\d*),\\s*([-+]?\\d+\\.?\\d*)/', $params['q'], $m)) {
                        $lat = (float) $m[1];
                        $lng = (float) $m[2];
                        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                            return [
                                'latitude' => $lat,
                                'longitude' => $lng,
                            ];
                        }
                    }
                }
            }

            // From @lat,lng in path
            if (preg_match('/@([-+]?\\d+\\.?\\d*),\\s*([-+]?\\d+\\.?\\d*)/', $trimmed, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ];
                }
            }
        }

        // If nothing worked, return null (no valid coordinates)
        return null;
    }
}

