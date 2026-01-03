<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;

/**
 * PermissionService
 * 
 * Centralized permission checks for governance and access control.
 * 
 * GOVERNANCE PRINCIPLES:
 * - Single source of truth for authorization
 * - Explicit over implicit permissions
 * - Audit-friendly (all checks can be logged)
 * 
 * ROLES:
 * - admin: Full access
 * - staff: Limited operational access
 * - customer: Self-service access only
 */
class PermissionService
{
    /**
     * Check if user can override product price.
     * Only admins can override system-calculated prices.
     */
    public function canOverridePrice(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Check if user can cancel an order.
     * - Owner of order can cancel their own
     * - Admin/Staff can cancel any order
     */
    public function canCancelOrder(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
            return $order->canBeCancelled();
        }

        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can view an order.
     * - Owner can view their own orders
     * - Admin/Staff can view all orders
     */
    public function canViewOrder(User $user, Order $order): bool
    {
        return $user->id === $order->user_id 
            || in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can approve a return request.
     * Only admin/staff can approve returns.
     */
    public function canApproveReturn(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can process a return (mark received, complete).
     */
    public function canProcessReturn(User $user, OrderReturn $return): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can apply manual discount.
     * Only admins can apply manual discounts outside of rules.
     */
    public function canApplyManualDiscount(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Check if user can modify customer price list.
     * Only admins can create/update customer-specific pricing.
     */
    public function canModifyPriceList(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Check if user can confirm WhatsApp payment.
     * Admin/Staff can confirm payments.
     */
    public function canConfirmPayment(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdminPanel(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can manage inventory batches.
     */
    public function canManageInventory(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Check if user can view audit logs.
     * Only admins can view audit trail.
     */
    public function canViewAuditLogs(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Check if user can export data.
     * Admin/Staff can export.
     */
    public function canExportData(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }
}
