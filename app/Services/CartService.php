<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * CartService
 * 
 * Manages shopping cart operations with B2B pricing integration.
 * 
 * PERFORMANCE NOTES:
 * - Uses bulk queries to prevent N+1 issues
 * - PricingService::getBulkPrices() for cart totals
 * - Single addItem() call handles any quantity (no loops)
 */
class CartService
{
    protected ?Cart $cart = null;
    protected const COOKIE_NAME = 'cart_session_id';
    protected const COOKIE_LIFETIME = 60 * 24 * 30; // 30 days

    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function getCart(): ?Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        if (Auth::check()) {
            $this->cart = Cart::with(['items.product.brand', 'items.product.priceTiers'])
                ->where('user_id', Auth::id())
                ->latest()
                ->first();
        } else {
            $sessionId = Cookie::get(self::COOKIE_NAME);
            if ($sessionId) {
                $this->cart = Cart::with(['items.product.brand', 'items.product.priceTiers'])
                    ->where('session_id', $sessionId)
                    ->latest()
                    ->first();
            }
        }

        return $this->cart;
    }

    public function getOrCreateCart(): Cart
    {
        $cart = $this->getCart();

        if ($cart) {
            return $cart;
        }

        if (Auth::check()) {
            $cart = Cart::create(['user_id' => Auth::id()]);
        } else {
            $sessionId = Cookie::get(self::COOKIE_NAME);
            if (!$sessionId) {
                $sessionId = Str::uuid()->toString();
                Cookie::queue(self::COOKIE_NAME, $sessionId, self::COOKIE_LIFETIME);
            }
            $cart = Cart::create(['session_id' => $sessionId]);
        }

        $this->cart = $cart;
        return $cart;
    }

    /**
     * Add item to cart.
     * 
     * PERFORMANCE: This accepts quantity directly, so adding 500 units
     * is a single database operation, not 500 loops.
     * 
     * VALIDATION: Enforces min_order_qty and order_increment rules.
     *
     * @param int $productId
     * @param int $quantity Amount to add (can be any positive number)
     * @return CartItem
     * @throws \InvalidArgumentException If quantity doesn't meet MOQ/increment rules
     */
    public function addItem(int $productId, int $quantity = 1): CartItem
    {
        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($productId);

        // Get existing quantity if any
        $existingItem = $cart->items()->where('product_id', $productId)->first();
        $existingQty = $existingItem?->quantity ?? 0;
        $newTotalQty = $existingQty + $quantity;

        // Validate MOQ and increment
        $validatedQty = $this->validateAndAdjustQuantity($product, $newTotalQty);

        // Get current B2B price for this user
        $priceInfo = $this->pricingService->getPrice(
            $product,
            Auth::user(),
            $validatedQty
        );

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $validatedQty,
                'price_at_add' => $priceInfo['price'],
            ]);
            
            return $existingItem->fresh();
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $validatedQty,
            'price_at_add' => $priceInfo['price'],
        ]);
    }

    /**
     * Validate and adjust quantity to meet MOQ and increment rules.
     *
     * @param Product $product
     * @param int $requestedQty
     * @return int Adjusted quantity
     */
    protected function validateAndAdjustQuantity(Product $product, int $requestedQty): int
    {
        $minQty = $product->min_order_qty ?? 1;
        $increment = $product->order_increment ?? 1;

        // Ensure at least minimum
        $qty = max($minQty, $requestedQty);

        // Round up to nearest valid increment
        if ($increment > 1) {
            $overMin = $qty - $minQty;
            $qty = $minQty + (int) ceil($overMin / $increment) * $increment;
        }

        return $qty;
    }

    /**
     * Set exact quantity for a cart item.
     * 
     * VALIDATION: Enforces min_order_qty and order_increment rules.
     * 
     * @param int $itemId
     * @param int $quantity New quantity (0 = remove)
     * @return CartItem|null
     */
    public function updateQuantity(int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->getCart();
        if (!$cart) return null;

        $item = $cart->items()->find($itemId);

        if (!$item) return null;

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        // Validate and adjust quantity for MOQ/increment
        $product = $item->product;
        $validatedQty = $this->validateAndAdjustQuantity($product, $quantity);

        // Recalculate price for new quantity (might hit different tier)
        $priceInfo = $this->pricingService->getPrice(
            $product,
            Auth::user(),
            $validatedQty
        );

        $item->update([
            'quantity' => $validatedQty,
            'price_at_add' => $priceInfo['price'],
        ]);
        
        return $item->fresh();
    }

    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCart();
        if (!$cart) return false;

        return (bool) $cart->items()->where('id', $itemId)->delete();
    }

    public function clearCart(): void
    {
        $cart = $this->getCart();
        if ($cart) {
            $cart->items()->delete();
        }
    }

    public function getItemCount(): int
    {
        $cart = $this->getCart();
        return $cart ? $cart->items()->sum('quantity') : 0;
    }

    /**
     * Get subtotal using B2B pricing.
     * 
     * PERFORMANCE: Uses bulk pricing lookup (single query for all products).
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        $cart = $this->getCart();
        if (!$cart || $cart->items->isEmpty()) return 0;

        // Build [product_id => quantity] map
        $productQuantities = $cart->items->pluck('quantity', 'product_id')->toArray();
        
        // Get all products in single query
        $products = Product::whereIn('id', array_keys($productQuantities))->get();
        
        // Build input for bulk pricing
        $productsWithQty = $products->map(function ($product) use ($productQuantities) {
            $product->quantity = $productQuantities[$product->id] ?? 1;
            return $product;
        });

        // Get all prices in single bulk operation
        $prices = $this->pricingService->getBulkPrices($productsWithQty, Auth::user());

        // Calculate total
        $total = 0;
        foreach ($cart->items as $item) {
            $unitPrice = $prices[$item->product_id]['price'] ?? $item->product->base_price;
            $total += $item->quantity * $unitPrice;
        }

        return $total;
    }

    /**
     * Validate that all cart items have sufficient stock.
     * 
     * NOTE: Only validates global stock (product->stock) for simplicity.
     * Batch-level validation is handled at order creation time.
     * 
     * @return array Array of stock errors, empty if all valid
     */
    public function validateStock(): array
    {
        $cart = $this->getCart();
        if (!$cart) return [];

        $errors = [];

        // Bulk load products for efficiency
        $productIds = $cart->items->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cart->items as $item) {
            $product = $products[$item->product_id] ?? null;
            if (!$product) continue;
            
            // Only check global stock - batch allocation happens at order time
            if ($item->quantity > $product->stock) {
                $errors[] = [
                    'item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'requested' => $item->quantity,
                    'available' => $product->stock,
                ];
            }
        }

        return $errors;
    }

    /**
     * Check if cart has sufficient stock for all items.
     * 
     * @return bool
     */
    public function hasValidStock(): bool
    {
        return empty($this->validateStock());
    }

    /**
     * Refresh cart item prices using B2B pricing rules.
     * 
     * IMPORTANT: This uses PricingService to get the correct B2B price
     * for each customer, NOT resetting to base_price.
     * 
     * @return array Price changes: [['product_id' => x, 'old' => y, 'new' => z], ...]
     */
    public function refreshPrices(): array
    {
        $cart = $this->getCart();
        if (!$cart || $cart->items->isEmpty()) return [];

        $user = Auth::user();
        $changes = [];

        // Build [product_id => quantity] map for bulk pricing
        $productQuantities = $cart->items->pluck('quantity', 'product_id')->toArray();
        
        // Get all products in single query
        $products = Product::whereIn('id', array_keys($productQuantities))->get();
        
        // Build input for bulk pricing
        $productsWithQty = $products->map(function ($product) use ($productQuantities) {
            $product->quantity = $productQuantities[$product->id] ?? 1;
            return $product;
        });

        // Get all B2B prices in single bulk operation
        $prices = $this->pricingService->getBulkPrices($productsWithQty, $user);

        // Update each item
        foreach ($cart->items as $item) {
            $priceInfo = $prices[$item->product_id] ?? null;
            if (!$priceInfo) continue;

            $currentPrice = $priceInfo['price'];
            $oldPrice = $item->price_at_add;
            
            // If price differs, record the change
            if ($oldPrice !== null && abs($oldPrice - $currentPrice) > 0.01) {
                $changes[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'old_price' => $oldPrice,
                    'new_price' => $currentPrice,
                    'difference' => $currentPrice - $oldPrice,
                    'source' => $priceInfo['source'],
                ];
            }
            
            // Always update to current B2B price
            $item->update(['price_at_add' => $currentPrice]);
        }

        // Refresh cart items
        if (!empty($changes)) {
            $cart->load('items.product.brand');
        }

        return $changes;
    }

    /**
     * Get subtotal using fresh B2B prices.
     * 
     * @return float
     */
    public function getFreshSubtotal(): float
    {
        // refreshPrices updates stored prices, then getSubtotal uses them
        $this->refreshPrices();
        return $this->getSubtotal();
    }

    /**
     * Get detailed cart breakdown with pricing info.
     * 
     * @return array
     */
    public function getDetailedCart(): array
    {
        $cart = $this->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return [
                'items' => [],
                'subtotal' => 0,
                'item_count' => 0,
            ];
        }

        $user = Auth::user();
        
        // Build [product_id => quantity] map
        $productQuantities = $cart->items->pluck('quantity', 'product_id')->toArray();
        
        // Get all products
        $products = Product::whereIn('id', array_keys($productQuantities))->get()->keyBy('id');
        
        // Build input for bulk pricing
        $productsWithQty = $products->map(function ($product) use ($productQuantities) {
            $product->quantity = $productQuantities[$product->id] ?? 1;
            return $product;
        });

        // Get all B2B prices
        $prices = $this->pricingService->getBulkPrices($productsWithQty, $user);

        $items = [];
        $subtotal = 0;

        foreach ($cart->items as $item) {
            $product = $products[$item->product_id] ?? $item->product;
            $priceInfo = $prices[$item->product_id] ?? [
                'price' => $product->base_price,
                'source' => 'base_price',
                'original_price' => $product->base_price,
            ];

            $lineTotal = $item->quantity * $priceInfo['price'];
            $subtotal += $lineTotal;

            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $product,
                'quantity' => $item->quantity,
                'unit_price' => $priceInfo['price'],
                'original_price' => $priceInfo['original_price'] ?? $product->base_price,
                'discount_percent' => $priceInfo['discount_percent'] ?? null,
                'price_source' => $priceInfo['source'],
                'line_total' => $lineTotal,
            ];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'item_count' => $cart->items->sum('quantity'),
        ];
    }

    /**
     * Validate that all cart items meet MOQ and increment requirements.
     * 
     * Returns array of violations (empty = all valid).
     * Also auto-adjusts quantities if $autoFix is true.
     * 
     * @param bool $autoFix If true, automatically adjusts invalid quantities
     * @return array Array of MOQ/increment violations
     */
    public function validateMOQ(bool $autoFix = false): array
    {
        $cart = $this->getCart();
        if (!$cart || $cart->items->isEmpty()) return [];

        $violations = [];
        $productIds = $cart->items->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cart->items as $item) {
            $product = $products[$item->product_id] ?? null;
            if (!$product) continue;

            $minQty = $product->min_order_qty ?? 1;
            $increment = $product->order_increment ?? 1;
            $currentQty = $item->quantity;
            
            // Check MOQ
            if ($currentQty < $minQty) {
                $violations[] = [
                    'type' => 'moq',
                    'item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_qty' => $currentQty,
                    'min_qty' => $minQty,
                    'corrected_qty' => $minQty,
                ];
                
                if ($autoFix) {
                    $item->update(['quantity' => $minQty]);
                }
                continue;
            }

            // Check increment (quantity should be in steps of order_increment from min_order_qty)
            $adjustedFromMin = $currentQty - $minQty;
            if ($increment > 1 && $adjustedFromMin % $increment !== 0) {
                // Round up to next valid increment
                $correctedQty = $minQty + (intdiv($adjustedFromMin, $increment) + 1) * $increment;
                
                $violations[] = [
                    'type' => 'increment',
                    'item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_qty' => $currentQty,
                    'increment' => $increment,
                    'corrected_qty' => $correctedQty,
                ];
                
                if ($autoFix) {
                    // Also recalculate price for new quantity
                    $priceInfo = $this->pricingService->getPrice($product, Auth::user(), $correctedQty);
                    $item->update([
                        'quantity' => $correctedQty,
                        'price_at_add' => $priceInfo['price'],
                    ]);
                }
            }
        }

        return $violations;
    }

    /**
     * Check if cart passes all MOQ requirements.
     * 
     * @return bool
     */
    public function hasValidMOQ(): bool
    {
        return empty($this->validateMOQ(false));
    }
}
