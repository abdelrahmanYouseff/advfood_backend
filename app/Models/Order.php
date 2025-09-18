<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'restaurant_id',
        'status',
        'shop_id',
        'dsp_order_id',
        'shipping_status',
        'driver_name',
        'driver_phone',
        'driver_latitude',
        'driver_longitude',
        'subtotal',
        'delivery_fee',
        'tax',
        'total',
        'delivery_address',
        'delivery_phone',
        'delivery_name',
        'special_instructions',
        'payment_method',
        'payment_status',
        'estimated_delivery_time',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_delivery_time' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                // Get the highest order number for today and increment
                $today = date('Ymd');
                $latestOrder = static::where('order_number', 'like', "ORD-{$today}-%")
                                   ->orderBy('order_number', 'desc')
                                   ->first();

                if ($latestOrder) {
                    // Extract the sequence number and increment
                    $lastNumber = (int) substr($latestOrder->order_number, -4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 20; // Start from 0020 to avoid conflicts
                }

                $order->order_number = 'ORD-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
