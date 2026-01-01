<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Review Model (Testimonials)
 * 
 * Product reviews with star ratings and verification.
 * Users earn +50 points for approved reviews.
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int|null $order_id
 * @property int $rating 1-5 stars
 * @property string|null $title
 * @property string|null $content
 * @property bool $is_verified Verified buyer
 * @property bool $is_approved Admin moderated
 * @property bool $points_awarded Track if bonus points given
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Product $product
 * @property-read Order|null $order
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',          // 1-5 stars
        'title',
        'content',
        'is_verified',     // Verified buyer
        'is_approved',     // Admin moderated
        'points_awarded',  // Track if bonus points given
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'points_awarded' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Points bonus for approved review (from proposal)
     */
    const REVIEW_BONUS_POINTS = 50;

    /**
     * Rating labels
     */
    const RATINGS = [
        1 => 'Sangat Buruk',
        2 => 'Buruk',
        3 => 'Cukup',
        4 => 'Bagus',
        5 => 'Sangat Bagus',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for approved reviews only
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for verified buyer reviews
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for pending moderation
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Approve review and award bonus points
     */
    public function approve(int $approvedBy): bool
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);

        // Award bonus points if not already awarded
        if (!$this->points_awarded && $this->user) {
            $this->user->addPoints(
                self::REVIEW_BONUS_POINTS,
                'review',
                null,
                "Bonus review untuk produk: {$this->product?->name}"
            );
            
            $this->update(['points_awarded' => true]);
        }

        return true;
    }

    /**
     * Get rating display with stars
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }

    /**
     * Get rating label
     */
    public function getRatingLabelAttribute(): string
    {
        return self::RATINGS[$this->rating] ?? 'Unknown';
    }

    /**
     * Check if user has already reviewed this product
     */
    public static function hasReviewed(int $userId, int $productId): bool
    {
        return static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get average rating for a product
     */
    public static function getAverageRating(int $productId): float
    {
        return (float) static::where('product_id', $productId)
            ->approved()
            ->avg('rating') ?? 0;
    }

    /**
     * Get review count for a product
     */
    public static function getReviewCount(int $productId): int
    {
        return static::where('product_id', $productId)
            ->approved()
            ->count();
    }
}
