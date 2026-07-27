<?php

namespace Modules\Catalog\Observers;

use Modules\Catalog\Models\Category;

class CategoryObserver
{
    public function created(Category $category): void
    {
        \Illuminate\Support\Facades\Cache::tags(['categories'])->flush();
    }

    public function updated(Category $category): void
    {
        \Illuminate\Support\Facades\Cache::tags(['categories'])->flush();
    }

    public function deleted(Category $category): void
    {
        \Illuminate\Support\Facades\Cache::tags(['categories'])->flush();
    }
}
