<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $restaurants = Restaurant::latest()->get();

        return Inertia::render('Restaurants', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('RestaurantCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'opening_time' => 'required|date_format:H:i:s',
            'closing_time' => 'required|date_format:H:i:s',
            'delivery_fee' => 'required|numeric|min:0',
            'delivery_time' => 'required|integer|min:1',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $logoPath = $request->file('logo')->store('restaurants/logos', 'public');
            $validated['logo'] = $logoPath;
        }

        Restaurant::create($validated);

        return redirect()->route('restaurants.index')
            ->with('success', 'تم إضافة المطعم بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $restaurant = Restaurant::with(['categories', 'menuItems'])->findOrFail($id);

        return Inertia::render('RestaurantShow', [
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        return Inertia::render('RestaurantEdit', [
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'address' => 'sometimes|string|max:500',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'sometimes|boolean',
            'opening_time' => 'sometimes|string',
            'closing_time' => 'sometimes|string',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'delivery_time' => 'sometimes|integer|min:1',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            // Delete old logo if exists
            if ($restaurant->logo && Storage::disk('public')->exists($restaurant->logo)) {
                Storage::disk('public')->delete($restaurant->logo);
            }
            $logoPath = $request->file('logo')->store('restaurants/logos', 'public');
            $validated['logo'] = $logoPath;
        }

        // Only update fields that were actually sent
        $restaurant->update($validated);

        return redirect()->route('restaurants.index')
            ->with('success', 'تم تحديث المطعم بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->delete();

        return redirect()->route('restaurants.index')
            ->with('success', 'تم حذف المطعم بنجاح');
    }
}
