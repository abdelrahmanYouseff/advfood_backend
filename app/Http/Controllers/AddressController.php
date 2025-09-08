<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addresses = Auth::user()->addresses()->active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $addresses
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

        // إذا كان العنوان الجديد هو الافتراضي، قم بإلغاء الافتراضي من العناوين الأخرى
        if ($validated['is_default'] ?? false) {
            Auth::user()->addresses()->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العنوان بنجاح',
            'data' => $address
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Address $address)
    {
        // تأكد من أن العنوان يخص المستخدم الحالي
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا العنوان'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Address $address)
    {
        // تأكد من أن العنوان يخص المستخدم الحالي
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا العنوان'
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

        // إذا كان العنوان الجديد هو الافتراضي، قم بإلغاء الافتراضي من العناوين الأخرى
        if ($validated['is_default'] ?? false) {
            Auth::user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنوان بنجاح',
            'data' => $address
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        // تأكد من أن العنوان يخص المستخدم الحالي
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا العنوان'
            ], 403);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان بنجاح'
        ]);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(Address $address)
    {
        // تأكد من أن العنوان يخص المستخدم الحالي
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا العنوان'
            ], 403);
        }

        // إلغاء الافتراضي من جميع العناوين
        Auth::user()->addresses()->update(['is_default' => false]);

        // تعيين العنوان الحالي كافتراضي
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين العنوان كافتراضي بنجاح',
            'data' => $address
        ]);
    }

    /**
     * Get user's default address.
     */
    public function getDefault()
    {
        $defaultAddress = Auth::user()->addresses()->default()->active()->first();

        return response()->json([
            'success' => true,
            'data' => $defaultAddress
        ]);
    }
}
