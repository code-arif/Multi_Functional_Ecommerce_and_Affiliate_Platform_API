<?php

namespace Modules\Cart\Services;

use Modules\Cart\Models\RecentView;
use App\Models\User;
use Modules\Product\Models\Product;

class RecentlyViewedService
{
    /**
     * Track a product view.
     */
    public function track(Product $product, ?User $user = null, ?string $sessionId = null): void
    {
        // Don't track if no identifier
        if (!$user && !$sessionId) {
            return;
        }

        // Remove old entry for this user/product combo
        RecentView::when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $product->id)
            ->delete();

        // Create new entry
        RecentView::create([
            'user_id'    => $user?->id,
            'product_id' => $product->id,
            'session_id' => !$user ? $sessionId : null,
        ]);

        // Enforce max stored views (keep only latest N)
        $this->prune($user, $sessionId);
    }

    /**
     * Get recently viewed products.
     */
    public function getRecent(?User $user = null, ?string $sessionId = null, int $limit = 10)
    {
        return RecentView::with('product.primaryImage')
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->latest()
            ->limit($limit)
            ->get()
            ->pluck('product');
    }

    /**
     * Merge guest session views into user account on login.
     */
    public function mergeSessionIntoUser(string $sessionId, User $user): void
    {
        RecentView::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->each(function (RecentView $view) use ($user) {
                // Skip if user already has a view for this product
                $existing = RecentView::where('user_id', $user->id)
                    ->where('product_id', $view->product_id)
                    ->exists();

                if (!$existing) {
                    $view->update(['user_id' => $user->id, 'session_id' => null]);
                } else {
                    // User already has this product — delete the session one
                    $view->delete();
                }
            });
    }

    /**
     * Keep only the latest N entries per user/session.
     */
    private function prune(?User $user, ?string $sessionId, int $max = 50): void
    {
        $query = RecentView::when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId));

        $count = $query->count();
        if ($count > $max) {
            $ids = $query->orderBy('created_at', 'asc')
                ->limit($count - $max)
                ->pluck('id');
            RecentView::whereIn('id', $ids)->delete();
        }
    }
}
