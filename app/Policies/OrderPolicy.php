<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Order Policy
 * 
 * Controls access to order resources. Regular users can only
 * view their own orders, while admins can access all orders.
 */
class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their own orders list
        return true;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Staff/Admin can view any order
        if ($user->isStaff()) {
            return true;
        }

        // Regular users can only view their own orders
        return $user->id === $order->user_id;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create orders
        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admin/staff can update orders
        return $user->isStaff();
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admin can delete orders
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Staff can cancel any order
        if ($user->isStaff()) {
            return true;
        }

        // Users can only cancel their own pending orders
        return $user->id === $order->user_id && 
               in_array($order->status, ['pending', 'awaiting_payment']);
    }

    /**
     * Determine whether the user can confirm payment.
     */
    public function confirmPayment(User $user, Order $order): bool
    {
        // Only staff can confirm payment
        return $user->isStaff();
    }
}
