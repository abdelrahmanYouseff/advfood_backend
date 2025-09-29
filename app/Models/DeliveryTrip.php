<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_number',
        'driver_name',
        'driver_phone',
        'vehicle_type',
        'vehicle_number',
        'status',
        'started_at',
        'completed_at',
        'notes',
        'total_distance',
        'fuel_cost',
        'driver_fee',
        'total_cost',
        'route_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'route_data' => 'array',
        'total_distance' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'driver_fee' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the orders for this delivery trip
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'delivery_trip_orders')
                    ->withPivot([
                        'sequence_order',
                        'delivery_status',
                        'picked_up_at',
                        'delivered_at',
                        'delivery_notes',
                        'delivery_fee'
                    ])
                    ->withTimestamps()
                    ->orderBy('delivery_trip_orders.sequence_order');
    }

    /**
     * Generate unique trip number
     */
    public static function generateTripNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return 'TRIP-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Start the delivery trip
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the delivery trip
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Get status in Arabic
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'معلق',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    /**
     * Get vehicle type in Arabic
     */
    public function getVehicleTypeTextAttribute(): string
    {
        return match($this->vehicle_type) {
            'bike' => 'دراجة',
            'car' => 'سيارة',
            'truck' => 'شاحنة',
            'motorcycle' => 'دراجة نارية',
            default => $this->vehicle_type,
        };
    }
}
