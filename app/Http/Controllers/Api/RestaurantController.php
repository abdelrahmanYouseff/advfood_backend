<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    /**
     * Get all restaurants with name and logo only
     */
    public function index()
    {
        $restaurants = Restaurant::select('id', 'name', 'logo')
            ->where('is_active', true)
            ->get()
            ->map(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'logo' => $restaurant->logo ? asset('storage/' . $restaurant->logo) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $restaurants
        ]);
    }
}
