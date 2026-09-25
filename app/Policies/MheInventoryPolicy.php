<?php

namespace App\Policies;

use App\Models\MheInventory;
use App\Models\User;
use App\Services\UserDataScopeService;

class MheInventoryPolicy
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-inventories.view');
    }

    public function view(User $user, MheInventory $mheInventory): bool
    {
        return $this->viewAny($user) && $this->canAccess($user, $mheInventory);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-inventories.manage');
    }

    public function update(User $user, MheInventory $mheInventory): bool
    {
        return $this->create($user) && $this->canAccess($user, $mheInventory);
    }

    public function delete(User $user, MheInventory $mheInventory): bool
    {
        return $this->create($user) && $this->canAccess($user, $mheInventory);
    }

    protected function canAccess(User $user, MheInventory $mheInventory): bool
    {
        return $this->userDataScopeService->canAccessMheInventory(
            $user,
            (int) $mheInventory->site_id,
            $mheInventory->supplier_id !== null ? (int) $mheInventory->supplier_id : null,
        );
    }
}
