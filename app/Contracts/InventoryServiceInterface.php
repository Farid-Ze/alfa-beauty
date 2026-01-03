<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for inventory management services.
 *
 * Defines the interface for managing product stock, batch inventory,
 * and FEFO (First Expired, First Out) allocation.
 */
interface InventoryServiceInterface
{
    /**
     * Allocate stock from batches using FEFO algorithm.
     *
     * @param int $productId
     * @param int $quantity
     * @param string|null $orderId Optional reference for logging
     * @return array Allocation details with batch information
     */
    public function allocateStock(int $productId, int $quantity, ?string $orderId = null): array;

    /**
     * Release stock back to batches for cancelled/refunded orders.
     *
     * @param array $allocations Allocations returned by allocateStock()
     * @param string|null $reason Optional audit/log reason
     */
    public function releaseStock(array $allocations, ?string $reason = null): void;

    /**
     * Check if product has sufficient stock across batches.
     */
    public function hasAvailableStock(int $productId, int $quantity): bool;
}
