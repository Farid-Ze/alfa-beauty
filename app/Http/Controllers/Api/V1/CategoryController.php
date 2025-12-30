<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
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
