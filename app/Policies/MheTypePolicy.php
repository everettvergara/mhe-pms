<?php

namespace App\Policies;

use App\Models\MheType;
use App\Models\User;

class MheTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-types.view');
    }

    public function view(User $user, MheType $mheType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-types.manage');
    }

    public function update(User $user, MheType $mheType): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MheType $mheType): bool
    {
        return $this->create($user);
    }
}
