<?php

namespace Modules\AdminPanel\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\AdminPanel\Policies\AffiliateProductPolicy;
use Modules\AdminPanel\Policies\BannerPolicy;
use Modules\AdminPanel\Policies\BrandPolicy;
use Modules\AdminPanel\Policies\CategoryPolicy;
use Modules\AdminPanel\Policies\CmsPagePolicy;
use Modules\AdminPanel\Policies\CouponPolicy;
use Modules\AdminPanel\Policies\DashboardPolicy;
use Modules\AdminPanel\Policies\ProductPolicy;
use Modules\AdminPanel\Policies\ReportPolicy;
use Modules\AdminPanel\Policies\ReviewPolicy;
use Modules\AdminPanel\Policies\SettingPolicy;
use Modules\AdminPanel\Policies\UserPolicy;
use App\Models\User;
use Modules\Product\Models\Product;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Brand;
use Modules\Orders\Models\Order;
use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Models\Banner;
use Modules\Reviews\Models\Review;
use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Cms\Models\CmsPage;
use Modules\AdminPanel\Models\Dispute;
use Modules\AdminPanel\Models\Setting;
use Modules\AdminPanel\Observers\SettingObserver;
use Modules\AdminPanel\Policies\DisputePolicy;
use Illuminate\Support\Facades\Gate;

class AdminPanelServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AdminPanel';
    protected string $nameLower = 'adminpanel';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Setting::observe(SettingObserver::class);
        $this->registerPolicies();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        // Order policy is registered by the Orders module (Modules\Orders\Providers\EventServiceProvider).
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(AffiliateProduct::class, AffiliateProductPolicy::class);
        Gate::define('viewDashboard', [DashboardPolicy::class, 'view']);
        Gate::define('viewReports', [ReportPolicy::class, 'view']);
        Gate::define('viewSalesReports', [ReportPolicy::class, 'viewSales']);
        Gate::define('viewFinancialReports', [ReportPolicy::class, 'viewFinancial']);
        Gate::define('viewSettings', [SettingPolicy::class, 'viewAny']);
        Gate::define('updateSettings', [SettingPolicy::class, 'update']);
        Gate::define('uploadSettingFile', [SettingPolicy::class, 'uploadFile']);
        Gate::policy(Dispute::class, DisputePolicy::class);
    }
}
