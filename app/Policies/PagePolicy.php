<?php

namespace App\Policies;

use App\Models\Cms\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Page $page): bool
    {
        return $user->is_admin || $page->isPublished();
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Page $page): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->is_admin;
    }
}
