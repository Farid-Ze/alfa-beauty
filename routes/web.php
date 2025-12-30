<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', App\Livewire\HomePage::class)->name('home');
Route::get('/products', App\Livewire\ProductListPage::class)->name('products.index');
Route::get('/products/{slug}', App\Livewire\ProductDetailPage::class)->name('products.show');
Route::get('/brands/{slug}', App\Livewire\BrandDetail::class)->name('brands.show');
Route::get('/checkout', App\Livewire\CheckoutPage::class)->name('checkout');
Route::get('/orders', \App\Livewire\MyOrders::class)->middleware('auth')->name('orders');
Route::get('/checkout/success/{order}', \App\Livewire\OrderSuccess::class)->name('checkout.success');
Route::get('/register', App\Livewire\RegisterPage::class)->name('register');
Route::get('/login', App\Livewire\LoginPage::class)->name('login');
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// FIX DATABASE ROUTE
Route::get('/fix-db', function() {
    try {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION int_to_bool(int) RETURNS boolean AS $$
            BEGIN
                IF $1 = 0 THEN
                    RETURN false;
                ELSE
                    RETURN true;
                END IF;
            END;
            $$ LANGUAGE plpgsql IMMUTABLE;
        ");
        
        try {
            DB::unprepared("DROP CAST IF EXISTS (integer AS boolean)");
        } catch (\Exception $e) {}

        DB::unprepared("CREATE CAST (integer AS boolean) WITH FUNCTION int_to_bool(int) AS IMPLICIT");
        
        return "Database fix applied successfully! You can now check the homepage.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

// Language Switch
Route::get('/lang/{locale}', function (string $locale) {
    if (!in_array($locale, ['id', 'en'])) {
        abort(404);
    }
    session(['locale' => $locale]);
    return redirect()->back()->withCookie(cookie('locale', $locale, 60 * 24 * 365)); // 1 year
})->name('lang.switch');
