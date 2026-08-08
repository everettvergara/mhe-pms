<?php

namespace App\Policies;

use App\Models\MheCategory;
use App\Models\User;

class MheCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-categories.view');
    }

    public function view(User $user, MheCategory $mheCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-categories.manage');
    }

    public function update(User $user, MheCategory $mheCategory): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MheCategory $mheCategory): bool
    {
        return $this->create($user);
    }
}
