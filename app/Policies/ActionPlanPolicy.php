<?php

namespace App\Policies;

use App\Models\ActionPlan;
use App\Models\User;
use App\Services\UserDataScopeService;

class ActionPlanPolicy
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('action-plans.view');
    }

    public function view(User $user, ActionPlan $actionPlan): bool
    {
        return $user->hasPermission('action-plans.view') && $this->canAccess($user, $actionPlan);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('action-plans.manage') && $user->isSupplier();
    }

    public function update(User $user, ActionPlan $actionPlan): bool
    {
        return $user->hasPermission('action-plans.manage') && $this->canAccess($user, $actionPlan);
    }

    public function comment(User $user, ActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function markImplemented(User $user, ActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function cancel(User $user, ActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }

    public function delete(User $user, ActionPlan $actionPlan): bool
    {
        if (! $user->hasPermission('action-plans.manage') || ! $user->isSupplier()) {
            return false;
        }

        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        return $this->canAccess($user, $actionPlan)
            && $actionPlan->pmsDetail?->pmsHeader?->isDraft() === true;
    }

    public function confirm(User $user, ActionPlan $actionPlan): bool
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        return $user->hasPermission('action-plans.confirm')
            && $user->isFastAdmin()
            && $actionPlan->pmsDetail?->pmsHeader?->isSubmitted() === true;
    }

    public function reject(User $user, ActionPlan $actionPlan): bool
    {
        return $this->confirm($user, $actionPlan);
    }

    protected function canAccess(User $user, ActionPlan $actionPlan): bool
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null) {
            return false;
        }

        return $this->userDataScopeService->canAccessPmsHeader(
            $user,
            (int) $pmsHeader->supplier_id,
            (int) $pmsHeader->site_id,
        );
    }
}
