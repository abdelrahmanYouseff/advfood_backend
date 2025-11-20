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

            // IMPORTANT: Immediately contact shipping company after order is created
            // This happens automatically when order is inserted into orders table
            // Goal: Get dsp_order_id from shipping company and update it in database
            \Illuminate\Support\Facades\Log::info('🔄 Order inserted into orders table - Starting automatic shipping company contact', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'shop_id' => $order->shop_id ?? 'NULL',
                'shop_id_type' => gettype($order->shop_id),
                'shop_id_empty' => empty($order->shop_id),
                'shop_id_not_empty' => !empty($order->shop_id),
                'payment_status' => $order->payment_status,
                'source' => $order->source ?? 'NULL',
                'step' => 'Order Model boot method - static::created hook',
            ]);

            // Send ALL orders that have shop_id to shipping company
            // This is critical: We need to get dsp_order_id immediately
            if (!empty($order->shop_id)) {
                try {
                    \Illuminate\Support\Facades\Log::info('📞 Contacting shipping company to get dsp_order_id', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                        'payment_status' => $order->payment_status,
                        'source' => $order->source ?? 'NULL',
                        'customer_name' => $order->delivery_name ?? 'NULL',
                        'customer_phone' => $order->delivery_phone ?? 'NULL',
                        'customer_address' => $order->delivery_address ?? 'NULL',
                        'has_coordinates' => !empty($order->customer_latitude) && !empty($order->customer_longitude),
                        'total' => $order->total ?? 0,
                        'step' => 'Calling ShippingService::createOrder()',
                    ]);

                    // Contact shipping company API to create order and get dsp_order_id
                    $shippingService = new \App\Services\ShippingService();
                    $shippingResult = $shippingService->createOrder($order);

                    if ($shippingResult && isset($shippingResult['dsp_order_id'])) {
                        // SUCCESS: Shipping company returned dsp_order_id
                        \Illuminate\Support\Facades\Log::info('✅ Shipping company responded with dsp_order_id', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id_received' => $shippingResult['dsp_order_id'],
                            'shipping_status' => $shippingResult['shipping_status'] ?? null,
                            'shop_id' => $order->shop_id,
                            'step' => 'Updating order in database with dsp_order_id',
                        ]);

                        // CRITICAL: Update order in database with dsp_order_id from shipping company
                            $order->dsp_order_id = $shippingResult['dsp_order_id'];
                            $order->shipping_status = $shippingResult['shipping_status'] ?? 'New Order';
                            $order->save();

                        \Illuminate\Support\Facades\Log::info('✅✅ Order updated in database with dsp_order_id - Order can now be tracked by shipping company', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'dsp_order_id_saved' => $order->dsp_order_id,
                            'shipping_status' => $order->shipping_status,
                            'database_updated' => true,
                            'step' => 'COMPLETED - Order sent to shipping company successfully',
                        ]);
                    } else {
                        // FAILURE: Shipping company did not return dsp_order_id
                        // Check logs immediately before this for detailed error from ShippingService
                        \Illuminate\Support\Facades\Log::error('❌ Shipping company did not return dsp_order_id', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'shop_id' => $order->shop_id,
                            'shipping_result' => $shippingResult,
                            'shipping_result_type' => gettype($shippingResult),
                            'shipping_result_keys' => array_keys($shippingResult ?? []),
                            'reason' => 'Shipping service returned null or no dsp_order_id',
                            'note' => 'Check logs immediately above for detailed error from ShippingService',
                            'check_for' => [
                                '📡 Shipping API Response Received (immediate)',
                                '❌ Failed to send order to shipping company',
                                '🔴 VALIDATION ERROR (422) FROM SHIPPING COMPANY:',
                                '🔴 AUTHENTICATION ERROR (401)',
                                '🛑 ShippingService::createOrder() returning NULL',
                            ],
                            'step' => 'FAILED - Order NOT sent to shipping company',
                        ]);

                        // Also log the order data that was sent to help debug
                        \Illuminate\Support\Facades\Log::info('📋 Order data that was sent to shipping company', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'shop_id' => $order->shop_id,
                            'delivery_name' => $order->delivery_name,
                            'delivery_phone' => $order->delivery_phone,
                            'delivery_address' => $order->delivery_address,
                            'customer_latitude' => $order->customer_latitude,
                            'customer_longitude' => $order->customer_longitude,
                            'total' => $order->total,
                            'payment_method' => $order->payment_method,
                            'payment_status' => $order->payment_status,
                            'source' => $order->source,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('❌ Exception while contacting shipping company', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'step' => 'EXCEPTION - Order NOT sent to shipping company',
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('⚠️ Order created but NOT sent to shipping company (missing shop_id)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'shop_id' => $order->shop_id ?? 'MISSING',
                    'payment_status' => $order->payment_status,
                    'source' => $order->source ?? 'NULL',
                    'note' => 'Order requires shop_id to be sent to shipping company',
                    'step' => 'SKIPPED - Missing shop_id',
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
