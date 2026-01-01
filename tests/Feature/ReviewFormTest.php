<?php

namespace Tests\Feature;

use App\Livewire\ReviewForm;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewFormTest extends TestCase
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

    public function test_review_form_renders(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->assertStatus(200);
    }

    public function test_review_form_starts_hidden(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->assertSet('showForm', false);
    }

    public function test_review_form_can_toggle(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->assertSet('showForm', false)
            ->call('toggleForm')
            ->assertSet('showForm', true)
            ->call('toggleForm')
            ->assertSet('showForm', false);
    }

    public function test_review_form_default_rating_is_five(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->assertSet('rating', 5);
    }

    public function test_review_form_can_set_rating(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->call('setRating', 3)
            ->assertSet('rating', 3);
    }

    public function test_review_form_requires_auth_to_submit(): void
    {
        $product = Product::first();

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('content', 'This is a great product that I love.')
            ->call('submit')
            ->assertRedirect(route('login'));
    }

    public function test_review_form_validates_content_required(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('content', '')
            ->call('submit')
            ->assertHasErrors(['content' => 'required']);
    }

    public function test_review_form_validates_content_min_length(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('content', 'Too short')
            ->call('submit')
            ->assertHasErrors(['content']);
    }

    public function test_review_form_validates_rating_range(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('rating', 6)
            ->set('content', 'This is a great product that I really love.')
            ->call('submit')
            ->assertHasErrors(['rating']);
    }

    public function test_review_form_submits_successfully(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('rating', 4)
            ->set('title', 'Great Product')
            ->set('content', 'This is an excellent product that I highly recommend.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertDispatched('review-submitted');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Great Product',
            'is_approved' => false,
        ]);
    }

    public function test_review_is_marked_verified_for_buyer(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        // Create a paid order with this product
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-VERIFY-001',
            'total_amount' => 350000,
            'shipping_cost' => 25000,
            'status' => 'completed',
            'payment_status' => 'paid',
            'shipping_address' => 'Test Address',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 350000,
            'total_price' => 350000,
        ]);

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('content', 'I bought this and it is fantastic product!')
            ->call('submit');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'is_verified' => true,
        ]);
    }

    public function test_review_not_verified_for_non_buyer(): void
    {
        $user = User::create([
            'name' => 'Non Buyer',
            'email' => 'nonbuyer' . uniqid() . '@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::skip(2)->first() ?? Product::first();

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->set('content', 'I want to review this product anyway!')
            ->call('submit');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'is_verified' => false,
        ]);
    }

    public function test_user_cannot_review_same_product_twice(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        // Create existing review
        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'Already reviewed this product before.',
            'is_approved' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(ReviewForm::class, ['product' => $product])
            ->assertSet('alreadyReviewed', true);
    }
}
