<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when quantity doesn't meet MOQ or increment requirements.
 */
class InvalidQuantityException extends Exception
{
    protected int $productId;
    protected int $requestedQuantity;
    protected int $minQuantity;
    protected int $increment;

    public function __construct(
        int $productId,
        int $requestedQuantity,
        int $minQuantity,
        int $increment = 1,
        string $message = ''
    ) {
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->minQuantity = $minQuantity;
        $this->increment = $increment;

        if (!$message) {
            if ($requestedQuantity < $minQuantity) {
                $message = "Minimum order quantity is {$minQuantity} for product ID {$productId}.";
            } else {
                $message = "Quantity must be in increments of {$increment} for product ID {$productId}.";
            }
        }

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

    public function getMinQuantity(): int
    {
        return $this->minQuantity;
    }

    public function getIncrement(): int
    {
        return $this->increment;
    }

    /**
     * Render the exception as HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'invalid_quantity',
                'message' => $this->getMessage(),
                'product_id' => $this->productId,
                'requested' => $this->requestedQuantity,
                'min_quantity' => $this->minQuantity,
                'increment' => $this->increment,
            ], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
