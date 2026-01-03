<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Order Model
 *
 * @property int $id
 * @property string|null $request_id
 * @property string|null $idempotency_key
 * @property int|null $user_id
 * @property string $order_number
 * @property string $status
 * @property string $payment_status
 * @property float|null $total_amount
 * @property float|null $shipping_cost
 * @property float|null $subtotal
 * @property float|null $discount_amount
 * @property int $discount_percent
 * @property float|null $subtotal_before_tax
 * @property float|null $tax_rate
 * @property float|null $tax_amount
 * @property bool $is_tax_inclusive
 * @property string|null $e_faktur_number
 * @property \Carbon\Carbon|null $e_faktur_date
 * @property float|null $amount_paid
 * @property float|null $balance_due
 * @property int|null $payment_term_days
 * @property \Carbon\Carbon|null $payment_due_date
 * @property \Carbon\Carbon|null $last_payment_date
 * @property string|null $shipping_address
 * @property string|null $notes
 * @property array|null $discount_breakdown
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection|OrderItem[] $items
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'idempotency_key',
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'shipping_address',
        'shipping_method',
        'shipping_cost',
        'notes',
        'subtotal',
        'discount_amount',
        'discount_percent',
        'discount_breakdown',
        'subtotal_before_tax',
        'tax_rate',
        'tax_amount',
        'is_tax_inclusive',
        'e_faktur_number',
        'e_faktur_date',
        'amount_paid',
        'balance_due',
        'payment_term_days',
        'payment_due_date',
        'last_payment_date',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'shipping_cost' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'subtotal_before_tax' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'is_tax_inclusive' => 'boolean',
        'e_faktur_date' => 'datetime',
        'amount_paid' => 'float',
        'balance_due' => 'float',
        'payment_due_date' => 'date',
        'last_payment_date' => 'datetime',
        'discount_breakdown' => 'array',
    ];

    /**
     * Order Status Constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment Status Constants
     */
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Get all payment logs for this order.
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    /**
     * Get the latest payment log.
     */
    public function latestPaymentLog()
    {
        return $this->hasOne(PaymentLog::class)->latestOfMany();
    }

    /**
     * Get cancellation record if order was cancelled.
     */
    public function cancellation()
    {
        return $this->hasOne(OrderCancellation::class);
    }

    /**
     * Get all returns for this order.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    /**
     * Get all discounts applied to this order.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    /**
     * Check if order is pending payment
     */
    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT 
            || $this->payment_status === self::PAYMENT_PENDING;
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    /**
     * Generate WhatsApp order summary message
     */
    public function generateWhatsAppMessage(): string
    {
        $items = $this->items->map(function ($item) {
            return "• {$item->product->name} x{$item->quantity} = Rp " . number_format($item->total_price, 0, ',', '.');
        })->join("\n");

        $message = "🛒 *PESANAN BARU*\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "No. Order: *{$this->order_number}*\n\n";
        $message .= "*Detail Pesanan:*\n{$items}\n\n";
        
        if ($this->discount_amount > 0) {
            $message .= "Subtotal: Rp " . number_format((float) ($this->subtotal ?? 0), 0, ',', '.') . "\n";
            $message .= "Diskon: -Rp " . number_format((float) ($this->discount_amount ?? 0), 0, ',', '.') . "\n";
        }
        
        $message .= "*TOTAL: Rp " . number_format((float) ($this->total_amount ?? 0), 0, ',', '.') . "*\n\n";
        $message .= "📍 *Alamat Pengiriman:*\n{$this->shipping_address}\n\n";
        
        if ($this->notes) {
            $notes = explode("\n", $this->notes);
            $customerName = str_replace('Name: ', '', $notes[0] ?? '');
            $customerPhone = str_replace('Phone: ', '', $notes[1] ?? '');
            $message .= "👤 Nama: {$customerName}\n";
            $message .= "📱 Telepon: {$customerPhone}\n\n";
        }
        
        $message .= "Mohon konfirmasi pesanan ini.\n";
        $message .= "Terima kasih! 🙏";

        return $message;
    }

    /**
     * Calculate tax amount based on subtotal and rate.
     */
    public function calculateTax(): float
    {
        if ($this->is_tax_inclusive) {
            // Tax is already included in prices
            return $this->subtotal_before_tax * ($this->tax_rate / (100 + $this->tax_rate));
        }

        return $this->subtotal_before_tax * ($this->tax_rate / 100);
    }

    /**
     * Recalculate totals including tax.
     * @phpstan-ignore-next-line
     */
    public function recalculateTotals(): void
    {
        /** @phpstan-ignore-next-line */
        $subtotalBeforeTax = (float) $this->items->sum('subtotal_before_tax');
        $this->subtotal_before_tax = $subtotalBeforeTax;
        $this->tax_amount = $this->calculateTax();
        $this->subtotal = $subtotalBeforeTax + ($this->is_tax_inclusive ? 0 : (float) $this->tax_amount);
        $this->total_amount = (float) $this->subtotal - (float) ($this->discount_amount ?? 0) + (float) ($this->shipping_cost ?? 0);
        $this->balance_due = (float) $this->total_amount - (float) ($this->amount_paid ?? 0);
    }

    /**
     * Record a payment.
     * @phpstan-ignore-next-line
     */
    public function recordPayment(float $amount, string $method, ?string $reference = null): PaymentLog
    {
        return DB::transaction(function () use ($amount, $method, $reference) {
            /** @var self $locked */
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $amountNormalized = (float) $amount;
            $idempotencyKey = $reference
                ? "payment:order:{$locked->id}:ref:" . trim($reference)
                : "payment:order:{$locked->id}:method:{$method}:amount:" . number_format($amountNormalized, 2, '.', '');

            $attributes = ['order_id' => $locked->id, 'idempotency_key' => $idempotencyKey];
            $values = [
                'payment_method' => $method,
                'amount' => $amountNormalized,
                'currency' => 'IDR',
                'status' => PaymentLog::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ];

            if ($reference) {
                $values['reference_number'] = trim($reference);
            }

            $paymentLog = $locked->paymentLogs()->firstOrCreate($attributes, $values);

            if ($paymentLog->status !== PaymentLog::STATUS_CONFIRMED) {
                $paymentLog->forceFill([
                    'status' => PaymentLog::STATUS_CONFIRMED,
                    'confirmed_at' => $paymentLog->confirmed_at ?? now(),
                ])->save();
            }

            $amountPaid = (float) $locked->paymentLogs()
                ->where('status', PaymentLog::STATUS_CONFIRMED)
                ->sum('amount');

            $lastPaymentAt = $locked->paymentLogs()
                ->where('status', PaymentLog::STATUS_CONFIRMED)
                ->max('confirmed_at');

            $locked->amount_paid = $amountPaid;
            $locked->balance_due = (float) ($locked->total_amount ?? 0) - $amountPaid;
            $locked->last_payment_date = $lastPaymentAt ? \Carbon\Carbon::parse($lastPaymentAt) : $locked->last_payment_date;

            if ($locked->balance_due <= 0 && $amountPaid > 0) {
                $locked->payment_status = self::PAYMENT_PAID;
            } elseif ($amountPaid > 0) {
                $locked->payment_status = 'partially_paid';
            }

            $locked->save();

            try {
                /** @var \App\Services\AuditEventService $audit */
                $audit = app(\App\Services\AuditEventService::class);
                $audit->record(
                    'order.payment_recorded',
                    self::class,
                    $locked->id,
                    [
                        'payment_log_id' => $paymentLog->id,
                        'payment_method' => $method,
                        'amount' => $amountNormalized,
                        'reference_number' => $reference,
                        'status' => $paymentLog->status,
                    ],
                    "order:payment_recorded:{$locked->id}:{$paymentLog->id}",
                    $locked->request_id,
                    null,
                );
            } catch (\Throwable $e) {
                // Non-fatal governance
            }

            return $paymentLog;
        });
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_PAYMENT,
        ]) && !$this->cancellation;
    }

    /**
     * Cancel order with reason.
     */
    public function cancel(string $reasonCode, ?string $notes = null, ?int $cancelledBy = null): OrderCancellation
    {
        // Idempotency: if already cancelled, return existing cancellation record.
        if ($this->cancellation) {
            return $this->cancellation;
        }

        if (!$this->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled');
        }

        /** @var \App\Services\OrderService $orderService */
        $orderService = app(\App\Services\OrderService::class);
        $orderService->cancelOrder($this, $reasonCode, $notes, $cancelledBy);

        return $this->fresh()->cancellation()->firstOrFail();
    }

    /**
     * Check if order is overdue for payment.
     */
    public function isPaymentOverdue(): bool
    {
        return $this->payment_due_date 
            && $this->payment_due_date < now() 
            && $this->balance_due > 0;
    }

    /**
     * Get total weight for shipping calculation.
     */
    public function getTotalWeightAttribute(): int
    {
        return $this->items->sum(function ($item) {
            return ($item->product->volumetric_weight ?? 0) * $item->quantity;
        });
    }
}
