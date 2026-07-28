<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;

class ChecklistItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('checklist-items.view');
    }

    public function view(User $user, ChecklistItem $checklistItem): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('checklist-items.manage');
    }

    public function update(User $user, ChecklistItem $checklistItem): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ChecklistItem $checklistItem): bool
    {
        return $this->create($user);
    }
}
