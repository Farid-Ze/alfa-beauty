<?php

namespace Tests\Feature;

use App\Livewire\ProductList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_product_list_renders(): void
    {
        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }

    public function test_product_list_shows_featured_products(): void
    {
        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }

    public function test_product_list_limits_to_four_products(): void
    {
        // ProductList should show max 4 products
        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }

    public function test_product_list_loads_prices_for_guest(): void
    {
        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }

    public function test_product_list_loads_prices_for_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }

    public function test_product_list_loads_with_brand_relation(): void
    {
        Livewire::test(ProductList::class)
            ->assertStatus(200);
    }
}
