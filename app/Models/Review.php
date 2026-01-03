<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        return DB::transaction(function () use ($approvedBy) {
            /** @var self $locked */
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            // Idempotent: if already approved and points awarded, no-op.
            if ($locked->is_approved && $locked->points_awarded) {
                return true;
            }

            $locked->forceFill([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ])->save();

            // Award bonus points if not already awarded
            if (!$locked->points_awarded && $locked->user_id) {
                $locked->loadMissing(['user', 'product']);
                if ($locked->user) {
                    $idempotencyKey = "earn:review:{$locked->id}:user:{$locked->user_id}";
                    $locked->user->addPoints(
                        self::REVIEW_BONUS_POINTS,
                        'review',
                        null,
                        "Bonus review untuk produk: {$locked->product?->name}",
                        $idempotencyKey,
                        request()?->attributes?->get('request_id'),
                    );

                    $locked->forceFill(['points_awarded' => true])->save();

                    $this->auditEvent([
                        'request_id' => request()?->attributes?->get('request_id'),
                        'idempotency_key' => $idempotencyKey,
                        'actor_user_id' => $approvedBy,
                        'action' => 'review.approved',
                        'entity_type' => self::class,
                        'entity_id' => $locked->id,
                        'meta' => [
                            'user_id' => $locked->user_id,
                            'product_id' => $locked->product_id,
                            'order_id' => $locked->order_id,
                            'rating' => $locked->rating,
                            'points_awarded' => self::REVIEW_BONUS_POINTS,
                        ],
                    ]);
                }
            }

            return true;
        });
    }

    protected function auditEvent(array $payload): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditEvent write failed', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
            ]);
        }
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
