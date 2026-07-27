<?php

namespace Modules\Cms\Providers;

use Modules\Cms\Models\CmsPage;
use Modules\Cms\Policies\CmsPolicy;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $policies = [
        CmsPage::class => CmsPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
