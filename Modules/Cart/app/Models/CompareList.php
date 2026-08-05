<?php

namespace Modules\Cart\Models;

use \Modules\Product\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasUuid;

class CompareList extends Model
{
    use HasUuid;
    protected $fillable = [
        'user_id',
        'session_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompareListItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'compare_list_items'
        )->withTimestamps();
    }

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }
}
