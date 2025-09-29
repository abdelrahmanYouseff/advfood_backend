<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTrip;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryTripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryTrips = DeliveryTrip::with(['orders.user', 'orders.restaurant'])
            ->latest()
            ->get();

        return Inertia::render('DeliveryTrips', [
            'deliveryTrips' => $deliveryTrips,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $availableOrders = Order::where('status', 'confirmed')
            ->where('shipping_status', 'Ready')
            ->whereDoesntHave('deliveryTrips')
            ->with(['user', 'restaurant'])
            ->get();

        return Inertia::render('DeliveryTrips/Create', [
            'availableOrders' => $availableOrders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string|max:20',
            'vehicle_type' => 'required|string|in:bike,car,truck,motorcycle',
            'vehicle_number' => 'required|string|max:50',
            'selected_orders' => 'required|array|min:1',
            'selected_orders.*' => 'exists:orders,id',
            'notes' => 'nullable|string',
        ]);

        $deliveryTrip = DeliveryTrip::create([
            'trip_number' => DeliveryTrip::generateTripNumber(),
            'driver_name' => $request->driver_name,
            'driver_phone' => $request->driver_phone,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_number' => $request->vehicle_number,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        // Attach orders to the delivery trip
        foreach ($request->selected_orders as $index => $orderId) {
            $deliveryTrip->orders()->attach($orderId, [
                'sequence_order' => $index + 1,
                'delivery_status' => 'pending',
            ]);
        }

        return redirect()->route('delivery-trips.index')
            ->with('success', 'تم إنشاء رحلة التوصيل بنجاح!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $deliveryTrip = DeliveryTrip::with(['orders.user', 'orders.restaurant'])
            ->findOrFail($id);

        return Inertia::render('DeliveryTrips/Show', [
            'deliveryTrip' => $deliveryTrip,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $deliveryTrip = DeliveryTrip::with(['orders.user', 'orders.restaurant'])
            ->findOrFail($id);

        $availableOrders = Order::where('status', 'confirmed')
            ->where('shipping_status', 'Ready')
            ->whereDoesntHave('deliveryTrips', function ($query) use ($id) {
                $query->where('delivery_trip_id', '!=', $id);
            })
            ->with(['user', 'restaurant'])
            ->get();

        return Inertia::render('DeliveryTrips/Edit', [
            'deliveryTrip' => $deliveryTrip,
            'availableOrders' => $availableOrders,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $deliveryTrip = DeliveryTrip::findOrFail($id);

        $request->validate([
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string|max:20',
            'vehicle_type' => 'required|string|in:bike,car,truck,motorcycle',
            'vehicle_number' => 'required|string|max:50',
            'selected_orders' => 'required|array|min:1',
            'selected_orders.*' => 'exists:orders,id',
            'notes' => 'nullable|string',
        ]);

        $deliveryTrip->update([
            'driver_name' => $request->driver_name,
            'driver_phone' => $request->driver_phone,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_number' => $request->vehicle_number,
            'notes' => $request->notes,
        ]);

        // Update orders
        $deliveryTrip->orders()->detach();
        foreach ($request->selected_orders as $index => $orderId) {
            $deliveryTrip->orders()->attach($orderId, [
                'sequence_order' => $index + 1,
                'delivery_status' => 'pending',
            ]);
        }

        return redirect()->route('delivery-trips.index')
            ->with('success', 'تم تحديث رحلة التوصيل بنجاح!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deliveryTrip = DeliveryTrip::findOrFail($id);
        $deliveryTrip->delete();

        return redirect()->route('delivery-trips.index')
            ->with('success', 'تم حذف رحلة التوصيل بنجاح!');
    }

    /**
     * Start the delivery trip
     */
    public function start(string $id)
    {
        $deliveryTrip = DeliveryTrip::findOrFail($id);
        $deliveryTrip->start();

        return redirect()->back()
            ->with('success', 'تم بدء رحلة التوصيل!');
    }

    /**
     * Complete the delivery trip
     */
    public function complete(string $id)
    {
        $deliveryTrip = DeliveryTrip::findOrFail($id);
        $deliveryTrip->complete();

        // Update all orders to delivered
        foreach ($deliveryTrip->orders as $order) {
            $order->update([
                'shipping_status' => 'Delivered',
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'تم إكمال رحلة التوصيل!');
    }

    /**
     * Update order delivery status
     */
    public function updateOrderStatus(Request $request, string $tripId, string $orderId)
    {
        $deliveryTrip = DeliveryTrip::findOrFail($tripId);
        $order = $deliveryTrip->orders()->findOrFail($orderId);

        $request->validate([
            'delivery_status' => 'required|in:pending,picked_up,delivered,failed',
            'delivery_notes' => 'nullable|string',
        ]);

        $pivotData = [
            'delivery_status' => $request->delivery_status,
            'delivery_notes' => $request->delivery_notes,
        ];

        if ($request->delivery_status === 'picked_up') {
            $pivotData['picked_up_at'] = now();
        } elseif ($request->delivery_status === 'delivered') {
            $pivotData['delivered_at'] = now();
            $order->update([
                'shipping_status' => 'Delivered',
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        }

        $deliveryTrip->orders()->updateExistingPivot($orderId, $pivotData);

        return redirect()->back()
            ->with('success', 'تم تحديث حالة التسليم!');
    }
}
