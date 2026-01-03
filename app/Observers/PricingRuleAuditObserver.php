<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PricingRuleAuditObserver
{
    public function created(Model $model): void
    {
        $this->audit($model, 'pricing_rule.created', [
            'attributes' => $this->onlyFillable($model, $model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();

        $diff = [];
        foreach ($changes as $key => $to) {
            $diff[$key] = [
                'from' => $original[$key] ?? null,
                'to' => $to,
            ];
        }

        $this->audit($model, 'pricing_rule.updated', [
            'diff' => $this->onlyFillable($model, $diff),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->audit($model, 'pricing_rule.deleted', [
            'attributes' => $this->onlyFillable($model, $model->getAttributes()),
        ]);
    }

    protected function audit(Model $model, string $action, array $meta): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => null,
                'actor_user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PricingRule audit failed', [
                'error' => $e->getMessage(),
                'action' => $action,
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
            ]);
        }
    }

    protected function onlyFillable(Model $model, array $payload): array
    {
        $fillable = $model->getFillable();
        if (empty($fillable)) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($fillable));
    }
}
