<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an order operation is invalid.
 */
class InvalidOrderException extends Exception
{
    protected ?int $orderId;
    protected string $reason;

    public function __construct(
        string $reason,
        ?int $orderId = null,
        string $message = ''
    ) {
        $this->orderId = $orderId;
        $this->reason = $reason;

        $message = $message ?: "Invalid order operation: {$reason}";
        if ($orderId) {
            $message .= " (Order ID: {$orderId})";
        }

        parent::__construct($message, 400);
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Report the exception for logging.
     */
    public function report(): bool
    {
        \Illuminate\Support\Facades\Log::warning('Invalid order operation', [
            'order_id' => $this->orderId,
            'reason' => $this->reason,
        ]);

        return true;
    }

    /**
     * Render the exception as HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'invalid_order',
                'message' => $this->getMessage(),
                'reason' => $this->reason,
                'order_id' => $this->orderId,
            ], 400);
        }

        return back()->with('error', $this->getMessage());
    }
}
