<?php

namespace Modules\AdminPanel\Observers;

use Modules\AdminPanel\Models\Setting;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        \Illuminate\Support\Facades\Cache::tags(['settings'])->flush();
    }

    public function deleted(Setting $setting): void
    {
        \Illuminate\Support\Facades\Cache::tags(['settings'])->flush();
    }
}
