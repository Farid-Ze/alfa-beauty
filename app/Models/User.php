<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'password',
        'points',
        'total_spend',
        'loyalty_tier_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'total_spend' => 'decimal:2',
        ];
    }

    public function loyaltyTier()
    {
        return $this->belongsTo(LoyaltyTier::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get customer payment terms.
     */
    public function paymentTerm()
    {
        return $this->hasOne(CustomerPaymentTerm::class);
    }

    /**
     * Get customer-specific price lists.
     */
    public function priceLists()
    {
        return $this->hasMany(CustomerPriceList::class);
    }

    /**
     * Check if user has B2B pricing.
     */
    public function getHasB2bPricingAttribute(): bool
    {
        return $this->priceLists()->valid()->exists();
    }

    /**
     * Check if user can use credit terms.
     */
    public function getCanUseCreditAttribute(): bool
    {
        return $this->paymentTerm?->is_approved 
            && $this->paymentTerm?->term_type !== CustomerPaymentTerm::TERM_COD;
    }

    /**
     * Get available credit amount.
     */
    public function getAvailableCreditAttribute(): float
    {
        return $this->paymentTerm?->available_credit ?? 0;
    }
}
