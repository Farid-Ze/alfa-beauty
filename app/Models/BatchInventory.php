<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * BatchInventory Model
 * 
 * Tracks product batches for BPOM traceability.
 * Data must be retained for 6 years after last production (BPOM PIF requirement).
 */
class BatchInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'batch_inventories';

    protected $fillable = [
        'product_id',
        'batch_number',
        'lot_number',
        'quantity_received',
        'quantity_available',
        'quantity_sold',
        'quantity_damaged',
        'manufactured_at',
        'expires_at',
        'received_at',
        'cost_price',
        'near_expiry_discount_percent',
        'is_active',
        'is_near_expiry',
        'is_expired',
        'warehouse_id',
        'supplier_name',
        'country_of_origin',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'manufactured_at' => 'date',
        'expires_at' => 'date',
        'received_at' => 'date',
        'cost_price' => 'decimal:2',
        'near_expiry_discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_near_expiry' => 'boolean',
        'is_expired' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Near-expiry threshold in days (products expiring within this many days)
     */
    const NEAR_EXPIRY_THRESHOLD_DAYS = 90;

    /**
     * Get the product this batch belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for active batches only
     */
    public function scopeActive($query)
    {
        return $query->whereRaw('is_active = true')
                     ->whereRaw('is_expired = false')
                     ->where('quantity_available', '>', 0);
    }

    /**
     * Scope for near-expiry batches
     */
    public function scopeNearExpiry($query)
    {
        return $query->whereRaw('is_near_expiry = true')
                     ->whereRaw('is_expired = false');
    }

    /**
     * Scope for expired batches
     */
    public function scopeExpired($query)
    {
        return $query->whereRaw('is_expired = true');
    }

    /**
     * Scope ordered by FIFO (First In First Out / earliest expiry first)
     */
    public function scopeFifo($query)
    {
        return $query->orderBy('expires_at', 'asc');
    }

    /**
     * Get batches for a specific product with available stock
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Calculate days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return (int) Carbon::now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if batch is near expiry (within threshold)
     */
    public function getIsNearExpiryComputedAttribute(): bool
    {
        return $this->days_until_expiry >= 0 
            && $this->days_until_expiry <= self::NEAR_EXPIRY_THRESHOLD_DAYS;
    }

    /**
     * Check if batch is expired
     */
    public function getIsExpiredComputedAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Update expiry status flags
     */
    public function updateExpiryStatus(): bool
    {
        return $this->update([
            'is_near_expiry' => $this->is_near_expiry_computed,
            'is_expired' => $this->is_expired_computed,
            'is_active' => !$this->is_expired_computed && $this->quantity_available > 0,
        ]);
    }

    /**
     * Reduce stock from this batch (FIFO)
     */
    public function reduceStock(int $quantity): bool
    {
        if ($quantity > $this->quantity_available) {
            return false;
        }

        return $this->update([
            'quantity_available' => $this->quantity_available - $quantity,
            'quantity_sold' => $this->quantity_sold + $quantity,
            'is_active' => ($this->quantity_available - $quantity) > 0,
        ]);
    }

    /**
     * Get effective price considering near-expiry discount
     */
    public function getEffectiveDiscount(): float
    {
        if ($this->is_near_expiry && $this->near_expiry_discount_percent > 0) {
            return (float) $this->near_expiry_discount_percent;
        }
        return 0;
    }

    /**
     * Static: Get available stock for a product using FIFO
     */
    public static function getAvailableStockForProduct(int $productId): int
    {
        return static::forProduct($productId)
            ->active()
            ->sum('quantity_available');
    }

    /**
     * Static: Get next batch to sell from (FIFO - earliest expiry)
     */
    public static function getNextBatchForProduct(int $productId): ?BatchInventory
    {
        return static::forProduct($productId)
            ->active()
            ->fifo()
            ->first();
    }
}
