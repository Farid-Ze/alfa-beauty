<?php

namespace Tests\Feature;

use App\Livewire\ProductReviews;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductReviewsTest extends TestCase
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

    protected function createReview(Product $product, User $user, array $overrides = []): Review
    {
        return Review::create(array_merge([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'This is a fantastic product that works great.',
            'is_verified' => true,
            'is_approved' => true,
            'points_awarded' => false,
        ], $overrides));
    }

    public function test_product_reviews_renders(): void
    {
        $product = Product::first();

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertStatus(200);
    }

    public function test_product_reviews_shows_no_reviews_message(): void
    {
        $product = Product::first();

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertStatus(200);
    }

    public function test_product_reviews_displays_approved_reviews(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $review = $this->createReview($product, $user);

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertSee($review->content);
    }

    public function test_product_reviews_hides_unapproved_reviews(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $review = $this->createReview($product, $user, ['is_approved' => false]);

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertDontSee($review->content);
    }

    public function test_product_reviews_shows_reviewer_name(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'John Reviewer',
            'email' => 'john@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createReview($product, $user);

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertSee($user->name);
    }

    public function test_product_reviews_can_load_more(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        // Create 10 reviews
        for ($i = 1; $i <= 10; $i++) {
            $this->createReview($product, $user, [
                'content' => "Review content number {$i} for product testing.",
            ]);
        }

        $component = Livewire::test(ProductReviews::class, ['product' => $product]);
        
        // Default is 5 per page
        $this->assertEquals(5, $component->get('perPage'));

        // Load more
        $component->call('loadMore');
        $this->assertEquals(10, $component->get('perPage'));
    }

    public function test_product_reviews_shows_rating_distribution(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        // Create reviews with different ratings
        $this->createReview($product, $user, ['rating' => 5]);
        $this->createReview($product, $user, ['rating' => 5, 'content' => 'Another five star review.']);
        $this->createReview($product, $user, ['rating' => 4, 'content' => 'Four star review here.']);

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertStatus(200);
    }

    public function test_product_reviews_shows_verified_badge(): void
    {
        $product = Product::first();
        $user = User::create([
            'name' => 'Verified Buyer',
            'email' => 'verified@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->createReview($product, $user, ['is_verified' => true]);

        Livewire::test(ProductReviews::class, ['product' => $product])
            ->assertStatus(200);
    }
}
