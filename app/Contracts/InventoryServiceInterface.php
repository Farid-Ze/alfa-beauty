<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use App\Models\Product;

/**
 * Contract for inventory management services.
 *
 * Defines the interface for managing product stock, batch inventory,
 * and FEFO (First Expired, First Out) allocation.
 */
interface InventoryServiceInterface
{
    /**
     * Check if sufficient stock is available for a product.
     *
     * @param Product $product The product to check
     * @param int $quantity The required quantity
     * @return bool Whether stock is available
     */
    public function checkStock(Product $product, int $quantity): bool;

    /**
     * Reserve stock for an order (pre-checkout).
     *
     * @param Product $product The product
     * @param int $quantity The quantity to reserve
     * @return bool Whether reservation succeeded
     */
    public function reserveStock(Product $product, int $quantity): bool;

    /**
     * Release reserved stock (cancelled order or timeout).
     *
     * @param Product $product The product
     * @param int $quantity The quantity to release
     * @return void
     */
    public function releaseStock(Product $product, int $quantity): void;

    /**
     * Allocate stock to an order using FEFO method.
     *
     * @param Order $order The order to allocate stock for
     * @return array Allocation details with batch information
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function allocateOrderStock(Order $order): array;

    /**
     * Get products with low stock levels.
     *
     * @param int $threshold Stock level threshold
     * @return \Illuminate\Support\Collection
     */
    public function getLowStockProducts(int $threshold = 10);

    /**
     * Get batches nearing expiration.
     *
     * @param int $days Days until expiration
     * @return \Illuminate\Support\Collection
     */
    public function getExpiringBatches(int $days = 30);

    /**
     * Sync product stock with batch inventory totals.
     *
     * @param Product $product The product to sync
     * @return int The updated stock level
     */
    public function syncProductStock(Product $product): int;
}
