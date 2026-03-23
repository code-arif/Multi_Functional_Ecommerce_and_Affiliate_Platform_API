<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug()
    {
        static::creating(function ($model) {
            $model->generateSlug();
        });

        static::updating(function ($model) {
            // Optional: name change হলে slug update হবে কিনা
            if ($model->isDirty($model->getSlugSourceColumn())) {
                $model->generateSlug();
            }
        });
    }

    protected function generateSlug(): void
    {
        $sourceColumn = $this->getSlugSourceColumn();
        $slugColumn   = $this->getSlugColumn();

        $slug = Str::slug($this->$sourceColumn);

        $originalSlug = $slug;
        $count = 1;

        while (
            static::withTrashed()
                ->where($slugColumn, $slug)
                ->when($this->id, function ($query) {
                    $query->where('id', '!=', $this->id);
                })
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $count++;
        }

        $this->$slugColumn = $slug;
    }

    protected function getSlugSourceColumn(): string
    {
        return property_exists($this, 'slugSourceColumn')
            ? $this->slugSourceColumn
            : 'name';
    }

    protected function getSlugColumn(): string
    {
        return property_exists($this, 'slugColumn')
            ? $this->slugColumn
            : 'slug';
    }
}
