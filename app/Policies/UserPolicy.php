<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->create($user) && $user->id !== $model->id;
    }

    public function assignSites(User $user, User $model): bool
    {
        return $this->update($user, $model) && $model->isSupplier();
    }
}
