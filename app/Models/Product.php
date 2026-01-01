<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Product Model
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $slug
 * @property int $brand_id
 * @property int $category_id
 * @property float $base_price
 * @property int $stock
 * @property string|null $description
 * @property array|null $images
 * @property bool $is_halal
 * @property bool $is_vegan
 * @property bool $is_active
 * @property bool $is_featured
 * @property string|null $bpom_number
 * @property int|null $weight_grams
 * @property int|null $length_mm
 * @property int|null $width_mm
 * @property int|null $height_mm
 * @property string|null $selling_unit
 * @property int|null $units_per_case
 * @property int $min_order_qty
 * @property int $order_increment
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Brand $brand
 * @property-read Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection|Review[] $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection|Review[] $approvedReviews
 * @property-read \Illuminate\Database\Eloquent\Collection|ProductPriceTier[] $priceTiers
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'brand_id',
        'category_id',
        'base_price',
        'stock',
        'description',
        'inci_list',
        'how_to_use',
        'is_halal',
        'is_vegan',
        'bpom_number',
        'images',
        'video_url',
        'msds_url',
        'is_active',
        'is_featured',
        'weight_grams',
        'length_mm',
        'width_mm',
        'height_mm',
        'selling_unit',
        'units_per_case',
        'min_order_qty',
        'order_increment',
    ];

    protected $casts = [
        'images' => 'array',
        'is_halal' => 'boolean',
        'is_vegan' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'base_price' => 'float',
        'weight_grams' => 'integer',
        'length_mm' => 'integer',
        'width_mm' => 'integer',
        'height_mm' => 'integer',
        'units_per_case' => 'integer',
        'min_order_qty' => 'integer',
        'order_increment' => 'integer',
    ];

    /**
     * Alias for base_price to ensure consistent access.
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->base_price;
    }

    /**
     * Points earned per purchase (1 point per Rp 10,000).
     */
    public function getPointsAttribute(): int
    {
        return (int) floor($this->base_price / 10000);
    }

    /**
     * Check if product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get volume price tiers.
     */
    public function priceTiers()
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('min_quantity');
    }

    /**
     * Get batch inventory records.
     */
    public function batches()
    {
        return $this->hasMany(BatchInventory::class);
    }

    /**
     * Get product reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved product reviews.
     */
    public function approvedReviews()
    {
        return $this->reviews()->approved();
    }

    /**
     * Get active batch inventory.
     */
    public function activeBatches()
    {
        return $this->batches()->active();
    }

    /**
     * Check if product has volume pricing.
     */
    public function getHasVolumePricingAttribute(): bool
    {
        return $this->priceTiers()->exists();
    }

    /**
     * Get MOQ overrides for this product.
     */
    public function moqOverrides()
    {
        return $this->hasMany(ProductMoqOverride::class);
    }

    /**
     * Get applicable discount rules.
     */
    public function discountRules()
    {
        return $this->hasMany(DiscountRule::class);
    }

    /**
     * Get weight in kilograms for display.
     */
    public function getWeightKgAttribute(): float
    {
        return round($this->weight_grams / 1000, 2);
    }

    /**
     * Get volumetric weight in grams (for shipping calculation).
     * Standard divisor: 5000 for domestic, 6000 for international.
     */
    public function getVolumetricWeightAttribute(): int
    {
        if (!$this->length_mm || !$this->width_mm || !$this->height_mm) {
            return $this->weight_grams;
        }

        // Convert mm to cm, calculate volumetric weight
        $volumeCm3 = ($this->length_mm / 10) * ($this->width_mm / 10) * ($this->height_mm / 10);
        $volumetricGrams = ($volumeCm3 / 5000) * 1000; // Domestic divisor

        return (int) max($this->weight_grams, $volumetricGrams);
    }

    /**
     * Get effective MOQ for a user.
     */
    public function getEffectiveMoq(?User $user = null): array
    {
        if ($user) {
            return ProductMoqOverride::getEffectiveMoq($user, $this);
        }

        return [
            'min_order_qty' => $this->min_order_qty ?? 1,
            'order_increment' => $this->order_increment ?? 1,
            'max_order_qty' => null,
            'source' => 'product',
        ];
    }

    /**
     * Validate order quantity against MOQ rules.
     */
    public function validateQuantity(int $quantity, ?User $user = null): array
    {
        $moq = $this->getEffectiveMoq($user);
        $errors = [];

        if ($quantity < $moq['min_order_qty']) {
            $errors['min_qty'] = sprintf(
                'Minimum order %d %s untuk produk ini',
                $moq['min_order_qty'],
                $this->selling_unit ?? 'pcs'
            );
        }

        if ($moq['order_increment'] > 1 && $quantity % $moq['order_increment'] !== 0) {
            $errors['increment'] = sprintf(
                'Quantity harus kelipatan %d',
                $moq['order_increment']
            );
        }

        if ($moq['max_order_qty'] && $quantity > $moq['max_order_qty']) {
            $errors['max_qty'] = sprintf(
                'Maximum order %d %s',
                $moq['max_order_qty'],
                $this->selling_unit ?? 'pcs'
            );
        }

        return $errors;
    }

    /**
     * Calculate shipping weight for given quantity.
     */
    public function calculateShippingWeight(int $quantity): int
    {
        return $this->volumetric_weight * $quantity;
    }
}
