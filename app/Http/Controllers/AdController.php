<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ads = Ad::orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return Inertia::render('Ads', [
            'ads' => $ads,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AdCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ads', 'public');
            $validated['image'] = $imagePath;
        }

        // Set default values for other fields
        $validated['title'] = 'New Ad';
        $validated['description'] = null;
        $validated['link'] = null;
        $validated['type'] = 'banner';
        $validated['position'] = 'top';
        $validated['is_active'] = true;
        $validated['start_date'] = null;
        $validated['end_date'] = null;
        $validated['clicks_count'] = 0;
        $validated['views_count'] = 0;
        $validated['sort_order'] = 0;

        Ad::create($validated);

        return redirect()->route('ads.index')
            ->with('success', 'Ad created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ad $ad)
    {
        return Inertia::render('AdShow', [
            'ad' => $ad,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ad $ad)
    {
        return Inertia::render('AdEdit', [
            'ad' => $ad,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ad $ad)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'type' => 'required|in:banner,popup,sidebar',
            'position' => 'required|in:top,bottom,left,right,center',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'sort_order' => 'integer|min:0',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($ad->image && Storage::disk('public')->exists($ad->image)) {
                Storage::disk('public')->delete($ad->image);
            }

            $imagePath = $request->file('image')->store('ads', 'public');
            $validated['image'] = $imagePath;
        }

        $ad->update($validated);

        return redirect()->route('ads.index')
            ->with('success', 'Ad updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ad $ad)
    {
        // Delete the image file if it exists
        if ($ad->image && Storage::disk('public')->exists($ad->image)) {
            Storage::disk('public')->delete($ad->image);
        }

        $ad->delete();

        return redirect()->route('ads.index')
            ->with('success', 'Ad deleted successfully');
    }

    /**
     * Toggle ad status.
     */
    public function toggleStatus(Ad $ad)
    {
        $ad->update(['is_active' => !$ad->is_active]);

        return redirect()->route('ads.index')
            ->with('success', $ad->is_active ? 'Ad activated successfully' : 'Ad deactivated successfully');
    }
}
