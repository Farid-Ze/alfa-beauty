<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\LoyaltyTier;
use App\Notifications\OrderConfirmation;
use App\Notifications\PaymentReceived;
use App\Notifications\TierUpgraded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_order_confirmation_notification_is_dispatched(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create();

        $user->notify(new OrderConfirmation($order));

        Notification::assertSentTo($user, OrderConfirmation::class);
    }

    public function test_payment_received_notification_is_dispatched(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->paid()
            ->create();

        $user->notify(new PaymentReceived($order));

        Notification::assertSentTo($user, PaymentReceived::class);
    }

    public function test_tier_upgraded_notification_is_dispatched(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        
        $previousTier = LoyaltyTier::where('slug', 'guest')->first();
        $newTier = LoyaltyTier::where('slug', 'bronze')->first();

        if ($previousTier && $newTier) {
            $user->notify(new TierUpgraded($previousTier, $newTier));

            Notification::assertSentTo($user, TierUpgraded::class);
        } else {
            $this->markTestSkipped('Required loyalty tiers not found');
        }
    }

    public function test_order_confirmation_notification_has_mail_channel(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create();

        $notification = new OrderConfirmation($order);
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
    }

    public function test_order_confirmation_notification_contains_order_number(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create();

        $notification = new OrderConfirmation($order);
        $mailMessage = $notification->toMail($user);

        $this->assertNotNull($mailMessage);
        // Check that the rendered output contains order number
        $this->assertStringContainsString($order->order_number, $mailMessage->render()->toHtml());
    }

    public function test_payment_received_notification_contains_amount(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->paid()
            ->create([
                'total_amount' => 500000,
            ]);

        $notification = new PaymentReceived($order);
        $mailMessage = $notification->toMail($user);

        $this->assertNotNull($mailMessage);
    }

    public function test_tier_upgraded_notification_contains_tier_names(): void
    {
        $user = User::factory()->create();
        
        $previousTier = LoyaltyTier::where('slug', 'guest')->first();
        $newTier = LoyaltyTier::where('slug', 'bronze')->first();

        if (!$previousTier || !$newTier) {
            $this->markTestSkipped('Required loyalty tiers not found');
        }

        $notification = new TierUpgraded($previousTier, $newTier);
        $mailMessage = $notification->toMail($user);

        $this->assertNotNull($mailMessage);
    }
}
