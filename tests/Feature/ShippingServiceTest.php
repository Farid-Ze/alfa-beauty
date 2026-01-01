<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\LoyaltyTier;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingService $shippingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        
        $this->shippingService = app(ShippingService::class);
    }

    public function test_shipping_zones_are_defined(): void
    {
        $zones = ShippingService::SHIPPING_ZONES;
        
        $this->assertArrayHasKey('jabodetabek', $zones);
        $this->assertArrayHasKey('jawa', $zones);
        $this->assertArrayHasKey('sumatera', $zones);
        $this->assertArrayHasKey('kalimantan', $zones);
        $this->assertArrayHasKey('sulawesi', $zones);
        $this->assertArrayHasKey('bali_nusa', $zones);
        $this->assertArrayHasKey('papua_maluku', $zones);
    }

    public function test_detect_jabodetabek_zone(): void
    {
        $addresses = [
            'Jl. Sudirman No. 123, Jakarta Pusat',
            'Komplek Ruko Bogor Trade Mall',
            'Apartemen Depok Indah',
            'Tangerang Selatan, BSD City',
            'Bekasi Timur, Jawa Barat',
        ];

        foreach ($addresses as $address) {
            $zone = $this->shippingService->detectZoneFromAddress($address);
            $this->assertEquals('jabodetabek', $zone, "Failed for address: $address");
        }
    }

    public function test_detect_jawa_zone(): void
    {
        $addresses = [
            'Jl. Braga No. 45, Bandung',
            'Semarang, Jawa Tengah',
            'Surabaya, Jawa Timur',
            'Yogyakarta, DIY',
        ];

        foreach ($addresses as $address) {
            $zone = $this->shippingService->detectZoneFromAddress($address);
            $this->assertEquals('jawa', $zone, "Failed for address: $address");
        }
    }

    public function test_detect_sumatera_zone(): void
    {
        $addresses = [
            'Medan, Sumatera Utara',
            'Palembang, Sumatera Selatan',
            'Pekanbaru, Riau',
        ];

        foreach ($addresses as $address) {
            $zone = $this->shippingService->detectZoneFromAddress($address);
            $this->assertEquals('sumatera', $zone, "Failed for address: $address");
        }
    }

    public function test_detect_kalimantan_zone(): void
    {
        $zone = $this->shippingService->detectZoneFromAddress('Balikpapan, Kalimantan Timur');
        $this->assertEquals('kalimantan', $zone);
    }

    public function test_detect_sulawesi_zone(): void
    {
        $zone = $this->shippingService->detectZoneFromAddress('Makassar, Sulawesi Selatan');
        $this->assertEquals('sulawesi', $zone);
    }

    public function test_detect_bali_zone(): void
    {
        $zone = $this->shippingService->detectZoneFromAddress('Denpasar, Bali');
        $this->assertEquals('bali_nusa', $zone);
    }

    public function test_detect_papua_zone(): void
    {
        $zone = $this->shippingService->detectZoneFromAddress('Jayapura, Papua');
        $this->assertEquals('papua_maluku', $zone);
    }

    public function test_unknown_address_defaults_to_jawa(): void
    {
        $zone = $this->shippingService->detectZoneFromAddress('Unknown Location XYZ');
        $this->assertEquals('jawa', $zone);
    }

    public function test_calculate_order_weight(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-001',
            'status' => 'pending',
            'subtotal' => $product->base_price * 2,
            'total_amount' => $product->base_price * 2,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Test Address',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->base_price,
            'total_price' => $product->base_price * 2,
        ]);

        $order->load('items.product');
        $weight = $this->shippingService->calculateOrderWeight($order);
        
        // Weight should be product weight * quantity
        $expectedWeight = ($product->volumetric_weight ?? $product->weight_grams ?? 0) * 2;
        $this->assertEquals($expectedWeight, $weight);
    }

    public function test_calculate_shipping_cost_jabodetabek(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-002',
            'status' => 'pending',
            'subtotal' => $product->base_price,
            'total_amount' => $product->base_price,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Jakarta',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->base_price,
            'total_price' => $product->base_price,
        ]);

        $order->load('items.product');
        $result = $this->shippingService->calculateShippingCost($order, 'jabodetabek');
        
        $this->assertEquals('jabodetabek', $result['zone']);
        $this->assertEquals('Jabodetabek', $result['zone_name']);
        $this->assertIsInt($result['weight_grams']);
        $this->assertArrayHasKey('total_cost', $result);
        $this->assertArrayHasKey('base_cost', $result);
    }

    public function test_free_shipping_for_gold_tier(): void
    {
        // Gold tier has free_shipping = true by default
        $goldTier = LoyaltyTier::where('slug', 'gold')->first();
        
        $user = User::factory()->create([
            'loyalty_tier_id' => $goldTier->id,
        ]);
        
        $product = Product::first();
        
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-003',
            'status' => 'pending',
            'subtotal' => $product->base_price,
            'total_amount' => $product->base_price,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Jakarta',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->base_price,
            'total_price' => $product->base_price,
        ]);

        $order->load('items.product', 'user.loyaltyTier');
        $result = $this->shippingService->calculateShippingCost($order, 'jabodetabek');
        
        $this->assertTrue($result['free_shipping']);
        $this->assertEquals(0, $result['total_cost']);
        $this->assertEquals('loyalty_tier', $result['free_shipping_reason']);
    }

    public function test_volumetric_divisor_constant(): void
    {
        $this->assertEquals(5000, ShippingService::VOLUMETRIC_DIVISOR);
    }
}
