<?php

namespace Modules\Cart\Models;

use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class RecentView extends Model
{
    use HasUuid;
    protected $table = 'recent_views';

    protected $fillable = [
        'user_id',
        'product_id',
        'session_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
