<?php

namespace Modules\Reviews\Models;

use Modules\Reviews\Models\ReviewHelpfulVote;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'title',
        'body',
        'images',
        'is_verified_purchase',
        'status',
        'admin_response',
        'vendor_response',
        'vendor_responded_at',
        'helpful_count',
    ];

    protected $casts = [
        'rating'               => 'integer',
        'images'               => 'array',
        'is_verified_purchase' => 'boolean',
        'vendor_responded_at'  => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeWithImages($query)
    {
        return $query->whereNotNull('images')->where('images', '!=', '[]');
    }

    public function scopeHasVendorResponse($query)
    {
        return $query->whereNotNull('vendor_response');
    }

    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    public function scopeNewest($query)
    {
        return $query->latest();
    }

    public function scopeOldest($query)
    {
        return $query->oldest();
    }

    public function scopeHighestRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    public function scopeLowestRated($query)
    {
        return $query->orderBy('rating', 'asc');
    }

    // ─── Accessors ─────────────────────────────────────────────────

    /**
     * Check if the current authenticated user found this review helpful.
     */
    public function getIsHelpfulAttribute(): bool
    {
        if (!auth()->check()) return false;

        return $this->relationLoaded('helpfulVotes')
            ? $this->helpfulVotes->contains('user_id', auth()->id())
            : $this->helpfulVotes()->where('user_id', auth()->id())->exists();
    }

    public function getHasVendorResponseAttribute(): bool
    {
        return !is_null($this->vendor_response);
    }

    /**
     * Get image URLs (full paths).
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) return [];

        return array_map(function ($img) {
            return str_starts_with($img, 'http') ? $img : asset('storage/' . $img);
        }, (array) $this->images);
    }

    // ─── Helpers ───────────────────────────────────────────────────

    /**
     * Mark this review as verified purchase based on order completion.
     */
    public function markAsVerifiedPurchase(): void
    {
        $this->update(['is_verified_purchase' => true]);
    }

    /**
     * Recalculate the helpful_count from actual vote records.
     */
    public function recalculateHelpfulCount(): void
    {
        $this->update([
            'helpful_count' => $this->helpfulVotes()->count(),
        ]);
    }
}
