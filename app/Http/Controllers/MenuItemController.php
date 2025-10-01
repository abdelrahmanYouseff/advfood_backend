<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menuItems = MenuItem::with(['restaurant'])->latest()->get();

        return Inertia::render('MenuItems', [
            'menuItems' => $menuItems,
            'restaurants' => Restaurant::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $restaurants = Restaurant::all();

        return Inertia::render('MenuItemCreate', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'preparation_time' => 'integer|min:1',
            'ingredients' => 'nullable|string',
            'allergens' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = $imagePath;
        }

        MenuItem::create($validated);

        return redirect()->route('menu-items.index')
            ->with('success', 'تم إضافة المنتج بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $menuItem = MenuItem::with(['restaurant'])->findOrFail($id);

        return Inertia::render('MenuItemShow', [
            'menuItem' => $menuItem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $menuItem = MenuItem::with(['restaurant'])->findOrFail($id);
        $restaurants = Restaurant::all();

        return Inertia::render('MenuItemEdit', [
            'menuItem' => $menuItem,
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $menuItem = MenuItem::findOrFail($id);

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'preparation_time' => 'integer|min:1',
            'ingredients' => 'nullable|string',
            'allergens' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
                Storage::disk('public')->delete($menuItem->image);
            }

            $imagePath = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = $imagePath;
        }

        $menuItem->update($validated);

        return redirect()->route('menu-items.index')
            ->with('success', 'تم تحديث المنتج بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $menuItem = MenuItem::findOrFail($id);

        // Delete the image file if it exists
        if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
            Storage::disk('public')->delete($menuItem->image);
        }

        $menuItem->delete();

        return redirect()->route('menu-items.index')
            ->with('success', 'تم حذف المنتج بنجاح');
    }
}
