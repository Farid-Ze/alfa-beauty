<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\CustomerOrderSetting;

/**
 * ShippingService
 * 
 * Handles shipping cost calculations based on weight and dimensions.
 * Supports volumetric weight calculation for domestic shipping.
 */
class ShippingService
{
    /**
     * Shipping zones and base rates (Rp per kg)
     * These are placeholder rates - integrate with actual courier APIs
     */
    public const SHIPPING_ZONES = [
        'jabodetabek' => [
            'name' => 'Jabodetabek',
            'base_rate' => 15000,
            'weight_rate' => 5000, // per additional kg
            'min_weight' => 1, // kg
        ],
        'jawa' => [
            'name' => 'Jawa',
            'base_rate' => 20000,
            'weight_rate' => 7000,
            'min_weight' => 1,
        ],
        'sumatera' => [
            'name' => 'Sumatera',
            'base_rate' => 35000,
            'weight_rate' => 12000,
            'min_weight' => 1,
        ],
        'kalimantan' => [
            'name' => 'Kalimantan',
            'base_rate' => 40000,
            'weight_rate' => 15000,
            'min_weight' => 1,
        ],
        'sulawesi' => [
            'name' => 'Sulawesi',
            'base_rate' => 45000,
            'weight_rate' => 15000,
            'min_weight' => 1,
        ],
        'bali_nusa' => [
            'name' => 'Bali & Nusa Tenggara',
            'base_rate' => 35000,
            'weight_rate' => 12000,
            'min_weight' => 1,
        ],
        'papua_maluku' => [
            'name' => 'Papua & Maluku',
            'base_rate' => 65000,
            'weight_rate' => 25000,
            'min_weight' => 1,
        ],
    ];

    /**
     * Volumetric divisor for domestic shipping
     */
    public const VOLUMETRIC_DIVISOR = 5000;

    /**
     * Calculate shipping cost for an order.
     */
    public function calculateShippingCost(Order $order, string $zone = 'jabodetabek'): array
    {
        $totalWeight = $this->calculateOrderWeight($order);
        $zoneConfig = self::SHIPPING_ZONES[$zone] ?? self::SHIPPING_ZONES['jabodetabek'];
        
        // Convert grams to kg (round up)
        $weightKg = ceil($totalWeight / 1000);
        $weightKg = max($weightKg, $zoneConfig['min_weight']);
        
        // Calculate base + additional weight cost
        $baseCost = $zoneConfig['base_rate'];
        $additionalWeight = max(0, $weightKg - $zoneConfig['min_weight']);
        $additionalCost = $additionalWeight * $zoneConfig['weight_rate'];
        
        $totalCost = $baseCost + $additionalCost;
        
        // Check for free shipping eligibility
        $freeShipping = $this->checkFreeShippingEligibility($order);
        
        return [
            'weight_grams' => $totalWeight,
            'weight_kg' => $weightKg,
            'zone' => $zone,
            'zone_name' => $zoneConfig['name'],
            'base_cost' => $baseCost,
            'additional_cost' => $additionalCost,
            'total_cost' => $freeShipping['eligible'] ? 0 : $totalCost,
            'original_cost' => $totalCost,
            'free_shipping' => $freeShipping['eligible'],
            'free_shipping_reason' => $freeShipping['reason'] ?? null,
        ];
    }

    /**
     * Calculate total weight for an order (in grams).
     * Uses volumetric weight if larger than actual weight.
     */
    public function calculateOrderWeight(Order $order): int
    {
        $totalWeight = 0;
        
        foreach ($order->items as $item) {
            $product = $item->product;
            
            if (!$product) {
                continue;
            }
            
            // Use weight_grams from product, fallback to 0 if not set
            // volumetric_weight is a computed property that may not exist
            /** @phpstan-ignore property.notFound */
            $itemWeight = ($product->volumetric_weight ?? $product->weight_grams ?? 0) * $item->quantity;
            $totalWeight += $itemWeight;
        }
        
        return $totalWeight;
    }

    /**
     * Calculate weight for a cart/array of items.
     */
    public function calculateItemsWeight(array $items): int
    {
        $totalWeight = 0;
        
        foreach ($items as $item) {
            $product = $item['product'] ?? Product::find($item['product_id'] ?? null);
            $quantity = $item['quantity'] ?? 1;
            
            if ($product) {
                $totalWeight += $product->volumetric_weight * $quantity;
            }
        }
        
        return $totalWeight;
    }

    /**
     * Check if order/user qualifies for free shipping.
     */
    public function checkFreeShippingEligibility(Order $order): array
    {
        $user = $order->user;
        
        if (!$user) {
            return ['eligible' => false];
        }
        
        // Check customer order settings
        $settings = CustomerOrderSetting::where('user_id', $user->id)->first();
        
        if ($settings && $settings->qualifiesForFreeShipping($order->subtotal ?? 0)) {
            return [
                'eligible' => true,
                'reason' => 'customer_setting',
            ];
        }
        
        // Check loyalty tier
        if ($user->loyaltyTier && $user->loyaltyTier->free_shipping) {
            return [
                'eligible' => true,
                'reason' => 'loyalty_tier',
            ];
        }
        
        return ['eligible' => false];
    }

    /**
     * Detect shipping zone from address.
     * This is a simplified implementation - use actual geo/postal code mapping in production.
     */
    public function detectZoneFromAddress(string $address): string
    {
        $address = strtolower($address);
        
        $zoneKeywords = [
            'jabodetabek' => ['jakarta', 'bogor', 'depok', 'tangerang', 'bekasi', 'jkt'],
            'jawa' => ['bandung', 'semarang', 'surabaya', 'yogya', 'jogja', 'solo', 'malang', 'cirebon', 'jawa'],
            'sumatera' => ['medan', 'palembang', 'padang', 'pekanbaru', 'lampung', 'aceh', 'sumatera', 'sumatra'],
            'kalimantan' => ['pontianak', 'banjarmasin', 'balikpapan', 'samarinda', 'kalimantan', 'borneo'],
            'sulawesi' => ['makassar', 'manado', 'palu', 'kendari', 'sulawesi', 'celebes'],
            'bali_nusa' => ['bali', 'denpasar', 'mataram', 'lombok', 'kupang', 'flores', 'nusa tenggara'],
            'papua_maluku' => ['jayapura', 'sorong', 'ambon', 'ternate', 'papua', 'maluku', 'irian'],
        ];
        
        foreach ($zoneKeywords as $zone => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($address, $keyword)) {
                    return $zone;
                }
            }
        }
        
        // Default to Java if can't detect
        return 'jawa';
    }

    /**
     * Apply shipping cost to order.
     */
    public function applyShippingToOrder(Order $order, ?string $zone = null): Order
    {
        if (!$zone && $order->shipping_address) {
            $zone = $this->detectZoneFromAddress($order->shipping_address);
        }
        
        $shippingData = $this->calculateShippingCost($order, $zone ?? 'jabodetabek');
        
        $order->shipping_cost = $shippingData['total_cost'];
        $order->shipping_method = $shippingData['zone_name'];
        $order->save();
        
        return $order;
    }

    /**
     * Get available shipping options for an order.
     */
    public function getShippingOptions(Order $order): array
    {
        $options = [];
        
        foreach (self::SHIPPING_ZONES as $zone => $config) {
            $options[$zone] = $this->calculateShippingCost($order, $zone);
        }
        
        return $options;
    }
}
