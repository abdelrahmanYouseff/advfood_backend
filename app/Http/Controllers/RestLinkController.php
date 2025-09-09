<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestLinkController extends Controller
{
    /**
     * Display the restaurants link page (Linktree style)
     */
    public function index()
    {
        $restaurants = Restaurant::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('rest-link', compact('restaurants'));
    }

    public function show($id)
    {
        $restaurant = Restaurant::where('is_active', true)->findOrFail($id);
        $menuItems = $restaurant->menuItems()
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        return view('restaurant-menu', compact('restaurant', 'menuItems'));
    }

    public function customerDetails()
    {
        return view('checkout.customer-details');
    }

    public function payment()
    {
        return view('checkout.payment');
    }

    public function saveOrder(Request $request)
    {
        try {
            $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'building_no' => 'required|string|max:50',
                'floor' => 'required|string|max:50',
                'apartment_number' => 'required|string|max:50',
                'street' => 'required|string|max:255',
                'note' => 'nullable|string',
                'total' => 'required|numeric|min:0',
                'cart_items' => 'required|array',
            ]);

            $order = \App\Models\LinkOrder::create([
                'restaurant_id' => $request->restaurant_id,
                'status' => 'pending',
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'building_no' => $request->building_no,
                'floor' => $request->floor,
                'apartment_number' => $request->apartment_number,
                'street' => $request->street,
                'note' => $request->note,
                'total' => $request->total,
                'cart_items' => $request->cart_items,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order saved successfully',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving order: ' . $e->getMessage()
            ], 500);
        }
    }
}
