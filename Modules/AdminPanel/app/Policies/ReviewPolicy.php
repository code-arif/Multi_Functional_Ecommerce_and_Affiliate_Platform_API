<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;
use Modules\Reviews\Models\Review;

class ReviewPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('reviews.view'); }
    public function moderate(User $user, Review $review): bool { return $user->isAdmin() || $user->hasPermissionTo('reviews.moderate'); }
    public function delete(User $user, Review $review): bool { return $user->isAdmin() || $user->hasPermissionTo('reviews.moderate'); }
}
