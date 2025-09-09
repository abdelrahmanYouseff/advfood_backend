<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'status',
        'full_name',
        'phone_number',
        'building_no',
        'floor',
        'apartment_number',
        'street',
        'note',
        'total',
        'cart_items',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'total' => 'float',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
