<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

/**
 * Adds a public `uuid` key alongside the internal auto-increment `id`.
 *
 * - Auto-generates the uuid on model `creating`.
 * - Makes route model binding resolve by `uuid` (getRouteKeyName()).
 * - Provides findByUuid() / findByUuidOrFail() helpers.
 *
 * Use with an additive migration that adds a unique `uuid` column to the table.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getUuidColumn()})) {
                $model->{$model->getUuidColumn()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Column that stores the public uuid.
     */
    public function getUuidColumn(): string
    {
        return 'uuid';
    }

    /**
     * Route model binding + resource URLs use the uuid, never the integer id.
     */
    public function getRouteKeyName(): string
    {
        return $this->getUuidColumn();
    }

    /**
     * Resolve a model by its public uuid.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where((new static)->getUuidColumn(), $uuid)->first();
    }

    /**
     * Resolve a model by its public uuid or throw ModelNotFoundException.
     */
    public static function findByUuidOrFail(string $uuid): static
    {
        return static::where((new static)->getUuidColumn(), $uuid)->firstOrFail();
    }

    /**
     * Scope a query by uuid.
     */
    public function scopeByUuid($query, string $uuid)
    {
        return $query->where($this->getUuidColumn(), $uuid);
    }
}
