<?php

namespace Tests\Unit;

use App\Models\LoyaltyTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyTierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
    }

    public function test_loyalty_tier_has_required_fields(): void
    {
        $tier = LoyaltyTier::first();

        $this->assertNotNull($tier->name);
        $this->assertNotNull($tier->slug);
    }

    public function test_loyalty_tier_has_min_spend(): void
    {
        $tier = LoyaltyTier::where('slug', 'gold')->first();

        $this->assertNotNull($tier->min_spend);
        $this->assertIsNumeric($tier->min_spend);
    }

    public function test_loyalty_tier_has_slug(): void
    {
        $tier = LoyaltyTier::first();

        $this->assertNotNull($tier->slug);
        $this->assertIsString($tier->slug);
    }

    public function test_loyalty_tier_has_users(): void
    {
        $tier = LoyaltyTier::first();

        $this->assertIsIterable($tier->users);
    }

    public function test_guest_tier_exists(): void
    {
        $tier = LoyaltyTier::where('slug', 'guest')->first();

        $this->assertNotNull($tier);
        $this->assertEquals('guest', $tier->slug);
    }

    public function test_gold_tier_exists(): void
    {
        $tier = LoyaltyTier::where('slug', 'gold')->first();

        $this->assertNotNull($tier);
        $this->assertEquals('gold', $tier->slug);
    }

    public function test_silver_tier_exists(): void
    {
        $tier = LoyaltyTier::where('slug', 'silver')->first();

        $this->assertNotNull($tier);
        $this->assertEquals('silver', $tier->slug);
    }
}
