<?php

namespace Tests\Feature;

use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        
        $this->taxService = app(TaxService::class);
    }

    public function test_calculate_item_tax_exclusive(): void
    {
        $result = $this->taxService->calculateItemTax(
            unitPrice: 100000,
            quantity: 2,
            taxRate: 11.0,
            isTaxInclusive: false
        );

        $this->assertEquals(100000, $result['unit_price_before_tax']);
        $this->assertEquals(200000, $result['subtotal_before_tax']);
        $this->assertEquals(11.0, $result['tax_rate']);
        $this->assertEquals(22000, $result['tax_amount']); // 200000 * 11%
        $this->assertEquals(222000, $result['line_total']);
    }

    public function test_calculate_item_tax_inclusive(): void
    {
        $result = $this->taxService->calculateItemTax(
            unitPrice: 111000, // Price includes 11% tax
            quantity: 1,
            taxRate: 11.0,
            isTaxInclusive: true
        );

        $this->assertEquals(100000, round($result['unit_price_before_tax']));
        $this->assertEquals(100000, round($result['subtotal_before_tax']));
        $this->assertEquals(11.0, $result['tax_rate']);
        $this->assertEquals(11000, round($result['tax_amount']));
        $this->assertEquals(111000, $result['line_total']);
    }

    public function test_calculate_item_tax_with_zero_rate(): void
    {
        $result = $this->taxService->calculateItemTax(
            unitPrice: 100000,
            quantity: 3,
            taxRate: TaxService::TAX_EXEMPT,
            isTaxInclusive: false
        );

        $this->assertEquals(300000, $result['subtotal_before_tax']);
        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(300000, $result['line_total']);
    }

    public function test_default_tax_rate_is_11_percent(): void
    {
        $this->assertEquals(11.0, TaxService::DEFAULT_TAX_RATE);
    }

    public function test_calculate_item_tax_with_single_quantity(): void
    {
        $result = $this->taxService->calculateItemTax(
            unitPrice: 50000,
            quantity: 1,
            taxRate: 11.0,
            isTaxInclusive: false
        );

        $this->assertEquals(50000, $result['subtotal_before_tax']);
        $this->assertEquals(5500, $result['tax_amount']);
        $this->assertEquals(55500, $result['line_total']);
    }

    public function test_tax_amounts_are_rounded_to_two_decimals(): void
    {
        // 33333 * 11% = 3666.63
        $result = $this->taxService->calculateItemTax(
            unitPrice: 33333,
            quantity: 1,
            taxRate: 11.0,
            isTaxInclusive: false
        );

        $this->assertEquals(3666.63, $result['tax_amount']);
        $this->assertEquals(36999.63, $result['line_total']);
    }
}
