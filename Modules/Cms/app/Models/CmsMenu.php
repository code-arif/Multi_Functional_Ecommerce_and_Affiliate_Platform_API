<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class CmsMenu extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'items',
        'is_active',
    ];

    protected $casts = [
        'items'     => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Get flattened menu items for easy rendering.
     */
    public function getFlattenedItemsAttribute(): array
    {
        $result = [];
        $this->flattenItems($this->items ?? [], $result);
        return $result;
    }

    private function flattenItems(array $items, array &$result, int $depth = 0): void
    {
        foreach ($items as $item) {
            $item['depth'] = $depth;
            $result[] = $item;
            if (!empty($item['children'])) {
                $this->flattenItems($item['children'], $result, $depth + 1);
            }
        }
    }
}
