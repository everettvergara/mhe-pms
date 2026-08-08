<?php

namespace App\Policies;

use App\Models\MheInventory;
use App\Models\User;

class MheInventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-inventories.view');
    }

    public function view(User $user, MheInventory $mheInventory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-inventories.manage');
    }

    public function update(User $user, MheInventory $mheInventory): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MheInventory $mheInventory): bool
    {
        return $this->create($user);
    }
}
