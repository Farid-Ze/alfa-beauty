<?php

namespace App\Livewire;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class HomePage extends Component
{
    public $brands;
    
    // Contact form fields
    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactSubject = '';
    public string $contactPhone = '';
    public string $contactMessage = '';

    /**
     * Cache TTL for featured brands (10 minutes)
     */
    private const BRANDS_CACHE_TTL = 600;

    public function mount()
    {
        // OPTIMIZED: Single query with withCount and subquery for stock sum
        // Eliminates N+1 problem (was 8+ queries, now 1 query)
        // Added caching to reduce database load on homepage
        $this->brands = Cache::remember('homepage_featured_brands', self::BRANDS_CACHE_TTL, function () {
            // IMPORTANT: Use whereRaw for boolean columns in PostgreSQL with EMULATE_PREPARES
            // Laravel binds booleans as integers (1/0) but PostgreSQL requires true/false
            return Brand::whereRaw('is_featured = true')
                ->orderBy('sort_order')
                ->take(4)
                ->withCount(['products as product_count' => function ($query) {
                    $query->whereRaw('is_active = true');
                }])
                ->addSelect([
                    'total_stock' => DB::table('products')
                        ->selectRaw('COALESCE(SUM(stock), 0)')
                        ->whereColumn('brand_id', 'brands.id')
                        ->whereRaw('is_active = true')
                ])
                ->get();
        });
    }

    public function render()
    {
        return view('livewire.home-page');
    }
    
    /**
     * Handle contact form submission.
     * 
     * For MVP: Log the contact and show success message.
     * Production: Send email notification to admin.
     */
    public function submitContact(): void
    {
        $this->validate([
            'contactEmail' => 'required|email',
            'contactName' => 'nullable|string|max:255',
            'contactSubject' => 'nullable|string|max:255',
            'contactPhone' => 'nullable|string|max:20',
            'contactMessage' => 'nullable|string|max:2000',
        ]);
        
        // Log for now, email integration can be added later
        Log::channel('single')->info('Contact form submission', [
            'name' => $this->contactName,
            'email' => $this->contactEmail,
            'subject' => $this->contactSubject,
            'phone' => $this->contactPhone,
            'message' => $this->contactMessage,
        ]);
        
        // Reset form
        $this->reset(['contactName', 'contactEmail', 'contactSubject', 'contactPhone', 'contactMessage']);
        
        session()->flash('contact_success', __('home.contact_success'));
    }
}
