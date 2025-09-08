<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Auth::user()->locations()->active()->get();

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'address' => 'required|string',
            'building_number' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'apartment' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();

        // إذا كان الموقع الجديد هو الافتراضي، قم بإلغاء الافتراضي من المواقع الأخرى
        if ($validated['is_default'] ?? false) {
            Auth::user()->locations()->update(['is_default' => false]);
        }

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الموقع بنجاح',
            'data' => $location
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        // تأكد من أن الموقع يخص المستخدم الحالي
        if ($location->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا الموقع'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        // تأكد من أن الموقع يخص المستخدم الحالي
        if ($location->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا الموقع'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'address' => 'required|string',
            'building_number' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'apartment' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_default' => 'boolean',
        ]);

        // إذا كان الموقع الجديد هو الافتراضي، قم بإلغاء الافتراضي من المواقع الأخرى
        if ($validated['is_default'] ?? false) {
            Auth::user()->locations()->where('id', '!=', $location->id)->update(['is_default' => false]);
        }

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الموقع بنجاح',
            'data' => $location
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        // تأكد من أن الموقع يخص المستخدم الحالي
        if ($location->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا الموقع'
            ], 403);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموقع بنجاح'
        ]);
    }

    /**
     * Set a location as default.
     */
    public function setDefault(Location $location)
    {
        // تأكد من أن الموقع يخص المستخدم الحالي
        if ($location->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا الموقع'
            ], 403);
        }

        // إلغاء الافتراضي من جميع المواقع
        Auth::user()->locations()->update(['is_default' => false]);

        // تعيين الموقع الحالي كافتراضي
        $location->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الموقع كافتراضي بنجاح',
            'data' => $location
        ]);
    }

    /**
     * Get user's default location.
     */
    public function getDefault()
    {
        $defaultLocation = Auth::user()->locations()->default()->active()->first();

        return response()->json([
            'success' => true,
            'data' => $defaultLocation
        ]);
    }
}
