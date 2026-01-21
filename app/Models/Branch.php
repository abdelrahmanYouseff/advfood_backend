<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Branch extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'latitude',
        'longitude',
        'status',
        'dashboard_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the dashboard user that owns the branch.
     */
    public function dashboardUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dashboard_user_id');
    }

    /**
     * Get the orders for this branch.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the branch restaurant shop IDs.
     */
    public function branchRestaurantShopIds(): HasMany
    {
        return $this->hasMany(BranchRestaurantShopId::class);
    }

    /**
     * Get shop_id for a specific restaurant.
     */
    public function getShopIdForRestaurant($restaurantId): ?string
    {
        $branchRestaurantShopId = $this->branchRestaurantShopIds()
            ->where('restaurant_id', $restaurantId)
            ->first();
        
        return $branchRestaurantShopId?->shop_id;
    }
}
