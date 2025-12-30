<?php

namespace App\Notifications;

use App\Models\LoyaltyTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TierUpgraded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public LoyaltyTier $previousTier,
        public LoyaltyTier $newTier
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
        $newTier = $this->newTier;
        
        return (new MailMessage)
            ->subject("🎉 Selamat! Anda Naik ke Tier {$newTier->name}")
            ->greeting("Selamat {$notifiable->name}!")
            ->line("Anda telah naik dari **{$this->previousTier->name}** ke **{$newTier->name}**!")
            ->line("Keuntungan tier baru Anda:")
            ->line("- Diskon: {$newTier->discount_percent}%")
            ->line("- Pengganda Poin: {$newTier->point_multiplier}x")
            ->line($newTier->free_shipping ? "- ✅ Gratis Ongkir di Semua Pesanan" : "- Gratis ongkir untuk pesanan minimal Rp 2.500.000")
            ->action('Mulai Belanja', url('/products'))
            ->line("Terima kasih telah menjadi pelanggan setia Alfa Beauty!")
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
            'previous_tier' => $this->previousTier->name,
            'new_tier' => $this->newTier->name,
            'discount_percent' => $this->newTier->discount_percent,
            'point_multiplier' => $this->newTier->point_multiplier,
            'message' => "Selamat! Anda naik ke tier {$this->newTier->name}.",
        ];
    }
}
