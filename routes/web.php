<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', App\Livewire\HomePage::class)->name('home');
Route::get('/products', App\Livewire\ProductListPage::class)->name('products.index');
Route::get('/products/{slug}', App\Livewire\ProductDetailPage::class)->name('products.show');
Route::get('/brands/{slug}', App\Livewire\BrandDetail::class)->name('brands.show');

/*
|--------------------------------------------------------------------------
| Auth Routes (Rate Limited)
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:auth'])->group(function () {
    Route::get('/login', App\Livewire\LoginPage::class)->name('login');
    Route::get('/register', App\Livewire\RegisterPage::class)->name('register');
});

Route::get('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Email Verification Routes
|--------------------------------------------------------------------------
*/
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/')->with('message', __('auth.email_verified'));
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', __('auth.verification_sent'));
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated + Rate Limited)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'throttle:checkout'])->group(function () {
    Route::get('/checkout', App\Livewire\CheckoutPage::class)->name('checkout');
    Route::get('/checkout/success/{order}', \App\Livewire\OrderSuccess::class)->name('checkout.success');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/orders', \App\Livewire\MyOrders::class)->name('orders');
});

/*
|--------------------------------------------------------------------------
| Language Switch
|--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', function (string $locale) {
    if (!in_array($locale, ['id', 'en'])) {
        abort(404);
    }
    session(['locale' => $locale]);
    return redirect()->back()->withCookie(cookie('locale', $locale, 60 * 24 * 365)); // 1 year
})->name('lang.switch');

/*
|--------------------------------------------------------------------------
| SEO Routes
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::where('is_active', true)->get();
    $brands = \App\Models\Brand::all();
    $categories = \App\Models\Category::all();
    
    return response()->view('sitemap', compact('products', 'brands', 'categories'))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');
