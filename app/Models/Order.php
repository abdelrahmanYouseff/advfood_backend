<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'sound',
        'estimated_delivery_time',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'sound' => 'boolean',
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

    public function deliveryTrips(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryTrip::class, 'delivery_trip_orders')
                    ->withPivot([
                        'sequence_order',
                        'delivery_status',
                        'picked_up_at',
                        'delivered_at',
                        'delivery_notes',
                        'delivery_fee'
                    ])
                    ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        // Create invoice automatically when order is created
        static::created(function ($order) {
            // Only create invoice for orders with status 'pending' or 'confirmed'
            if (in_array($order->status, ['pending', 'confirmed'])) {
                $order->createInvoice();
            }
        });
    }

    /**
     * Create invoice for this order
     */
    public function createInvoice()
    {
        try {
            $invoice = new \App\Models\Invoice();
            $invoice->order_id = $this->id;
            $invoice->user_id = $this->user_id;
            $invoice->restaurant_id = $this->restaurant_id;
            $invoice->subtotal = $this->subtotal;
            $invoice->delivery_fee = $this->delivery_fee;
            $invoice->tax = $this->tax;
            $invoice->total = $this->total;
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->due_date = now(); // Due immediately since it's paid
            $invoice->notes = 'Invoice for order: ' . $this->order_number;
            $invoice->save();

            return $invoice;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create invoice for order ' . $this->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
