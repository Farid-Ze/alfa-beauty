<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
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
        
        return (new MailMessage)
            ->subject("Konfirmasi Pesanan #{$order->order_number}")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Terima kasih atas pesanan Anda. Pesanan #{$order->order_number} telah kami terima.")
            ->line("Detail Pesanan:")
            ->line("- Total: Rp " . number_format($order->total_amount, 0, ',', '.'))
            ->line("- Status: " . ucfirst($order->status))
            ->line("- Alamat: {$order->shipping_address}")
            ->action('Lihat Pesanan', url('/orders'))
            ->line("Tim kami akan segera memproses pesanan Anda.")
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
            'status' => $this->order->status,
            'message' => "Pesanan #{$this->order->order_number} telah dikonfirmasi.",
        ];
    }
}
