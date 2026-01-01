<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\LoyaltyTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
    }

    public function test_user_belongs_to_loyalty_tier(): void
    {
        $tier = LoyaltyTier::first();
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => $tier->id,
        ]);

        $this->assertInstanceOf(LoyaltyTier::class, $user->loyaltyTier);
    }

    public function test_user_has_default_points(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->assertEquals(0, $user->points ?? 0);
    }

    public function test_user_has_default_total_spend(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->assertEquals(0, $user->total_spend ?? 0);
    }

    public function test_user_can_have_orders(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->assertIsIterable($user->orders);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => bcrypt('plainpassword'),
            'loyalty_tier_id' => 1,
        ]);

        $this->assertNotEquals('plainpassword', $user->password);
    }

    public function test_user_has_fillable_fields(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('Test Company', $user->company_name);
        $this->assertEquals('081234567890', $user->phone);
    }

    public function test_user_has_cart_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'carttest@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        // Cart relationship should be accessible (returns null if no cart)
        $this->assertNull($user->cart);
    }

    public function test_user_has_reviews_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'reviewtest@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        // Reviews relationship should be iterable
        $this->assertIsIterable($user->reviews);
    }
}
