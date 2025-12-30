<?php

namespace Tests\Feature;

use App\Models\BatchInventory;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        
        $this->inventoryService = app(InventoryService::class);
        
        // Create a product for testing
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'stock' => 100,
            'base_price' => 50000,
        ]);
    }

    /**
     * Create test batches for the product
     */
    protected function createTestBatches(): void
    {
        // Batch 1: Near expiry (should be picked first in FEFO)
        BatchInventory::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-001',
            'lot_number' => 'LOT-001',
            'quantity_received' => 30,
            'quantity_available' => 30,
            'quantity_sold' => 0,
            'quantity_damaged' => 0,
            'manufactured_at' => Carbon::now()->subMonths(6),
            'expires_at' => Carbon::now()->addDays(30), // Near expiry
            'received_at' => Carbon::now()->subMonths(5),
            'cost_price' => 25000,
            'is_active' => true,
            'is_near_expiry' => true,
            'is_expired' => false,
        ]);

        // Batch 2: Later expiry
        BatchInventory::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-002',
            'lot_number' => 'LOT-002',
            'quantity_received' => 50,
            'quantity_available' => 50,
            'quantity_sold' => 0,
            'quantity_damaged' => 0,
            'manufactured_at' => Carbon::now()->subMonths(3),
            'expires_at' => Carbon::now()->addMonths(6),
            'received_at' => Carbon::now()->subMonths(2),
            'cost_price' => 26000,
            'is_active' => true,
            'is_near_expiry' => false,
            'is_expired' => false,
        ]);

        // Batch 3: Latest expiry
        BatchInventory::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-003',
            'lot_number' => 'LOT-003',
            'quantity_received' => 20,
            'quantity_available' => 20,
            'quantity_sold' => 0,
            'quantity_damaged' => 0,
            'manufactured_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addYear(),
            'received_at' => Carbon::now()->subWeek(),
            'cost_price' => 27000,
            'is_active' => true,
            'is_near_expiry' => false,
            'is_expired' => false,
        ]);

        // Update product stock to match batch totals
        $this->product->update(['stock' => 100]);
    }

    public function test_fefo_allocates_from_near_expiry_first(): void
    {
        $this->createTestBatches();

        // Allocate 20 units - should come from BATCH-001 (near expiry)
        $allocations = $this->inventoryService->allocateStock($this->product->id, 20);

        $this->assertCount(1, $allocations);
        $this->assertEquals('BATCH-001', $allocations[0]['batch_number']);
        $this->assertEquals(20, $allocations[0]['quantity']);
        $this->assertTrue($allocations[0]['is_near_expiry']);

        // Verify batch was updated
        $batch = BatchInventory::where('batch_number', 'BATCH-001')->first();
        $this->assertEquals(10, $batch->quantity_available);
        $this->assertEquals(20, $batch->quantity_sold);
    }

    public function test_fefo_spans_multiple_batches_when_needed(): void
    {
        $this->createTestBatches();

        // Allocate 50 units - should span BATCH-001 (30) and BATCH-002 (20)
        $allocations = $this->inventoryService->allocateStock($this->product->id, 50);

        $this->assertCount(2, $allocations);
        
        // First allocation from near-expiry batch
        $this->assertEquals('BATCH-001', $allocations[0]['batch_number']);
        $this->assertEquals(30, $allocations[0]['quantity']);
        
        // Second allocation from next expiring batch
        $this->assertEquals('BATCH-002', $allocations[1]['batch_number']);
        $this->assertEquals(20, $allocations[1]['quantity']);

        // Verify product stock updated
        $this->product->refresh();
        $this->assertEquals(50, $this->product->stock);
    }

    public function test_allocation_fails_with_insufficient_stock(): void
    {
        $this->createTestBatches();

        $this->expectException(\Exception::class);

        // Try to allocate more than available
        $this->inventoryService->allocateStock($this->product->id, 150);
    }

    public function test_release_stock_returns_to_batches(): void
    {
        $this->createTestBatches();

        // First allocate
        $allocations = $this->inventoryService->allocateStock($this->product->id, 25);

        // Verify allocation
        $batch = BatchInventory::where('batch_number', 'BATCH-001')->first();
        $this->assertEquals(5, $batch->quantity_available);

        // Now release
        $this->inventoryService->releaseStock($allocations, 'Test release');

        // Verify release
        $batch->refresh();
        $this->assertEquals(30, $batch->quantity_available);
        $this->assertEquals(0, $batch->quantity_sold);

        // Verify product stock restored
        $this->product->refresh();
        $this->assertEquals(100, $this->product->stock);
    }

    public function test_batch_deactivates_when_depleted(): void
    {
        $this->createTestBatches();

        // Allocate all of BATCH-001
        $this->inventoryService->allocateStock($this->product->id, 30);

        $batch = BatchInventory::where('batch_number', 'BATCH-001')->first();
        $this->assertEquals(0, $batch->quantity_available);
        $this->assertFalse($batch->is_active); // Should be deactivated
    }

    public function test_has_available_stock_check(): void
    {
        $this->createTestBatches();

        $this->assertTrue($this->inventoryService->hasAvailableStock($this->product->id, 50));
        $this->assertTrue($this->inventoryService->hasAvailableStock($this->product->id, 100));
        $this->assertFalse($this->inventoryService->hasAvailableStock($this->product->id, 150));
    }

    public function test_get_available_batches_with_preview(): void
    {
        $this->createTestBatches();

        $result = $this->inventoryService->getAvailableBatches($this->product->id, 50);

        $this->assertEquals(100, $result['total_available']);
        $this->assertCount(3, $result['batches']);
        $this->assertTrue($result['can_fulfill']);
        $this->assertCount(2, $result['allocation_preview']);
    }

    public function test_order_creation_uses_fefo(): void
    {
        $this->createTestBatches();

        // Create user and cart
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 25,
        ]);
        $cart->load('items.product');

        // Create order through OrderService
        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => 'Test notes',
        ], $user->id);

        // Verify order item has batch allocations
        $orderItem = $order->items->first();
        $this->assertNotNull($orderItem->batch_allocations);
        $this->assertIsArray($orderItem->batch_allocations);
        $this->assertEquals('BATCH-001', $orderItem->batch_allocations[0]['batch_number']);
    }

    public function test_sync_stock_with_batches(): void
    {
        $this->createTestBatches();

        // Manually desync product stock
        $this->product->update(['stock' => 50]); // Wrong value

        // Sync
        $results = $this->inventoryService->syncStockWithBatches($this->product->id);

        $this->assertCount(1, $results);
        $this->assertEquals($this->product->id, $results[0]['product_id']);
        $this->assertEquals(50, $results[0]['old_stock']);
        $this->assertEquals(100, $results[0]['new_stock']);

        // Verify product stock corrected
        $this->product->refresh();
        $this->assertEquals(100, $this->product->stock);
    }
}
