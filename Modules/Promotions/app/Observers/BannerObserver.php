<?php

namespace Modules\Promotions\Observers;

use Modules\Promotions\Models\Banner;

class BannerObserver
{
    public function created(Banner $banner): void
    {
        \Illuminate\Support\Facades\Cache::tags(['banners'])->flush();
    }

    public function updated(Banner $banner): void
    {
        \Illuminate\Support\Facades\Cache::tags(['banners'])->flush();
    }

    public function deleted(Banner $banner): void
    {
        \Illuminate\Support\Facades\Cache::tags(['banners'])->flush();
    }
}
