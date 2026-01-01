<?php

namespace App\Contracts;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;

/**
 * Contract for shopping cart services.
 *
 * Defines the interface for managing shopping cart operations
 * including adding, updating, and removing items.
 */
interface CartServiceInterface
{
    /**
     * Get the current cart for the session/user.
     *
     * @return Cart|null
     */
    public function getCart(): ?Cart;

    /**
     * Add a product to the cart.
     *
     * @param Product $product The product to add
     * @param int $quantity The quantity to add
     * @return bool Whether the operation succeeded
     */
    public function addItem(Product $product, int $quantity = 1): bool;

    /**
     * Update the quantity of an item in the cart.
     *
     * @param int $itemId The cart item ID
     * @param int $quantity The new quantity
     * @return bool Whether the operation succeeded
     */
    public function updateQuantity(int $itemId, int $quantity): bool;

    /**
     * Remove an item from the cart.
     *
     * @param int $itemId The cart item ID
     * @return bool Whether the operation succeeded
     */
    public function removeItem(int $itemId): bool;

    /**
     * Clear all items from the cart.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Get the total number of items in the cart.
     *
     * @return int
     */
    public function getItemCount(): int;

    /**
     * Get the cart subtotal (before discounts/shipping).
     *
     * @return float
     */
    public function getSubtotal(): float;

    /**
     * Transfer guest cart to authenticated user.
     *
     * @param User $user The user to transfer cart to
     * @return void
     */
    public function transferToUser(User $user): void;
}
