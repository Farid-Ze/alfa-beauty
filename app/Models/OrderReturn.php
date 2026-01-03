<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * OrderReturn Model
 * 
 * Handles product returns (partial or full) from orders.
 * Supports refund, exchange, or store credit return types.
 *
 * @property int $id
 * @property string $return_number
 * @property int $order_id
 * @property int $user_id
 * @property int|null $processed_by
 * @property string $status
 * @property string $return_type
 * @property string|null $reason_code
 * @property string|null $reason_notes
 * @property string|null $customer_notes
 * @property float|null $return_value
 * @property float|null $restocking_fee
 * @property float|null $refund_amount
 * @property string|null $refund_status
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $received_at
 * @property \Carbon\Carbon|null $completed_at
 * @property-read Order $order
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|ReturnItem[] $items
 */
class OrderReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'processed_by',
        'status',
        'return_type',
        'reason_code',
        'reason_notes',
        'customer_notes',
        'return_value',
        'restocking_fee',
        'refund_amount',
        'refund_status',
        'approved_at',
        'received_at',
        'completed_at',
        'inventory_restocked_at',
        'loyalty_reversed_at',
    ];

    protected $casts = [
        'return_value' => 'float',
        'restocking_fee' => 'float',
        'refund_amount' => 'float',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
        'inventory_restocked_at' => 'datetime',
        'loyalty_reversed_at' => 'datetime',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Constants
     * ───────────────────────────────────────────────────────────── */

    public const STATUSES = [
        'requested' => 'Diminta',
        'approved' => 'Disetujui',
        'received' => 'Diterima',
        'inspected' => 'Diperiksa',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    public const RETURN_TYPES = [
        'refund' => 'Pengembalian dana',
        'exchange' => 'Tukar barang',
        'credit' => 'Kredit toko',
    ];

    public const REASON_CODES = [
        'defective' => 'Produk cacat',
        'wrong_item' => 'Barang salah',
        'expired' => 'Mendekati/sudah expired',
        'damaged_shipping' => 'Rusak dalam pengiriman',
        'quality_issue' => 'Masalah kualitas',
        'not_as_described' => 'Tidak sesuai deskripsi',
        'customer_change_mind' => 'Pelanggan berubah pikiran',
        'other' => 'Lainnya',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Boot
     * ───────────────────────────────────────────────────────────── */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = self::generateReturnNumber();
            }
        });
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    public static function generateReturnNumber(): string
    {
        $prefix = 'RET';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}-{$date}-{$random}";
    }

    public function calculateRefundAmount(): float
    {
        $returnValue = (float) $this->items->sum('line_total');
        $this->return_value = $returnValue;
        $this->refund_amount = $returnValue - (float) ($this->restocking_fee ?? 0);
        
        return (float) $this->refund_amount;
    }

    public function approve(?int $processedBy = null): void
    {
        /** @var \App\Contracts\ReturnServiceInterface $service */
        $service = app(\App\Contracts\ReturnServiceInterface::class);
        $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();
        $service->approveReturn($this, $processedBy, $requestId);
        $this->refresh();
    }

    public function markReceived(): void
    {
        /** @var \App\Contracts\ReturnServiceInterface $service */
        $service = app(\App\Contracts\ReturnServiceInterface::class);
        $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();
        $service->markReturnReceived($this, $this->processed_by, $requestId);
        $this->refresh();
    }

    public function complete(): void
    {
        /** @var \App\Contracts\ReturnServiceInterface $service */
        $service = app(\App\Contracts\ReturnServiceInterface::class);
        $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();
        $service->completeReturn($this, $this->processed_by, $requestId);
        $this->refresh();
    }

    public function reject(string $reason): void
    {
        /** @var \App\Contracts\ReturnServiceInterface $service */
        $service = app(\App\Contracts\ReturnServiceInterface::class);
        $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();
        $service->rejectReturn($this, $reason, $this->processed_by, $requestId);
        $this->refresh();
    }

    /* ─────────────────────────────────────────────────────────────
     * Accessors
     * ───────────────────────────────────────────────────────────── */

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASON_CODES[$this->reason_code] ?? $this->reason_code;
    }

    public function getReturnTypeLabelAttribute(): string
    {
        return self::RETURN_TYPES[$this->return_type] ?? $this->return_type;
    }

    /* ─────────────────────────────────────────────────────────────
     * Scopes
     * ───────────────────────────────────────────────────────────── */

    public function scopePending($query)
    {
        return $query->whereIn('status', ['requested', 'approved']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
