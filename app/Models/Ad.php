<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'type',
        'position',
        'is_active',
        'start_date',
        'end_date',
        'clicks_count',
        'views_count',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'clicks_count' => 'integer',
        'views_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Scope a query to only include active ads.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include ads by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include ads by position.
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope a query to only include currently active ads (within date range).
     */
    public function scopeCurrentlyActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $now);
                    });
    }

    /**
     * Increment clicks count.
     */
    public function incrementClicks()
    {
        $this->increment('clicks_count');
    }

    /**
     * Increment views count.
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get the full image URL.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
}
