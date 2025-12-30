<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'points' => $this->points,
            'total_spend' => $this->total_spend,
            'loyalty_tier' => $this->whenLoaded('loyaltyTier', function () {
                return [
                    'id' => $this->loyaltyTier->id,
                    'name' => $this->loyaltyTier->name,
                    'slug' => $this->loyaltyTier->slug,
                    'discount_percent' => $this->loyaltyTier->discount_percent,
                    'point_multiplier' => $this->loyaltyTier->point_multiplier,
                    'free_shipping' => $this->loyaltyTier->free_shipping,
                    'badge_color' => $this->loyaltyTier->badge_color,
                ];
            }),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
