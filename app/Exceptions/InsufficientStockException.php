<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when stock is insufficient for an operation.
 */
class InsufficientStockException extends Exception
{
    protected int $productId;
    protected int $requestedQuantity;
    protected int $availableStock;

    public function __construct(
        int $productId,
        int $requestedQuantity,
        int $availableStock,
        string $message = ''
    ) {
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableStock = $availableStock;

        $message = $message ?: "Insufficient stock for product ID {$productId}. Requested: {$requestedQuantity}, Available: {$availableStock}";

        parent::__construct($message, 422);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    public function getAvailableStock(): int
    {
        return $this->availableStock;
    }

    /**
     * Report the exception for logging.
     */
    public function report(): bool
    {
        // Log as warning, not error (business logic exception)
        \Illuminate\Support\Facades\Log::warning('Insufficient stock attempt', [
            'product_id' => $this->productId,
            'requested' => $this->requestedQuantity,
            'available' => $this->availableStock,
        ]);

        return true; // Don't report to default handler
    }

    /**
     * Render the exception as HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'insufficient_stock',
                'message' => $this->getMessage(),
                'product_id' => $this->productId,
                'requested' => $this->requestedQuantity,
                'available' => $this->availableStock,
            ], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
