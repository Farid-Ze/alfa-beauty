<?php

namespace App\Providers;

use App\Contracts\CartServiceInterface;
use App\Contracts\InventoryServiceInterface;
use App\Contracts\OrderServiceInterface;
use App\Contracts\PricingServiceInterface;
use App\Contracts\ReturnServiceInterface;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\ReturnService;
use App\Models\CustomerPriceList;
use App\Models\LoyaltyTier;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\User;
use App\Observers\PricingRuleAuditObserver;
use App\Observers\PricingSourceAuditObserver;
use App\Observers\UserAuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Service container bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PricingServiceInterface::class => PricingService::class,
        CartServiceInterface::class => CartService::class,
        OrderServiceInterface::class => OrderService::class,
        InventoryServiceInterface::class => InventoryService::class,
        ReturnServiceInterface::class => ReturnService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind service interfaces to implementations
        foreach ($this->bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production (Vercel runs behind proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
        $this->configureQueryLogging();
        $this->configureQueueFailedHandler();

        CustomerPriceList::observe(PricingRuleAuditObserver::class);
        ProductPriceTier::observe(PricingRuleAuditObserver::class);

        Product::observe(new PricingSourceAuditObserver(['base_price']));
        LoyaltyTier::observe(new PricingSourceAuditObserver(['discount_percent', 'point_multiplier', 'min_spend', 'free_shipping']));

        User::observe(UserAuditObserver::class);
    }

    /**
     * Configure database query logging for slow query detection.
     */
    protected function configureQueryLogging(): void
    {
        // Only log slow queries in production (threshold: 1000ms)
        if (!$this->app->environment('production')) {
            return;
        }

        DB::listen(function ($query) {
            $slowQueryThreshold = 1000; // milliseconds
            
            if ($query->time > $slowQueryThreshold) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Configure queue failed job handler.
     */
    protected function configureQueueFailedHandler(): void
    {
        Event::listen(JobFailed::class, \App\Listeners\LogFailedJobs::class);
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Login/Register: 5 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Checkout: 10 attempts per minute per user
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // WhatsApp redirect: 5 per minute to prevent abuse
        RateLimiter::for('whatsapp', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // API: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // General web requests: 120 per minute
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
