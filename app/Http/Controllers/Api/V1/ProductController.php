<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Product API Controller
 *
 * Handles product listing and detail operations.
 *
 * @package App\Http\Controllers\Api\V1
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products with filtering and pagination.
     *
     * @param Request $request The HTTP request with query parameters
     * @return AnonymousResourceCollection Paginated product collection
     *
     * @queryParam search string Filter by product name, SKU, or description.
     * @queryParam brand_id integer Filter by brand ID.
     * @queryParam category_id integer Filter by category ID.
     * @queryParam featured boolean Filter featured products only.
     * @queryParam in_stock boolean Filter products with stock > 0.
     * @queryParam min_price number Filter by minimum price.
     * @queryParam max_price number Filter by maximum price.
     * @queryParam sort string Sort field: name, base_price, created_at, stock. Default: created_at.
     * @queryParam direction string Sort direction: asc, desc. Default: desc.
     * @queryParam per_page integer Items per page (max 100). Default: 15.
     */
    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        
        $query = Product::query()
            ->with(['brand', 'category', 'priceTiers'])
            ->where('is_active', true);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Filter by stock availability
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        // Price range
        if ($request->has('min_price')) {
            $query->where('base_price', '>=', $request->get('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('base_price', '<=', $request->get('max_price'));
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'base_price', 'created_at', 'stock'];
        
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);

        return ProductResource::collection($query->paginate($perPage));
    }

    /**
     * Display the specified product by slug.
     *
     * @param string $slug The product slug
     * @return ProductResource The product resource with full details
     *
     * @urlParam slug string required The product slug. Example: alfaparf-color-wear-5-1
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When product not found
     */
    public function show(string $slug): ProductResource
    {
        $product = Product::with(['brand', 'category', 'priceTiers'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
