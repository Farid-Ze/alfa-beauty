<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
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

    protected function createCartWithProduct(User $user): Cart
    {
        $product = Product::first();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        return $cart;
    }

    public function test_checkout_page_redirects_when_cart_empty(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->actingAs($user);

        // Access checkout via HTTP - should redirect
        $response = $this->get('/checkout');
        $response->assertRedirect('/');
    }

    public function test_checkout_page_renders_with_cart(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
    }

    public function test_checkout_prefills_user_data(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'phone' => '08123456789',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->assertSet('name', 'John Doe')
            ->assertSet('phone', '08123456789');
    }

    public function test_checkout_validates_required_fields(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->set('name', '')
            ->set('phone', '')
            ->set('address', '')
            ->call('placeOrder')
            ->assertHasErrors(['name', 'phone', 'address']);
    }

    public function test_checkout_validates_phone_length(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->set('name', 'John Doe')
            ->set('phone', '123')
            ->set('address', 'Jl. Test No. 123, Jakarta')
            ->call('placeOrder')
            ->assertHasErrors(['phone']);
    }

    public function test_checkout_validates_address_length(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->set('name', 'John Doe')
            ->set('phone', '08123456789')
            ->set('address', 'Short')
            ->call('placeOrder')
            ->assertHasErrors(['address']);
    }

    public function test_checkout_can_set_notes(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->set('notes', 'Please handle with care')
            ->assertSet('notes', 'Please handle with care');
    }

    public function test_checkout_initializes_empty_errors(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createCartWithProduct($user);
        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->assertSet('stockErrors', [])
            ->assertSet('moqViolations', []);
    }
}
