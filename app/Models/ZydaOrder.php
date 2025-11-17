<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZydaOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'location',
        'total_amount',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'decimal:2',
    ];
}

