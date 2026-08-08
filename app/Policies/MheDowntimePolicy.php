<?php

namespace App\Policies;

use App\Models\MheDowntime;
use App\Models\User;
use App\Services\UserDataScopeService;

class MheDowntimePolicy
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-downtimes.view');
    }

    public function view(User $user, MheDowntime $mheDowntime): bool
    {
        return $this->viewAny($user) && $this->canAccess($user, $mheDowntime);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-downtimes.manage');
    }

    public function update(User $user, MheDowntime $mheDowntime): bool
    {
        if (! $user->hasPermission('mhe-downtimes.manage') || ! $this->canAccess($user, $mheDowntime)) {
            return false;
        }

        if ($user->isSupplier()) {
            if ($mheDowntime->isPosted()) {
                return false;
            }

            return $this->userDataScopeService->canSupplierEditMheDowntime($user, $mheDowntime);
        }

        return true;
    }

    public function post(User $user, MheDowntime $mheDowntime): bool
    {
        return $user->hasPermission('mhe-downtimes.post')
            && $this->canAccess($user, $mheDowntime)
            && $mheDowntime->isDraft()
            && $this->userDataScopeService->canSupplierEditMheDowntime($user, $mheDowntime);
    }

    public function cancel(User $user, MheDowntime $mheDowntime): bool
    {
        return $this->update($user, $mheDowntime) && ! $mheDowntime->isCancelled();
    }

    public function revertToDraft(User $user, MheDowntime $mheDowntime): bool
    {
        if ($user->isSupplier()) {
            return false;
        }

        return $this->update($user, $mheDowntime)
            && ($mheDowntime->isPosted() || $mheDowntime->isCancelled());
    }

    public function delete(User $user, MheDowntime $mheDowntime): bool
    {
        return $this->update($user, $mheDowntime)
            && $mheDowntime->isDraft();
    }

    protected function canAccess(User $user, MheDowntime $mheDowntime): bool
    {
        return $this->userDataScopeService->canAccessMheDowntime(
            $user,
            (int) $mheDowntime->site_id,
            $mheDowntime->supplier_id,
        );
    }
}
