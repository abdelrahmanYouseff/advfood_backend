<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Display a listing of menu items.
     */
    public function index(Request $request)
    {
        $query = MenuItem::with(['restaurant']);

        // Filter by restaurant if provided
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by featured items
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort by price
        if ($request->has('sort_by_price')) {
            $query->orderBy('price', $request->sort_by_price === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $menuItems = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }

    /**
     * Store a newly created menu item.
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
            'sort_order' => 'integer|min:0',
            'preparation_time' => 'integer|min:1',
            'ingredients' => 'nullable|string',
            'allergens' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = $imagePath;
        }

        $menuItem = MenuItem::create($validated);
        $menuItem->load('restaurant');

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنتج بنجاح',
            'data' => $menuItem
        ], 201);
    }

    /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem)
    {
        $menuItem->load('restaurant');

        return response()->json([
            'success' => true,
            'data' => $menuItem
        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'restaurant_id' => 'sometimes|exists:restaurants,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
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
        $menuItem->load('restaurant');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح',
            'data' => $menuItem
        ]);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(MenuItem $menuItem)
    {
        // Delete the image file if it exists
        if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
            Storage::disk('public')->delete($menuItem->image);
        }

        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ]);
    }

    /**
     * Get menu items by restaurant.
     */
    public function getByRestaurant(Restaurant $restaurant, Request $request)
    {
        $query = $restaurant->menuItems();

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by featured items
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort by price
        if ($request->has('sort_by_price')) {
            $query->orderBy('price', $request->sort_by_price === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $menuItems = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'restaurant' => $restaurant,
            'data' => $menuItems
        ]);
    }

    /**
     * Get featured menu items.
     */
    public function getFeatured(Request $request)
    {
        $query = MenuItem::with(['restaurant'])
            ->where('is_featured', true)
            ->where('is_available', true);

        // Filter by restaurant if provided
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        $menuItems = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }

    /**
     * Toggle availability of menu item.
     */
    public function toggleAvailability(MenuItem $menuItem)
    {
        $menuItem->update(['is_available' => !$menuItem->is_available]);

        return response()->json([
            'success' => true,
            'message' => $menuItem->is_available ? 'تم تفعيل المنتج' : 'تم إلغاء تفعيل المنتج',
            'data' => $menuItem
        ]);
    }
}
