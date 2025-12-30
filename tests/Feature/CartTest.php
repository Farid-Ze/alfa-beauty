<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);

        $item = $cartService->addItem($product->id, 1);

        $this->assertInstanceOf(CartItem::class, $item);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals($product->id, $item->product_id);
    }

    public function test_authenticated_user_can_add_item_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        $cartService = app(CartService::class);

        $item = $cartService->addItem($product->id, 2);

        $this->assertEquals(2, $item->quantity);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);

        $cartService->addItem($product->id, 1);
        $cartService->addItem($product->id, 3);

        $cart = $cartService->getCart();
        $item = $cart->items->first();

        $this->assertEquals(4, $item->quantity);
        $this->assertEquals(1, $cart->items->count());
    }

    public function test_can_update_cart_item_quantity(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);

        $item = $cartService->addItem($product->id, 1);
        $cartService->updateQuantity($item->id, 5);

        $cart = $cartService->getCart();
        $this->assertEquals(5, $cart->items->first()->quantity);
    }

    public function test_setting_quantity_to_zero_removes_item(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);

        $item = $cartService->addItem($product->id, 1);
        $cartService->updateQuantity($item->id, 0);

        $cart = $cartService->getCart();
        $this->assertEquals(0, $cart->items->count());
    }

    public function test_can_remove_cart_item(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);

        $item = $cartService->addItem($product->id, 1);
        $result = $cartService->removeItem($item->id);

        $this->assertTrue($result);
        $cart = $cartService->getCart();
        $this->assertEquals(0, $cart->items->count());
    }

    public function test_can_clear_cart(): void
    {
        $products = Product::take(3)->get();
        $cartService = app(CartService::class);

        foreach ($products as $product) {
            $cartService->addItem($product->id, 1);
        }

        $this->assertEquals(3, $cartService->getItemCount());

        $cartService->clearCart();

        $this->assertEquals(0, $cartService->getItemCount());
    }

    public function test_get_correct_subtotal(): void
    {
        $products = Product::take(2)->get();
        $cartService = app(CartService::class);

        foreach ($products as $product) {
            $cartService->addItem($product->id, 1);
        }

        $expectedSubtotal = $products->sum('base_price');
        $this->assertEquals($expectedSubtotal, $cartService->getSubtotal());
    }

    public function test_validate_stock_returns_empty_when_stock_sufficient(): void
    {
        $product = Product::where('stock', '>', 5)->first();
        $cartService = app(CartService::class);

        $cartService->addItem($product->id, 2);

        $errors = $cartService->validateStock();
        $this->assertEmpty($errors);
    }

    public function test_validate_stock_returns_errors_when_stock_insufficient(): void
    {
        $product = Product::first();
        $originalStock = $product->stock;
        $product->update(['stock' => 2]);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 5); // More than available

        $errors = $cartService->validateStock();
        
        $this->assertNotEmpty($errors);
        $this->assertEquals($product->name, $errors[0]['product_name']);
        $this->assertEquals(5, $errors[0]['requested']);
        $this->assertEquals(2, $errors[0]['available']);

        // Restore stock
        $product->update(['stock' => $originalStock]);
    }

    public function test_has_valid_stock_returns_boolean(): void
    {
        $product = Product::where('stock', '>', 0)->first();
        $cartService = app(CartService::class);

        $cartService->addItem($product->id, 1);

        $this->assertTrue($cartService->hasValidStock());
    }
}
