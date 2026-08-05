<?php

namespace Modules\Reviews\Models;

use Modules\Reviews\Models\Review;
use App\Models\User;
use Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewHelpfulVote extends Model
{
    use HasUuid;

    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
