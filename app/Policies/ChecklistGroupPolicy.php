<?php

namespace App\Policies;

use App\Models\ChecklistGroup;
use App\Models\User;

class ChecklistGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('checklist-groups.view');
    }

    public function view(User $user, ChecklistGroup $checklistGroup): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('checklist-groups.manage');
    }

    public function update(User $user, ChecklistGroup $checklistGroup): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ChecklistGroup $checklistGroup): bool
    {
        return $this->create($user);
    }
}
