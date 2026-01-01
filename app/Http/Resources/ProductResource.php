<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'formatted_price' => 'Rp ' . number_format($this->base_price, 0, ',', '.'),
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'points' => $this->points,
            /** @phpstan-ignore argument.type */
            'brand' => new BrandResource($this->whenLoaded('brand')),
            /** @phpstan-ignore argument.type */
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->images ?? [],
            'is_halal' => $this->is_halal,
            'is_vegan' => $this->is_vegan,
            'bpom_number' => $this->bpom_number,
            'is_featured' => $this->is_featured,
            // B2B specific fields
            'min_order_qty' => $this->min_order_qty ?? 1,
            'order_increment' => $this->order_increment ?? 1,
            'selling_unit' => $this->selling_unit,
            'units_per_case' => $this->units_per_case,
            'has_volume_pricing' => $this->priceTiers->isNotEmpty(),
            'price_tiers' => $this->when($this->relationLoaded('priceTiers'), function () {
                return $this->priceTiers->map(fn($tier) => [
                    'min_quantity' => $tier->min_quantity,
                    'price' => $tier->price,
                    'formatted_price' => 'Rp ' . number_format($tier->price, 0, ',', '.'),
                    'discount_percent' => $tier->discount_percent,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
