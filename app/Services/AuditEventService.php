<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditEventService
{
    /**
     * Best-effort governance log.
     *
     * Idempotency is enforced by checking existing idempotency_key.
     * A DB unique index on idempotency_key (when present) further hardens this.
     */
    public function record(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        array $meta = [],
        ?string $idempotencyKey = null,
        ?string $requestId = null,
        ?int $actorUserId = null,
    ): void {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            if ($idempotencyKey) {
                $exists = AuditEvent::where('idempotency_key', $idempotencyKey)->exists();
                if ($exists) {
                    return;
                }
            }

            AuditEvent::create([
                'request_id' => $requestId,
                'idempotency_key' => $idempotencyKey,
                'actor_user_id' => $actorUserId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => is_numeric($entityId) ? (int) $entityId : null,
                'meta' => empty($meta) ? null : $meta,
            ]);
        } catch (\Throwable $e) {
            if ($idempotencyKey && $this->isUniqueConstraintViolation($e)) {
                // Idempotency race: another request already wrote this event.
                return;
            }
            Log::warning('Failed to write audit event', [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'idempotency_key' => $idempotencyKey,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function isUniqueConstraintViolation(\Throwable $e): bool
    {
        if (!$e instanceof QueryException) {
            return false;
        }

        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if ($sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062) {
            return true;
        }

        // SQLite is often just HY000 with message containing 'unique'.
        return str_contains($message, 'unique constraint') || str_contains($message, 'duplicate');
    }
}
