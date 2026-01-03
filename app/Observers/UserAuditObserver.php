<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UserAuditObserver
{
    /** @var list<string> */
    private array $governedFields = [
        'points',
        'total_spend',
        'loyalty_tier_id',
        'tier_evaluated_at',
        'tier_valid_until',
        'current_period_spend',
    ];

    public function updated(User $user): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            $requestId = request()?->attributes?->get('request_id');
            $actorUserId = auth()->id();
            // Avoid audit spam from console/background jobs with no trace context.
            if ($requestId === null && $actorUserId === null) {
                return;
            }

            $dirty = array_keys($user->getDirty());
            $changedGoverned = array_values(array_intersect($dirty, $this->governedFields));
            if ($changedGoverned === []) {
                return;
            }

            $changes = [];
            foreach ($changedGoverned as $field) {
                $changes[$field] = [
                    'from' => $user->getOriginal($field),
                    'to' => $user->{$field},
                ];
            }

            AuditEvent::create([
                'request_id' => $requestId,
                'idempotency_key' => null,
                'actor_user_id' => $actorUserId,
                'action' => 'user.governed_fields_updated',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'meta' => [
                    'changed' => $changes,
                    'source' => 'observer',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('UserAuditObserver failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }
}
