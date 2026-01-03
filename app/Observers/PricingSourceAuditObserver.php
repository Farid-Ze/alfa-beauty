<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PricingSourceAuditObserver
{
    /**
     * @param string[] $trackedAttributes
     */
    public function __construct(private readonly array $trackedAttributes = [])
    {
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();

        $diff = [];
        foreach ($changes as $key => $to) {
            if (!in_array($key, $this->trackedAttributes, true)) {
                continue;
            }

            $diff[$key] = [
                'from' => $original[$key] ?? null,
                'to' => $to,
            ];
        }

        if (empty($diff)) {
            return;
        }

        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => null,
                'actor_user_id' => Auth::id(),
                'action' => 'pricing_source.updated',
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
                'meta' => [
                    'diff' => $diff,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('PricingSource audit failed', [
                'error' => $e->getMessage(),
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
            ]);
        }
    }
}
