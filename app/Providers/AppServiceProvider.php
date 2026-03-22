<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\SettingObserver;
use App\Services\Cache\CacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton cache service
        $this->app->singleton(CacheService::class, fn() => new CacheService());
    }

    public function boot(): void
    {
        // ─── Register Model Observers ─────────────────────────────
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Banner::observe(BannerObserver::class);
        Setting::observe(SettingObserver::class);
        Order::observe(OrderObserver::class);

        // ─── Rate Limiters ────────────────────────────────────────
        $this->configureRateLimiting();

        // ─── Strict DB queries in production ──────────────────────
        if ($this->app->isProduction()) {
            Model::preventLazyLoading();
        }
    }

    private function configureRateLimiting(): void
    {
        // General API: 60 requests/min per IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Auth endpoints: 10 attempts/min (brute force protection)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Checkout: 5 per minute per user/IP
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Search: 30 per minute
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
