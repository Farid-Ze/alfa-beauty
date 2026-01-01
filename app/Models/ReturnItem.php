<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReturnItem Model
 * 
 * Individual line items in an order return.
 * Tracks quantity requested vs received/approved for partial returns.
 *
 * @property int $id
 * @property int $order_return_id
 * @property int $order_item_id
 * @property int $product_id
 * @property int|null $batch_inventory_id
 * @property int $quantity_requested
 * @property int|null $quantity_received
 * @property int|null $quantity_approved
 * @property float|null $unit_price
 * @property float|null $line_total
 * @property string|null $condition
 * @property string|null $inspection_notes
 * @property bool $restock
 * @property-read OrderReturn $orderReturn
 * @property-read OrderItem $orderItem
 * @property-read Product $product
 */
class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_return_id',
        'order_item_id',
        'product_id',
        'batch_inventory_id',
        'quantity_requested',
        'quantity_received',
        'quantity_approved',
        'unit_price',
        'line_total',
        'condition',
        'inspection_notes',
        'restock',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_received' => 'integer',
        'quantity_approved' => 'integer',
        'unit_price' => 'float',
        'line_total' => 'float',
        'restock' => 'boolean',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Constants
     * ───────────────────────────────────────────────────────────── */

    public const CONDITIONS = [
        'unopened' => 'Belum dibuka',
        'opened' => 'Sudah dibuka',
        'damaged' => 'Rusak',
        'expired' => 'Kadaluarsa',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batchInventory(): BelongsTo
    {
        return $this->belongsTo(BatchInventory::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Accessors
     * ───────────────────────────────────────────────────────────── */

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition ?? '-';
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    public function calculateLineTotal(): float
    {
        $lineTotal = (int) ($this->quantity_approved ?? 0) * (float) ($this->unit_price ?? 0);
        $this->line_total = $lineTotal;
        return $lineTotal;
    }

    public function canRestock(): bool
    {
        return in_array($this->condition, ['unopened']) && $this->quantity_approved > 0;
    }

    public function markReceived(int $quantity, string $condition): void
    {
        $this->update([
            'quantity_received' => $quantity,
            'condition' => $condition,
        ]);
    }

    public function approve(int $quantity, bool $restock = false): void
    {
        $approvedQty = min($quantity, $this->quantity_received);
        
        $this->update([
            'quantity_approved' => $approvedQty,
            'restock' => $restock && $this->canRestock(),
            'line_total' => $approvedQty * $this->unit_price,
        ]);
    }
}
