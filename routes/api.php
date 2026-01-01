<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Middleware\ApiVersion;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->name('api.v1.')->middleware(['throttle:api', ApiVersion::class])->group(function () {
    // Public endpoints
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('brands/{slug}', [BrandController::class, 'show'])->name('brands.show');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');

    // Protected endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // User profile
        Route::get('user', [UserController::class, 'show'])->name('user.show');
        Route::put('user', [UserController::class, 'update'])->name('user.update');
        
        // Orders
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });

    // Health check endpoints
    Route::get('health', [HealthController::class, 'basic'])->name('health');
    Route::get('health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');
});
