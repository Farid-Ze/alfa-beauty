<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Brand API Controller
 *
 * Handles brand listing and detail operations.
 *
 * @package App\Http\Controllers\Api\V1
 */
class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     *
     * @param Request $request The HTTP request with query parameters
     * @return AnonymousResourceCollection Brand collection
     *
     * @queryParam featured boolean Filter featured brands only.
     * @queryParam has_products boolean Filter brands with at least one product.
     * @queryParam sort string Sort field. Default: sort_order.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Brand::query()->withCount('products');

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->whereRaw('is_featured = true');
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
     * Display the specified brand by slug.
     *
     * @param string $slug The brand slug
     * @return BrandResource The brand resource with product count
     *
     * @urlParam slug string required The brand slug. Example: alfaparf-milano
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When brand not found
     */
    public function show(string $slug): BrandResource
    {
        $brand = Brand::where('slug', $slug)
            ->withCount('products')
            ->firstOrFail();

        return new BrandResource($brand);
    }
}
