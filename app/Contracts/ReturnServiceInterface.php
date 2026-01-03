<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\OrderReturn;

interface ReturnServiceInterface
{
    public function approveReturn(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn;

    public function markReturnReceived(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn;

    public function completeReturn(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn;

    public function rejectReturn(OrderReturn $return, string $reason, ?int $processedBy = null, ?string $requestId = null): OrderReturn;
}
