<?php

namespace Modules\Cms\Policies;

use Modules\Auth\Models\User;
use Modules\Cms\Models\CmsPage;

class CmsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('cms.view');
    }

    public function view(User $user, CmsPage $page): bool
    {
        return $user->hasPermission('cms.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('cms.create');
    }

    public function update(User $user, CmsPage $page): bool
    {
        return $user->hasPermission('cms.edit');
    }

    public function delete(User $user, CmsPage $page): bool
    {
        return $user->hasPermission('cms.delete');
    }

    public function manageBlocks(User $user): bool
    {
        return $user->hasPermission('cms.manage');
    }

    public function manageMenus(User $user): bool
    {
        return $user->hasPermission('cms.manage');
    }
}
