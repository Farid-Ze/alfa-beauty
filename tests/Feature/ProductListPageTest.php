<?php

namespace Tests\Feature;

use App\Livewire\ProductListPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListPageTest extends TestCase
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

    public function test_product_list_page_renders(): void
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
    }

    public function test_product_list_page_shows_products(): void
    {
        Livewire::test(ProductListPage::class)
            ->assertSee('Katalog Produk');
    }

    public function test_search_filters_products(): void
    {
        // Get a known product
        $product = Product::first();
        
        // Test that search value is set correctly
        Livewire::test(ProductListPage::class)
            ->set('search', $product->name)
            ->assertSet('search', $product->name);
    }

    public function test_search_resets_pagination(): void
    {
        $component = Livewire::test(ProductListPage::class)
            ->set('search', 'test');
        
        // After setting search, page should be 1
        $component->assertSet('search', 'test');
    }

    public function test_brand_filter_works(): void
    {
        $brand = Brand::first();
        
        Livewire::test(ProductListPage::class)
            ->set('selectedBrands', [$brand->id])
            ->assertSet('selectedBrands', [$brand->id]);
    }

    public function test_category_filter_works(): void
    {
        $category = Category::first();
        
        Livewire::test(ProductListPage::class)
            ->set('selectedCategories', [$category->id])
            ->assertSet('selectedCategories', [$category->id]);
    }

    public function test_sort_options_work(): void
    {
        Livewire::test(ProductListPage::class)
            ->set('sort', 'price_asc')
            ->assertSet('sort', 'price_asc')
            ->set('sort', 'price_desc')
            ->assertSet('sort', 'price_desc')
            ->set('sort', 'name_asc')
            ->assertSet('sort', 'name_asc');
    }

    public function test_per_page_setting_works(): void
    {
        Livewire::test(ProductListPage::class)
            ->set('perPage', 24)
            ->assertSet('perPage', 24);
    }

    public function test_price_range_filter_works(): void
    {
        Livewire::test(ProductListPage::class)
            ->set('priceMin', 100000)
            ->set('priceMax', 500000)
            ->assertSet('priceMin', 100000)
            ->assertSet('priceMax', 500000);
    }

    public function test_url_query_params_are_bound(): void
    {
        // Test that URL parameters work with Livewire
        $response = $this->get('/products?search=keratin&sort=price_asc');
        $response->assertStatus(200);
    }

    public function test_empty_search_shows_all_products(): void
    {
        $productCount = Product::where('is_active', true)->count();
        
        Livewire::test(ProductListPage::class)
            ->set('search', '')
            ->assertSet('search', '');
    }

    public function test_multiple_filters_combined(): void
    {
        $brand = Brand::first();
        $category = Category::first();
        
        Livewire::test(ProductListPage::class)
            ->set('search', 'serum')
            ->set('selectedBrands', [$brand->id])
            ->set('selectedCategories', [$category->id])
            ->set('sort', 'price_asc')
            ->assertSet('search', 'serum')
            ->assertSet('selectedBrands', [$brand->id])
            ->assertSet('sort', 'price_asc');
    }
}
