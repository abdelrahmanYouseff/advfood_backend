<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZydaOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'name',
        'phone',
        'address',
        'location',
        'latitude',
        'longitude',
        'total_amount',
        'items',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'decimal:2',
    ];
}

