<?php

namespace App\Services;

use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheInventory;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PmsScheduleReportService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @return array{0: int, 1: int}
     */
    public static function yearRange(): array
    {
        $year = (int) now()->year;

        return [$year - 5, $year + 1];
    }

    /**
     * @return array{
     *     districts: Collection<int, District>,
     *     sites: Collection<int, Site>,
     *     years: list<int>,
     *     months: array<int, string>
     * }
     */
    public function options(User $user): array
    {
        $sites = $this->accessibleSites($user);
        [$minYear, $maxYear] = self::yearRange();

        return [
            'districts' => $this->accessibleDistricts($user, $sites),
            'sites' => $sites,
            'years' => range($minYear, $maxYear),
            'months' => $this->monthOptions(),
        ];
    }

    /**
     * @param  array{district_id: int|null, site_id: int|null, year: int, month: int}  $filters
     * @return list<array{name: string, sites: list<array{name: string, suppliers: list<array{name: string, units: list<array<string, mixed>>}>}>}>
     */
    public function groups(User $user, array $filters): array
    {
        if (! $this->filtersAreInScope($user, $filters)) {
            return [];
        }

        $inventories = $this->inventories($user, $filters);

        if ($inventories->isEmpty()) {
            return [];
        }

        $pmsByUnit = $this->submittedPmsByUnit($user, $inventories);
        $start = Carbon::create($filters['year'], $filters['month'], 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        /** @var array<string, array<string, array<string, list<array<string, mixed>>>>> $tree */
        $tree = [];

        foreach ($inventories as $inventory) {
            $districtName = $inventory->siteRelation?->district?->district_name ?: '—';
            $siteName = $inventory->siteRelation?->site_name ?: ($inventory->site ?: '—');
            $supplierName = $inventory->supplier?->supplier_name ?: '—';
            $key = $this->unitKey((int) $inventory->site_id, (string) $inventory->unit_no);

            $tree[$districtName][$siteName][$supplierName][] = $this->unitRow(
                $inventory,
                $pmsByUnit[$key] ?? [],
                $start,
                $end,
            );
        }

        return $this->sortGroups($tree);
    }

    /**
     * @return Collection<int, Site>
     */
    protected function accessibleSites(User $user): Collection
    {
        $query = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSite($query, $user);

        return $query->get(['id', 'site_name', 'district_id']);
    }

    /**
     * @param  Collection<int, Site>  $sites
     * @return Collection<int, District>
     */
    protected function accessibleDistricts(User $user, Collection $sites): Collection
    {
        if ($user->isSuperAdmin()) {
            return District::query()->orderBy('district_name')->get(['id', 'district_name']);
        }

        $ids = $sites->pluck('district_id')->filter()->unique()->values();

        return District::query()
            ->whereIn('id', $ids)
            ->orderBy('district_name')
            ->get(['id', 'district_name']);
    }

    /**
     * @param  array{district_id: int|null, site_id: int|null, year: int, month: int}  $filters
     */
    protected function filtersAreInScope(User $user, array $filters): bool
    {
        if ($filters['site_id'] !== null && ! $this->userDataScopeService->canAccessSite($user, $filters['site_id'])) {
            return false;
        }

        if ($filters['district_id'] !== null) {
            $districtIds = $this->accessibleDistricts($user, $this->accessibleSites($user))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if (! in_array($filters['district_id'], $districtIds, true)) {
                return false;
            }
        }

        if ($filters['site_id'] !== null && $filters['district_id'] !== null) {
            $siteDistrictId = Site::query()->whereKey($filters['site_id'])->value('district_id');

            if ((int) $siteDistrictId !== $filters['district_id']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{district_id: int|null, site_id: int|null, year: int, month: int}  $filters
     * @return Collection<int, MheInventory>
     */
    protected function inventories(User $user, array $filters): Collection
    {
        $query = MheInventory::query()
            ->where('equipment_status', RecordStatus::Active)
            ->with(['supplier', 'siteRelation.district', 'mheType']);

        $this->userDataScopeService->scopeMheInventory($query, $user);

        if ($filters['district_id'] !== null) {
            $districtId = $filters['district_id'];
            $query->whereHas('siteRelation', fn ($siteQuery) => $siteQuery->where('district_id', $districtId));
        }

        if ($filters['site_id'] !== null) {
            $query->where('site_id', $filters['site_id']);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, MheInventory>  $inventories
     * @return array<string, list<PmsHeader>>
     */
    protected function submittedPmsByUnit(User $user, Collection $inventories): array
    {
        $siteIds = $inventories->pluck('site_id')->filter()->unique()->values();

        if ($siteIds->isEmpty()) {
            return [];
        }

        $query = PmsHeader::query()
            ->whereIn('site_id', $siteIds)
            ->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])
            ->whereNotNull('date_from')
            ->orderByDesc('date_from')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        $this->userDataScopeService->scopePmsHeader($query, $user);

        $byUnit = [];

        foreach ($query->get(['id', 'pms_no', 'site_id', 'unit_number', 'date_from', 'submitted_at']) as $pms) {
            $byUnit[$this->unitKey((int) $pms->site_id, (string) $pms->unit_number)][] = $pms;
        }

        return $byUnit;
    }

    /**
     * @param  list<PmsHeader>  $records
     * @return array<string, mixed>
     */
    protected function unitRow(MheInventory $inventory, array $records, Carbon $start, Carbon $end): array
    {
        $inMonth = null;

        foreach ($records as $pms) {
            if ($pms->date_from !== null && $pms->date_from->between($start, $end)) {
                $inMonth = $pms;
                break;
            }
        }

        $latest = $records[0] ?? null;

        return [
            'unit_no' => $inventory->unit_no ?: '—',
            'mhe_type' => $inventory->mheType?->code ?: '—',
            'serviced' => $inMonth !== null,
            'pms_date' => $inMonth?->date_from?->format('Y-m-d'),
            'pms_id' => $inMonth?->id,
            'pms_no' => $inMonth?->pms_no,
            'last_serviced' => $inMonth === null ? $latest?->date_from?->format('Y-m-d') : null,
            'next_service' => $inMonth === null ? $inventory->next_pms_date?->format('Y-m-d') : null,
        ];
    }

    /**
     * @param  array<string, array<string, array<string, list<array<string, mixed>>>>>  $tree
     * @return list<array{name: string, sites: list<array{name: string, suppliers: list<array{name: string, units: list<array<string, mixed>>}>}>}>
     */
    protected function sortGroups(array $tree): array
    {
        uksort($tree, 'strcasecmp');

        $groups = [];

        foreach ($tree as $districtName => $sites) {
            uksort($sites, 'strcasecmp');
            $siteGroups = [];

            foreach ($sites as $siteName => $suppliers) {
                uksort($suppliers, 'strcasecmp');
                $supplierGroups = [];

                foreach ($suppliers as $supplierName => $units) {
                    usort($units, fn (array $a, array $b): int => strcasecmp((string) $a['unit_no'], (string) $b['unit_no']));
                    $supplierGroups[] = [
                        'name' => $supplierName,
                        'units' => $units,
                    ];
                }

                $siteGroups[] = [
                    'name' => $siteName,
                    'suppliers' => $supplierGroups,
                ];
            }

            $groups[] = [
                'name' => $districtName,
                'sites' => $siteGroups,
            ];
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    protected function monthOptions(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = Carbon::create(2000, $month, 1)->format('F');
        }

        return $months;
    }

    protected function unitKey(int $siteId, string $unitNo): string
    {
        return $siteId.'|'.strtolower(trim($unitNo));
    }
}
