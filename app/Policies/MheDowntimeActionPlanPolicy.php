<?php

namespace App\Policies;

use App\Models\MheDowntimeActionPlan;
use App\Models\User;
use App\Services\UserDataScopeService;

class MheDowntimeActionPlanPolicy
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('mhe-downtimes.view');
    }

    public function view(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->viewAny($user)
            && $this->userDataScopeService->canAccessMheDowntime(
                $user,
                (int) $actionPlan->mheDowntime->site_id,
                $actionPlan->mheDowntime->supplier_id,
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('mhe-downtimes.manage') && $user->isSupplier();
    }

    public function update(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $user->hasPermission('mhe-downtimes.manage')
            && $user->isSupplier()
            && $this->userDataScopeService->canAccessMheDowntime(
                $user,
                (int) $actionPlan->mheDowntime->site_id,
                $actionPlan->mheDowntime->supplier_id,
            );
    }

    public function comment(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function markImplemented(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function cancel(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function delete(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function confirm(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $user->hasPermission('action-plans.confirm')
            && $user->isFastAdmin()
            && $actionPlan->mheDowntime->isPosted();
    }

    public function reject(User $user, MheDowntimeActionPlan $actionPlan): bool
    {
        return $this->confirm($user, $actionPlan);
    }
}
