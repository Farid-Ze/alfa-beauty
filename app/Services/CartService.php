<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    protected ?Cart $cart = null;
    protected const COOKIE_NAME = 'cart_session_id';
    protected const COOKIE_LIFETIME = 60 * 24 * 30; // 30 days

    public function getCart(): ?Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        if (Auth::check()) {
            $this->cart = Cart::with('items.product.brand')
                ->where('user_id', Auth::id())
                ->latest()
                ->first();
        } else {
            $sessionId = Cookie::get(self::COOKIE_NAME);
            if ($sessionId) {
                $this->cart = Cart::with('items.product.brand')
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

    public function addItem(int $productId, int $quantity = 1): CartItem
    {
        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($productId);

        $existingItem = $cart->items()->where('product_id', $productId)->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem;
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'price_at_add' => $product->base_price, // Store price for change detection
        ]);
    }

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

        $item->update(['quantity' => $quantity]);
        return $item;
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

    public function getSubtotal(): float
    {
        $cart = $this->getCart();
        if (!$cart) return 0;

        return $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->base_price;
        });
    }

    /**
     * Validate that all cart items have sufficient stock.
     * 
     * @return array Array of stock errors, empty if all valid
     */
    public function validateStock(): array
    {
        $cart = $this->getCart();
        if (!$cart) return [];

        $errors = [];
        $inventoryService = app(InventoryService::class);

        foreach ($cart->items as $item) {
            // Refresh product to get latest stock
            $product = $item->product->fresh();
            
            // Check both global stock AND batch-level availability
            $globalStockOk = $item->quantity <= $product->stock;
            $batchStockOk = $inventoryService->hasAvailableStock($product->id, $item->quantity);
            
            if (!$globalStockOk || !$batchStockOk) {
                // Get batch availability for detailed message
                $batchInfo = $inventoryService->getAvailableBatches($product->id);
                
                $errors[] = [
                    'item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'requested' => $item->quantity,
                    'available' => min($product->stock, $batchInfo['total_available']),
                    'global_stock' => $product->stock,
                    'batch_stock' => $batchInfo['total_available'],
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
     * Refresh cart item prices from database.
     * 
     * Call this at checkout to ensure prices match current DB values.
     * Returns array of price changes if any occurred.
     * 
     * @return array Price changes: [['product_id' => x, 'old' => y, 'new' => z], ...]
     */
    public function refreshPrices(): array
    {
        $cart = $this->getCart();
        if (!$cart) return [];

        $changes = [];

        foreach ($cart->items as $item) {
            // Get fresh product price
            $currentPrice = $item->product->fresh()->base_price;
            
            // If item has stored price and it differs, log the change
            if (isset($item->price_at_add) && $item->price_at_add != $currentPrice) {
                $changes[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'old_price' => $item->price_at_add,
                    'new_price' => $currentPrice,
                    'difference' => $currentPrice - $item->price_at_add,
                ];
                
                // Update stored price
                $item->update(['price_at_add' => $currentPrice]);
            }
        }

        // Refresh cart items to reflect updated prices
        if (!empty($changes)) {
            $cart->load('items.product.brand');
        }

        return $changes;
    }

    /**
     * Get fresh subtotal using latest database prices.
     * 
     * @return float
     */
    public function getFreshSubtotal(): float
    {
        $cart = $this->getCart();
        if (!$cart) return 0;

        return $cart->items->sum(function ($item) {
            // Always use fresh product price
            return $item->quantity * $item->product->fresh()->base_price;
        });
    }
}
