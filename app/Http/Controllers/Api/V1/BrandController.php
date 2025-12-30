<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Brand::query()->withCount('products');

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Only brands with products
        if ($request->boolean('has_products')) {
            $query->has('products');
        }

        // Sorting
        $query->orderBy($request->get('sort', 'sort_order'), 'asc');

        return BrandResource::collection($query->get());
    }

    /**
     * Display the specified brand.
     */
    public function show(string $slug): BrandResource
    {
        $brand = Brand::where('slug', $slug)
            ->withCount('products')
            ->firstOrFail();

        return new BrandResource($brand);
    }
}
