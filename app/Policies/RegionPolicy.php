<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('regions.view');
    }

    public function view(User $user, Region $region): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('regions.manage');
    }

    public function update(User $user, Region $region): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Region $region): bool
    {
        return $this->create($user);
    }
}
