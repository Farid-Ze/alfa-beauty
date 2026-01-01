<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    protected function createUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);
    }

    public function test_review_belongs_to_user(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'Great product!',
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    public function test_review_belongs_to_product(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'content' => 'Good product!',
        ]);

        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertEquals($product->id, $review->product->id);
    }

    public function test_review_has_rating(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 3,
            'content' => 'Average product.',
        ]);

        $this->assertEquals(3, $review->rating);
        $this->assertIsInt($review->rating);
    }

    public function test_review_can_have_title(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Best Product Ever',
            'content' => 'I love this product!',
        ]);

        $this->assertEquals('Best Product Ever', $review->title);
    }

    public function test_review_is_verified_flag(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'Verified purchase!',
            'is_verified' => true,
        ]);

        $this->assertTrue($review->is_verified);
    }

    public function test_review_is_approved_flag(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'Approved review!',
            'is_approved' => true,
        ]);

        $this->assertTrue($review->is_approved);
    }

    public function test_review_approved_scope(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $product = Product::first();

        // Create approved review (user 1)
        Review::create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'This is an approved review.',
            'is_approved' => true,
        ]);

        // Create unapproved review (different user to respect unique constraint)
        Review::create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'rating' => 1,
            'content' => 'This is pending approval.',
            'is_approved' => false,
        ]);

        $approved = Review::approved()->count();
        $this->assertEquals(1, $approved);
    }

    public function test_product_has_reviews_relationship(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'content' => 'Test review.',
            'is_approved' => true,
        ]);

        $this->assertCount(1, $product->reviews);
        $this->assertCount(1, $product->approvedReviews);
    }

    public function test_user_has_reviews_relationship(): void
    {
        $user = $this->createUser();
        $product = Product::first();

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'content' => 'My review.',
        ]);

        $user->refresh();
        $this->assertCount(1, $user->reviews);
    }
}
