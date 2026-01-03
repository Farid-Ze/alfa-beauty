<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderCancellation Model
 * 
 * Tracks order cancellations and refund processing.
 * Separate from returns - this is for voiding entire orders.
 */
class OrderCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cancelled_by',
        'reason_code',
        'reason_notes',
        'refund_amount',
        'refund_status',
        'refund_method',
        'refund_completed_at',
        'inventory_released_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_completed_at' => 'datetime',
        'inventory_released_at' => 'datetime',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Constants
     * ───────────────────────────────────────────────────────────── */

    public const REASON_CODES = [
        'cancelled_by_customer' => 'Dibatalkan oleh pelanggan',
        'out_of_stock' => 'Stok habis',
        'payment_failed' => 'Pembayaran gagal',
        'payment_timeout' => 'Timeout pembayaran',
        'duplicate_order' => 'Order duplikat',
        'pricing_error' => 'Kesalahan harga',
        'fraud_suspected' => 'Dugaan penipuan',
        'customer_request' => 'Permintaan pelanggan',
        'other' => 'Lainnya',
    ];

    public const REFUND_STATUSES = [
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'declined' => 'Ditolak',
    ];

    public const REFUND_METHODS = [
        'original_payment' => 'Metode pembayaran asal',
        'bank_transfer' => 'Transfer bank',
        'credit' => 'Kredit toko',
        'cash' => 'Tunai',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /* ─────────────────────────────────────────────────────────────
     * Accessors
     * ───────────────────────────────────────────────────────────── */

    public function getReasonLabelAttribute(): string
    {
        return self::REASON_CODES[$this->reason_code] ?? $this->reason_code;
    }

    public function getRefundStatusLabelAttribute(): string
    {
        return self::REFUND_STATUSES[$this->refund_status] ?? $this->refund_status;
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    public function markRefundCompleted(string $method): void
    {
        $this->update([
            'refund_status' => 'completed',
            'refund_method' => $method,
            'refund_completed_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->refund_status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->refund_status === 'completed';
    }
}
