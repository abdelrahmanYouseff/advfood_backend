<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Restaurant;
use App\Models\Order;
use App\Models\LinkOrder;

class OnlineCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'link_order_id',
        'order_id',
        'full_name',
        'phone_number',
        'building_no',
        'floor',
        'apartment_number',
        'street',
        'note',
        'customer_latitude',
        'customer_longitude',
        'source',
        'latest_status',
        'meta',
    ];

    protected $casts = [
        'customer_latitude' => 'decimal:7',
        'customer_longitude' => 'decimal:7',
        'meta' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function linkOrder(): BelongsTo
    {
        return $this->belongsTo(LinkOrder::class);
    }
}


