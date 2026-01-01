<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;

/**
 * Contract for order processing services.
 *
 * Defines the interface for creating, processing, and managing orders
 * including checkout flow and order completion.
 */
interface OrderServiceInterface
{
    /**
     * Create a new order from the user's cart.
     *
     * @param User $user The user placing the order
     * @param array $shippingData Shipping address and method details
     * @param string|null $notes Optional order notes
     * @return Order The created order
     * @throws \App\Exceptions\InsufficientStockException
     * @throws \App\Exceptions\InvalidOrderException
     */
    public function createOrder(User $user, array $shippingData, ?string $notes = null): Order;

    /**
     * Complete an order after payment confirmation.
     *
     * @param Order $order The order to complete
     * @param array $paymentData Payment confirmation details
     * @return Order The updated order
     */
    public function completeOrder(Order $order, array $paymentData = []): Order;

    /**
     * Cancel an order.
     *
     * @param Order $order The order to cancel
     * @param string $reason The cancellation reason
     * @return Order The updated order
     */
    public function cancelOrder(Order $order, string $reason): Order;

    /**
     * Get order by ID with authorization check.
     *
     * @param int $orderId The order ID
     * @param User $user The requesting user
     * @return Order|null
     */
    public function getOrder(int $orderId, User $user): ?Order;

    /**
     * Get paginated orders for a user.
     *
     * @param User $user The user
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserOrders(User $user, int $perPage = 10);
}
