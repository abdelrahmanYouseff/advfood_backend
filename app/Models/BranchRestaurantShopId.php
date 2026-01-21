<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchRestaurantShopId extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'shop_id',
    ];

    /**
     * Get the branch that owns this shop ID mapping.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the restaurant for this shop ID mapping.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get shop_id for a specific branch and restaurant.
     *
     * @param int $branchId
     * @param int $restaurantId
     * @return string|null
     */
    public static function getShopId(int $branchId, int $restaurantId): ?string
    {
        $mapping = self::where('branch_id', $branchId)
            ->where('restaurant_id', $restaurantId)
            ->first();
        
        return $mapping?->shop_id;
    }
}
