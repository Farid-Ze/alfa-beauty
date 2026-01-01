<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\User;
use App\Models\LoyaltyTier;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        
        $this->orderService = app(OrderService::class);
    }

    public function test_admin_can_confirm_whatsapp_payment(): void
    {
        $customer = User::factory()->create([
            'points' => 0,
            'total_spend' => 0,
        ]);
        $admin = User::factory()->create();

        $order = Order::factory()
            ->for($customer)
            ->pending()
            ->create([
                'payment_method' => 'whatsapp',
            ]);

        PaymentLog::factory()
            ->for($order)
            ->whatsapp()
            ->pending()
            ->create([
                'amount' => $order->total_amount,
            ]);

        $result = $this->orderService->confirmWhatsAppPayment(
            $order,
            $admin->id,
            'REF-123456'
        );

        $this->assertTrue($result);

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->status);
    }

    public function test_payment_confirmation_awards_points(): void
    {
        $customer = User::factory()->create([
            'points' => 0,
            'total_spend' => 0,
            'loyalty_tier_id' => LoyaltyTier::where('slug', 'guest')->first()?->id,
        ]);
        $admin = User::factory()->create();

        $order = Order::factory()
            ->for($customer)
            ->pending()
            ->create([
                'payment_method' => 'whatsapp',
                'total_amount' => 100000, // Should earn 10 points
            ]);

        PaymentLog::factory()
            ->for($order)
            ->whatsapp()
            ->pending()
            ->create([
                'amount' => 100000,
            ]);

        $this->orderService->confirmWhatsAppPayment($order, $admin->id);

        $customer->refresh();
        $this->assertGreaterThan(0, $customer->points);
    }

    public function test_payment_confirmation_updates_total_spend(): void
    {
        $customer = User::factory()->create([
            'points' => 0,
            'total_spend' => 0,
        ]);
        $admin = User::factory()->create();

        $orderAmount = 500000;
        $order = Order::factory()
            ->for($customer)
            ->pending()
            ->create([
                'payment_method' => 'whatsapp',
                'total_amount' => $orderAmount,
            ]);

        PaymentLog::factory()
            ->for($order)
            ->whatsapp()
            ->pending()
            ->create([
                'amount' => $orderAmount,
            ]);

        $this->orderService->confirmWhatsAppPayment($order, $admin->id);

        $customer->refresh();
        $this->assertEquals($orderAmount, $customer->total_spend);
    }

    public function test_payment_log_is_marked_confirmed(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();

        $order = Order::factory()
            ->for($customer)
            ->pending()
            ->create([
                'payment_method' => 'whatsapp',
            ]);

        $paymentLog = PaymentLog::factory()
            ->for($order)
            ->whatsapp()
            ->pending()
            ->create([
                'amount' => $order->total_amount,
            ]);

        $this->orderService->confirmWhatsAppPayment(
            $order,
            $admin->id,
            'REF-789'
        );

        $paymentLog->refresh();
        $this->assertEquals(PaymentLog::STATUS_CONFIRMED, $paymentLog->status);
        $this->assertEquals($admin->id, $paymentLog->confirmed_by);
        $this->assertNotNull($paymentLog->confirmed_at);
    }
}
