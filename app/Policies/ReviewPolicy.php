<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Review Policy
 * 
 * Controls access to review resources.
 */
class ReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view approved reviews (public)
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(?User $user, Review $review): bool
    {
        // Approved reviews are public
        if ($review->is_approved) {
            return true;
        }

        // Unapproved reviews only visible to owner or staff
        if (!$user) {
            return false;
        }

        return $user->id === $review->user_id || $user->isStaff();
    }

    /**
     * Determine whether the user can create reviews.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create reviews
        return true;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        // Users can update their own unapproved reviews
        if ($user->id === $review->user_id && !$review->is_approved) {
            return true;
        }

        // Staff can update any review
        return $user->isStaff();
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        // Users can delete their own reviews
        if ($user->id === $review->user_id) {
            return true;
        }

        // Staff can delete any review
        return $user->isStaff();
    }

    /**
     * Determine whether the user can approve the review.
     */
    public function approve(User $user, Review $review): bool
    {
        // Only staff can approve reviews
        return $user->isStaff();
    }
}
