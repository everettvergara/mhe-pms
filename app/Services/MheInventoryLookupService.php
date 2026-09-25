<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\MheInventory;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MheInventoryLookupService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @return array<int, array{id: int, site_name: string, site_code: string, label: string}>
     */
    public function searchSites(User $user, ?string $term = null): array
    {
        $query = Site::query()
            ->where('status', RecordStatus::Active)
            ->when($term !== null && $term !== '', function (Builder $q) use ($term): void {
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('site_name', 'like', '%'.$term.'%')
                        ->orWhere('site_code', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('site_name');

        $this->userDataScopeService->scopeSite($query, $user);

        return $query->get(['id', 'site_name', 'site_code'])->map(fn (Site $site) => [
            'id' => $site->id,
            'site_name' => $site->site_name,
            'site_code' => $site->site_code,
            'label' => $site->site_name.' ('.$site->site_code.')',
        ])->all();
    }

    /**
     * @return array{matched: bool, id: int|null, label: string|null}
     */
    public function lookupSite(User $user, string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return ['matched' => false, 'id' => null, 'label' => null];
        }

        $query = Site::query()
            ->where('status', RecordStatus::Active);

        $this->userDataScopeService->scopeSite($query, $user);

        $termLower = strtolower($term);

        foreach ($query->get(['id', 'site_name', 'site_code']) as $site) {
            $label = $site->site_name.' ('.$site->site_code.')';

            if (strtolower($label) === $termLower
                || strtolower($site->site_name) === $termLower
                || strtolower($site->site_code) === $termLower) {
                return [
                    'matched' => true,
                    'id' => $site->id,
                    'label' => $label,
                ];
            }
        }

        return ['matched' => false, 'id' => null, 'label' => null];
    }

    /**
     * @return array<int, array{unit_no: string|null, supplier_id: int|null, supplier_name: string, mhe_type_id: int|null, mhe_type_label: string, label: string}>
     */
    public function searchUnits(User $user, int $siteId, ?int $mheTypeId = null, ?string $term = null, ?int $limit = 20): array
    {
        if (! $this->userDataScopeService->canAccessSite($user, $siteId)) {
            return [];
        }

        $query = $this->scopedInventoryQuery($user)
            ->with([
                'mheType:id,code,description',
                'supplier:id,supplier_name',
            ])
            ->where('equipment_status', RecordStatus::Active)
            ->where('site_id', $siteId)
            ->when($mheTypeId, fn (Builder $q) => $q->where('mhe_type_id', $mheTypeId))
            ->when($term !== null && $term !== '', fn (Builder $q) => $q->where('unit_no', 'like', '%'.$term.'%'))
            ->orderBy('unit_no')
            ->when($limit !== null, fn (Builder $q) => $q->limit($limit));

        return $query->get(['id', 'unit_no', 'supplier_id', 'mhe_type_id'])->map(function (MheInventory $inventory): array {
            $typeLabel = $inventory->mheType
                ? $inventory->mheType->code.' — '.$inventory->mheType->description
                : '';
            $supplierName = trim((string) ($inventory->supplier?->supplier_name ?? ''));

            return [
                'unit_no' => $inventory->unit_no,
                'supplier_id' => $inventory->supplier_id,
                'supplier_name' => $supplierName,
                'mhe_type_id' => $inventory->mhe_type_id,
                'mhe_type_label' => $typeLabel,
                'label' => $inventory->unit_no.' ('.($typeLabel !== '' ? $typeLabel : '—').')',
            ];
        })->all();
    }

    /**
     * @return array{matched: bool, unit_no: string|null, supplier_id: int|null, mhe_type_id: int|null}
     */
    public function lookupUnit(User $user, int $siteId, string $unitNo): array
    {
        if ($siteId === 0 || trim($unitNo) === '') {
            return ['matched' => false, 'unit_no' => null, 'supplier_id' => null, 'mhe_type_id' => null];
        }

        if (! $this->userDataScopeService->canAccessSite($user, $siteId)) {
            return ['matched' => false, 'unit_no' => null, 'supplier_id' => null, 'mhe_type_id' => null];
        }

        $inventory = $this->findScopedInventory($user, $siteId, $unitNo, activeOnly: true);

        if ($inventory === null) {
            return ['matched' => false, 'unit_no' => null, 'supplier_id' => null, 'mhe_type_id' => null];
        }

        return [
            'matched' => true,
            'unit_no' => $inventory->unit_no,
            'supplier_id' => $inventory->supplier_id,
            'mhe_type_id' => $inventory->mhe_type_id,
        ];
    }

    public function findScopedInventory(User $user, int $siteId, string $unitNo, bool $activeOnly = false): ?MheInventory
    {
        $unit = trim($unitNo);

        if ($unit === '' || ! $this->userDataScopeService->canAccessSite($user, $siteId)) {
            return null;
        }

        $query = $this->scopedInventoryQuery($user)
            ->where('site_id', $siteId)
            ->whereRaw('LOWER(unit_no) = ?', [strtolower($unit)]);

        if ($activeOnly) {
            $query->where('equipment_status', RecordStatus::Active);
        }

        return $query->first();
    }

    /**
     * @return Builder<MheInventory>
     */
    protected function scopedInventoryQuery(User $user): Builder
    {
        $query = MheInventory::query();
        $this->userDataScopeService->scopeMheInventory($query, $user);

        return $query;
    }
}
