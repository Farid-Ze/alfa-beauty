<?php

namespace Tests\Feature;

use App\Livewire\BrandDetail;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_brand_detail_page_renders(): void
    {
        $brand = Brand::first();
        
        $response = $this->get('/brands/' . $brand->slug);
        $response->assertStatus(200);
    }

    public function test_brand_detail_shows_brand_name(): void
    {
        $brand = Brand::first();
        
        Livewire::test(BrandDetail::class, ['slug' => $brand->slug])
            ->assertSee($brand->name);
    }

    public function test_brand_detail_shows_product_count(): void
    {
        $brand = Brand::first();
        $productCount = Product::where('brand_id', $brand->id)
            ->where('is_active', true)
            ->count();
        
        $component = Livewire::test(BrandDetail::class, ['slug' => $brand->slug]);
        
        $this->assertEquals($productCount, $component->get('productCount'));
    }

    public function test_brand_detail_shows_total_stock(): void
    {
        $brand = Brand::first();
        
        $component = Livewire::test(BrandDetail::class, ['slug' => $brand->slug]);
        
        $this->assertIsNumeric($component->get('totalStock'));
    }

    public function test_brand_detail_has_featured_product(): void
    {
        $brand = Brand::first();
        
        // Ensure there's at least one product for this brand
        Product::where('brand_id', $brand->id)->update(['is_active' => true]);
        
        $component = Livewire::test(BrandDetail::class, ['slug' => $brand->slug]);
        
        // Featured product may or may not exist
        $featuredProduct = $component->get('featuredProduct');
        if (Product::where('brand_id', $brand->id)->exists()) {
            $this->assertNotNull($featuredProduct);
        }
    }

    public function test_brand_detail_shows_other_brands(): void
    {
        $brand = Brand::first();
        
        $component = Livewire::test(BrandDetail::class, ['slug' => $brand->slug]);
        
        $otherBrands = $component->get('otherBrands');
        $this->assertIsIterable($otherBrands);
        
        // Other brands should not include current brand
        foreach ($otherBrands as $otherBrand) {
            $this->assertNotEquals($brand->id, $otherBrand->id);
        }
    }

    public function test_invalid_brand_slug_returns_404(): void
    {
        $response = $this->get('/brands/non-existent-brand-slug');
        $response->assertStatus(404);
    }

    public function test_brand_detail_page_title_includes_brand_name(): void
    {
        $brand = Brand::first();
        
        $response = $this->get('/brands/' . $brand->slug);
        $response->assertSee($brand->name);
    }
}
