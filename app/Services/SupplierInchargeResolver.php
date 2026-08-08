<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\MheDowntime;
use App\Models\PmsHeader;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class SupplierInchargeResolver
{
    /**
     * @return Collection<int, User>
     */
    public function resolve(MheDowntime $downtime): Collection
    {
        return $this->resolveForSiteAndSupplier($downtime->site_id, $this->resolveSupplierId($downtime));
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveFromPmsHeader(PmsHeader $pmsHeader): Collection
    {
        return $this->resolveForSiteAndSupplier($pmsHeader->site_id, $pmsHeader->supplier_id);
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveForSiteAndSupplier(?int $siteId, ?int $supplierId): Collection
    {
        if ($siteId === null || $supplierId === null) {
            return collect();
        }

        return User::query()
            ->where('status', UserStatus::Active)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::SLUG_SUPPLIER_USER))
            ->whereHas('sites', fn ($query) => $query->where('sites.id', $siteId))
            ->where(function ($query) use ($supplierId): void {
                $query->whereHas('suppliers', fn ($supplierQuery) => $supplierQuery->where('suppliers.id', $supplierId))
                    ->orWhere('supplier_id', $supplierId);
            })
            ->get();
    }

    protected function resolveSupplierId(MheDowntime $downtime): ?int
    {
        if ($downtime->supplier_id !== null) {
            return (int) $downtime->supplier_id;
        }

        $downtime->loadMissing('mheInventory');

        return $downtime->mheInventory?->supplier_id;
    }
}
