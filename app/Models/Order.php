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
        'customer_latitude',
        'customer_longitude',
        'special_instructions',
        'payment_method',
        'source',
        'payment_status',
        'payment_order_reference',
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

            // Send order to shipping company automatically after order is created
            // Send ALL orders that have shop_id (regardless of payment status)
            if (!empty($order->shop_id)) {
                try {
                    \Illuminate\Support\Facades\Log::info('📦 Sending order to shipping company automatically after creation', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                        'payment_status' => $order->payment_status,
                    ]);

                    $shippingService = new \App\Services\ShippingService();
                    $shippingResult = $shippingService->createOrder($order);

                    if ($shippingResult) {
                        \Illuminate\Support\Facades\Log::info('✅ Order automatically sent to shipping company after creation', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id' => $shippingResult['dsp_order_id'] ?? null,
                            'shipping_status' => $shippingResult['shipping_status'] ?? null,
                            'shop_id' => $order->shop_id,
                            'customer_name' => $order->delivery_name,
                            'customer_phone' => $order->delivery_phone,
                            'customer_address' => $order->delivery_address,
                            'total' => $order->total,
                            'payment_method' => $order->payment_method,
                            'payment_status' => $order->payment_status,
                        ]);

                        // Update order with shipping information
                        if (isset($shippingResult['dsp_order_id'])) {
                            $order->dsp_order_id = $shippingResult['dsp_order_id'];
                            $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
                            $order->save();
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('⚠️ Failed to automatically send order to shipping company after creation', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'shop_id' => $order->shop_id,
                            'reason' => 'Shipping service returned null'
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('❌ Error automatically sending order to shipping company after creation', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('⚠️ Order created but not sent to shipping (missing shop_id)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                    'payment_status' => $order->payment_status,
                ]);
            }
        });

        // Also send to shipping when payment_status is updated to 'paid'
        static::updated(function ($order) {
            \Illuminate\Support\Facades\Log::info('🔄 ORDER MODEL UPDATED EVENT TRIGGERED', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status_changed' => $order->wasChanged('payment_status'),
                'current_payment_status' => $order->payment_status,
                'previous_payment_status' => $order->getOriginal('payment_status'),
                'dsp_order_id' => $order->dsp_order_id ?? 'MISSING',
                'shop_id' => $order->shop_id ?? 'MISSING',
                'environment' => config('app.env'),
            ]);

            // Check if payment_status was just changed to 'paid'
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                \Illuminate\Support\Facades\Log::info('✅ PAYMENT_STATUS CHANGED TO PAID - Checking conditions for shipping', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'dsp_order_id' => $order->dsp_order_id ?? 'MISSING',
                    'shop_id' => $order->shop_id ?? 'MISSING',
                    'has_dsp_order_id' => !empty($order->dsp_order_id),
                    'has_shop_id' => !empty($order->shop_id),
                ]);

                // Only send if not already sent (no dsp_order_id)
                if (empty($order->dsp_order_id) && !empty($order->shop_id)) {
                    \Illuminate\Support\Facades\Log::info('🚀 CONDITIONS MET - Calling ShippingService::createOrder', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                    ]);
                    try {
                        $shippingService = new \App\Services\ShippingService();
                        $shippingResult = $shippingService->createOrder($order);

                        if ($shippingResult) {
                            \Illuminate\Support\Facades\Log::info('✅ Order automatically sent to shipping company after payment confirmed', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'dsp_order_id' => $shippingResult['dsp_order_id'] ?? null,
                                'shipping_status' => $shippingResult['shipping_status'] ?? null,
                                'shop_id' => $order->shop_id,
                                'customer_name' => $order->delivery_name,
                                'customer_phone' => $order->delivery_phone,
                                'customer_address' => $order->delivery_address,
                                'total' => $order->total,
                            ]);

                            // Update order with shipping information
                            if (isset($shippingResult['dsp_order_id'])) {
                                $order->dsp_order_id = $shippingResult['dsp_order_id'];
                                $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
                                $order->save();
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('⚠️ Failed to automatically send order to shipping company after payment confirmed', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'shop_id' => $order->shop_id,
                                'reason' => 'Shipping service returned null'
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('❌ Error automatically sending order to shipping company after payment confirmed', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'shop_id' => $order->shop_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Create invoice for this order
     */
    public function createInvoice()
    {
        try {
            // Check if invoice already exists for this order
            $existingInvoice = \App\Models\Invoice::where('order_id', $this->id)->first();
            
            if ($existingInvoice) {
                \Illuminate\Support\Facades\Log::info('Invoice already exists for order', [
                    'order_id' => $this->id,
                    'order_number' => $this->order_number,
                    'invoice_id' => $existingInvoice->id,
                    'invoice_number' => $existingInvoice->invoice_number,
                ]);
                return $existingInvoice;
            }

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
            // Save payment order reference from order
            if (!empty($this->payment_order_reference)) {
                $invoice->order_reference = $this->payment_order_reference;
            }
            $invoice->save();

            \Illuminate\Support\Facades\Log::info('Invoice created successfully for order', [
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'order_reference' => $invoice->order_reference ?? 'N/A',
                'payment_order_reference' => $this->payment_order_reference ?? 'N/A',
            ]);

            return $invoice;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create invoice for order ' . $this->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
