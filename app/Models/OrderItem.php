<?php

namespace App\Models;

use App\Support\OrderItemOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'product_id',
        'item_name',
        'price',
        'quantity',
        'subtotal',
        'special_instructions',
        'item_options',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected $hidden = [
        'item_option',
    ];

    protected function itemOptions(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $raw = $value ?? ($attributes['item_option'] ?? null);

                return OrderItemOptions::normalize($raw);
            },
            set: function (mixed $value) {
                if ($value === null) {
                    return null;
                }

                if (is_string($value)) {
                    return $value;
                }

                $normalized = OrderItemOptions::normalize($value) ?? $value;

                return json_encode($normalized, JSON_UNESCAPED_UNICODE);
            },
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
