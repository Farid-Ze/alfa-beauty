<?php

namespace Tests\Feature;

use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoyaltySystemTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected LoyaltyTier $silverTier;
    protected LoyaltyTier $goldTier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderService = app(OrderService::class);

        // Seed Tiers
        $this->silverTier = LoyaltyTier::create([
            'name' => 'Silver',
            'slug' => 'silver',
            'min_spend' => 0,
            'point_multiplier' => 1.0
        ]);

        $this->goldTier = LoyaltyTier::create([
            'name' => 'Gold',
            'slug' => 'gold',
            'min_spend' => 50000000,
            'point_multiplier' => 1.5
        ]);
    }

    public function test_user_earns_points_on_paid_order()
    {
        $user = User::factory()->create(['loyalty_tier_id' => $this->silverTier->id]);
        
        // Create Order (Total: 1.000.000)
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-001',
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 1000000,
            'payment_method' => 'manual',
            'shipping_cost' => 0
        ]);

        // Act: Complete Order
        $this->orderService->completeOrder($order);

        // Assert: 100 Points (1.000.000 / 10.000)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'points' => 100,
            'total_spend' => 1000000
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => 100,
            'type' => 'earn'
        ]);
    }

    public function test_gold_user_earns_multiplier_points()
    {
        $user = User::factory()->create([
            'loyalty_tier_id' => $this->goldTier->id, // Gold (1.5x)
            'points' => 0
        ]);
        
        // Create Order (Total: 1.000.000)
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-002',
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 1000000,
            'payment_method' => 'manual',
            'shipping_cost' => 0
        ]);

        // Act
        $this->orderService->completeOrder($order);

        // Assert: 150 Points (100 * 1.5)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'points' => 150
        ]);
    }

    public function test_user_upgrades_to_gold_tier()
    {
        $user = User::factory()->create([
            'loyalty_tier_id' => $this->silverTier->id,
            'total_spend' => 45000000 // Close to 50M
        ]);
        
        // Create Order (Total: 6.000.000) -> Total Spend becomes 51M
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-UPGRADE',
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 6000000,
            'payment_method' => 'manual',
            'shipping_cost' => 0
        ]);

        // Act
        $this->orderService->completeOrder($order);

        // Assert: Tier upgraded to Gold
        $this->assertEquals($this->goldTier->id, $user->refresh()->loyalty_tier_id);
    }
}
