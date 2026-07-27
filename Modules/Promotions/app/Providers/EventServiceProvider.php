<?php

namespace Modules\Promotions\Providers;

use Modules\Promotions\Models\Promotion;
use Modules\Promotions\Policies\PromotionPolicy;
use Modules\Promotions\Observers\BannerObserver;
use Modules\Promotions\Models\Banner;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $policies = [
        Promotion::class => PromotionPolicy::class,
    ];

    protected $listen = [
        // Future: PromotionApplied::class => [NotifyUser::class],
    ];

    protected $observers = [
        Banner::class => [BannerObserver::class],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
