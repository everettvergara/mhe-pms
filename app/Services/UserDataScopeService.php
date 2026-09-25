<?php

namespace App\Services;

use App\Models\MheDowntime;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserDataScopeService
{
    public function applies(User $user): bool
    {
        return ! $user->isSuperAdmin();
    }

    /**
     * @param  Builder<Model>  $query
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

        $query->whereIn('site_id', $user->assignedSiteIds());
    }

    /**
     * @param  Builder<Model>  $query
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
     * @param  Builder<Model>  $query
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
     * @param  Builder<Model>  $query
     */
    public function scopeSite(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $query->whereIn('id', $user->assignedSiteIds());
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

        return $siteIds !== [] && in_array($siteId, $siteIds, true);
    }

    public function canAccessSite(User $user, int $siteId): bool
    {
        if (! $this->applies($user)) {
            return true;
        }

        $siteIds = $user->assignedSiteIds();

        return $siteIds !== [] && in_array($siteId, $siteIds, true);
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function scopeMheDowntime(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            $query->whereIn('supplier_id', $supplierIds);
        }

        $query->whereIn('site_id', $user->assignedSiteIds());
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function scopeMheDowntimeActionPlan(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        $query->whereHas('mheDowntime', function (Builder $downtimeQuery) use ($user): void {
            $this->scopeMheDowntime($downtimeQuery, $user);
        });
    }

    public function canAccessMheDowntime(User $user, int $siteId, ?int $supplierId = null): bool
    {
        if (! $this->applies($user)) {
            return true;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            if ($supplierId === null || ! in_array($supplierId, $supplierIds, true)) {
                return false;
            }
        }

        $siteIds = $user->assignedSiteIds();

        return $siteIds !== [] && in_array($siteId, $siteIds, true);
    }

    public function canSupplierEditMheDowntime(User $user, MheDowntime $downtime): bool
    {
        if (! $user->isSupplier()) {
            return true;
        }

        if (! $this->canAccessMheDowntime($user, (int) $downtime->site_id, $downtime->supplier_id)) {
            return false;
        }

        $downtime->loadMissing('creator');

        return ! ($downtime->creator?->isFastAdmin() ?? false);
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function scopeMheInventory(Builder $query, User $user): void
    {
        if (! $this->applies($user)) {
            return;
        }

        if ($user->isSupplier() && $user->assignedSupplierIds() === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            $query->whereIn('supplier_id', $supplierIds);
        }

        $query->whereIn('site_id', $user->assignedSiteIds());
    }

    public function canAccessMheInventory(User $user, int $siteId, ?int $supplierId = null): bool
    {
        if (! $this->applies($user)) {
            return true;
        }

        if ($user->isSupplier() && $user->assignedSupplierIds() === []) {
            return false;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            if ($supplierId === null || ! in_array($supplierId, $supplierIds, true)) {
                return false;
            }
        }

        $siteIds = $user->assignedSiteIds();

        return $siteIds !== [] && in_array($siteId, $siteIds, true);
    }
}
