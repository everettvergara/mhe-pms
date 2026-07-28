<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserDataScopeService
{
    public function applies(User $user): bool
    {
        return ! $user->isSuperAdmin();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function scopePmsHeader(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            $query->whereIn('supplier_id', $supplierIds);
        }

        $siteIds = $user->assignedSiteIds();

        if ($siteIds !== []) {
            $query->whereIn('site_id', $siteIds);
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function scopeActionPlan(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $query->whereHas('pmsDetail.pmsHeader', function (Builder $headerQuery) use ($user): void {
            $this->scopePmsHeader($headerQuery, $user);
        });
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function scopeSupplier(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            $query->whereIn('id', $supplierIds);
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function scopeSite(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $siteIds = $user->assignedSiteIds();

        if ($siteIds !== []) {
            $query->whereIn('id', $siteIds);
        }
    }

    public function canAccessPmsHeader(User $user, int $supplierId, int $siteId): bool
    {
        if (! $this->applies($user)) {
            return true;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== [] && ! in_array($supplierId, $supplierIds, true)) {
            return false;
        }

        $siteIds = $user->assignedSiteIds();

        return $siteIds === [] || in_array($siteId, $siteIds, true);
    }
}
