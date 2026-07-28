<?php

namespace App\Policies;

use App\Models\PmsHeader;
use App\Models\User;
use App\Services\UserDataScopeService;

class PmsPolicy
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pms.view');
    }

    public function view(User $user, PmsHeader $pmsHeader): bool
    {
        return $user->hasPermission('pms.view') && $this->canAccess($user, $pmsHeader);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pms.manage') && $user->isSupplier();
    }

    public function update(User $user, PmsHeader $pmsHeader): bool
    {
        return $user->hasPermission('pms.manage') && $this->canAccess($user, $pmsHeader);
    }

    public function finalize(User $user, PmsHeader $pmsHeader): bool
    {
        return $this->update($user, $pmsHeader) && $pmsHeader->isDraft();
    }

    /** @deprecated Use finalize() */
    public function submit(User $user, PmsHeader $pmsHeader): bool
    {
        return $this->finalize($user, $pmsHeader);
    }

    public function cancel(User $user, PmsHeader $pmsHeader): bool
    {
        return $this->update($user, $pmsHeader) && ! $pmsHeader->isCancelled();
    }

    public function revertToDraft(User $user, PmsHeader $pmsHeader): bool
    {
        return $user->hasPermission('pms.manage')
            && $user->isSupplier()
            && $this->canAccess($user, $pmsHeader)
            && $pmsHeader->isSubmitted();
    }

    protected function canAccess(User $user, PmsHeader $pmsHeader): bool
    {
        return $this->userDataScopeService->canAccessPmsHeader(
            $user,
            (int) $pmsHeader->supplier_id,
            (int) $pmsHeader->site_id,
        );
    }
}
