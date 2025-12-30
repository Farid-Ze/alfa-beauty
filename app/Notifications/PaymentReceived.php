<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $pointsEarned = $order->pointTransactions->where('type', 'earn')->sum('amount');
        
        return (new MailMessage)
            ->subject("Pembayaran Diterima - Pesanan #{$order->order_number}")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Pembayaran untuk pesanan #{$order->order_number} telah kami terima. Terima kasih!")
            ->line("Detail Pembayaran:")
            ->line("- Total Dibayar: Rp " . number_format($order->total_amount, 0, ',', '.'))
            ->line("- Poin yang Didapat: {$pointsEarned} poin")
            ->line("Status pesanan Anda telah diperbarui menjadi: **Sedang Diproses**")
            ->action('Lihat Pesanan', url('/orders'))
            ->line("Pesanan Anda akan segera dikirim. Kami akan mengirimkan notifikasi saat pesanan dalam perjalanan.")
            ->salutation('Salam, Tim Alfa Beauty');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
            'status' => 'paid',
            'message' => "Pembayaran untuk pesanan #{$this->order->order_number} telah diterima.",
        ];
    }
}
