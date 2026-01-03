<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier Model
 * 
 * Represents product suppliers for batch inventory tracking.
 * Required for proper batch_number uniqueness across different sources.
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'npwp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function batchInventory(): HasMany
    {
        return $this->hasMany(BatchInventory::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Scopes
     * ───────────────────────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->whereRaw('is_active = true');
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    /**
     * Generate a unique supplier code
     */
    public static function generateCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $count = self::where('code', 'like', $prefix . '%')->count();
        
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
