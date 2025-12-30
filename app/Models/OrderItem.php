<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'batch_allocations' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get batch numbers used for this order item.
     * For BPOM traceability on invoices/packing slips.
     */
    public function getBatchNumbersAttribute(): array
    {
        if (empty($this->batch_allocations)) {
            return [];
        }

        return collect($this->batch_allocations)
            ->pluck('batch_number')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if any batch allocation is near expiry.
     */
    public function getHasNearExpiryBatchAttribute(): bool
    {
        if (empty($this->batch_allocations)) {
            return false;
        }

        return collect($this->batch_allocations)
            ->where('is_near_expiry', true)
            ->isNotEmpty();
    }
}
