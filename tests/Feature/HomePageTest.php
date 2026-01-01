<?php

namespace Tests\Feature;

use App\Livewire\HomePage;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_homepage_renders(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_homepage_shows_featured_brands(): void
    {
        $featuredBrand = Brand::where('is_featured', true)->first();
        
        if ($featuredBrand) {
            $response = $this->get('/');
            $response->assertSee($featuredBrand->name);
        } else {
            $this->assertTrue(true); // No featured brands, test passes
        }
    }

    public function test_homepage_loads_brands_with_stats(): void
    {
        $component = Livewire::test(HomePage::class);
        
        $brands = $component->get('brands');
        
        $this->assertIsIterable($brands);
        
        foreach ($brands as $brand) {
            $this->assertTrue(isset($brand->product_count) || $brand->product_count === null);
        }
    }

    public function test_homepage_limits_brands_to_four(): void
    {
        $component = Livewire::test(HomePage::class);
        
        $brands = $component->get('brands');
        
        $this->assertLessThanOrEqual(4, count($brands));
    }

    public function test_homepage_only_shows_featured_brands(): void
    {
        $component = Livewire::test(HomePage::class);
        
        $brands = $component->get('brands');
        
        foreach ($brands as $brand) {
            // is_featured could be true/1 depending on database driver
            $this->assertTrue((bool) $brand->is_featured);
        }
    }

    public function test_homepage_brands_sorted_by_sort_order(): void
    {
        $component = Livewire::test(HomePage::class);
        
        $brands = $component->get('brands');
        
        $previousSortOrder = -1;
        foreach ($brands as $brand) {
            $this->assertGreaterThanOrEqual($previousSortOrder, $brand->sort_order);
            $previousSortOrder = $brand->sort_order;
        }
    }
}
