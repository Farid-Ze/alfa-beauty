<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentLog Model
 * 
 * Tracks all payment transactions for tax compliance (UU KUP).
 * Data must be retained for 10 years per Indonesian tax law.
 */
class PaymentLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'payment_method',
        'provider',
        'amount',
        'currency',
        'status',
        'confirmed_by',
        'confirmed_at',
        'reference_number',
        'external_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Payment method constants
     */
    const METHOD_WHATSAPP = 'whatsapp';
    const METHOD_MANUAL_TRANSFER = 'manual_transfer';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the order associated with this payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the admin user who confirmed this payment.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for confirmed payments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope for WhatsApp payments
     */
    public function scopeWhatsApp($query)
    {
        return $query->where('payment_method', self::METHOD_WHATSAPP);
    }

    /**
     * Mark payment as confirmed
     */
    public function confirm(int $confirmedBy, ?string $referenceNumber = null, ?string $notes = null): bool
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return true;
        }

        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
            'reference_number' => $referenceNumber ?? $this->reference_number,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
