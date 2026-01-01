<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OrderItem Model
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_price
 * @property float|null $unit_price_before_tax
 * @property float|null $subtotal_before_tax
 * @property float|null $tax_rate
 * @property float|null $tax_amount
 * @property array|null $batch_allocations
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Product $product
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'float',
        'total_price' => 'float',
        'unit_price_before_tax' => 'float',
        'subtotal_before_tax' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
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
