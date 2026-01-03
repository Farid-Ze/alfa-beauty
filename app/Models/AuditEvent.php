<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AuditEvent
 *
 * Governance + traceability log for business-critical writes.
 */
class AuditEvent extends Model
{
    protected $fillable = [
        'request_id',
        'idempotency_key',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
