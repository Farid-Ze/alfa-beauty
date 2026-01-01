<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Category API Controller
 *
 * Handles category listing operations.
 *
 * @package App\Http\Controllers\Api\V1
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     *
     * @param Request $request The HTTP request with query parameters
     * @return AnonymousResourceCollection Category collection
     *
     * @queryParam root_only boolean Filter only root categories (no parent).
     * @queryParam with_children boolean Include child categories.
     * @queryParam has_products boolean Filter categories with at least one product.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Category::query()->withCount('products');

        // Only root categories (no parent)
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // Include children
        if ($request->boolean('with_children')) {
            $query->with('children');
        }

        // Only categories with products
        if ($request->boolean('has_products')) {
            $query->has('products');
        }

        return CategoryResource::collection($query->get());
    }
}
