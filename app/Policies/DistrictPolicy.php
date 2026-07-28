<?php

namespace App\Policies;

use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('districts.view');
    }

    public function view(User $user, District $district): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('districts.manage');
    }

    public function update(User $user, District $district): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, District $district): bool
    {
        return $this->create($user);
    }
}
