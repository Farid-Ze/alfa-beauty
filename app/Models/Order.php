<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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
            $message .= "Subtotal: Rp " . number_format($this->subtotal, 0, ',', '.') . "\n";
            $message .= "Diskon: -Rp " . number_format($this->discount_amount, 0, ',', '.') . "\n";
        }
        
        $message .= "*TOTAL: Rp " . number_format($this->total_amount, 0, ',', '.') . "*\n\n";
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
}
