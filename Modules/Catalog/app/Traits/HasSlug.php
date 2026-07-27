<?php

namespace Modules\Catalog\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name ?? $model->title ?? '');
            }
        });
    }
}
