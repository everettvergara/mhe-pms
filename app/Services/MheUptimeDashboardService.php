<?php

namespace App\Services;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MheUptimeDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     groups: list<array{
     *         name: string,
     *         count: int,
     *         sites: list<array{
     *             name: string,
     *             count: int,
     *             suppliers: list<array{
     *                 name: string,
     *                 count: int,
     *                 units: list<array{
     *                     unit_no: string,
     *                     mhe_type_name: string,
     *                     available_hours: float,
     *                     hours_down: float,
     *                     uptime_hours: float,
     *                     uptime_pct: float,
     *                     status: string
     *                 }>
     *             }>
     *         }>
     *     }>
     * }
     */
    public function build(User $user, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();

        return [
            'groups' => $this->groupedUnits($user, $filters, $from, $to),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{name: string, count: int, sites: list<array<string, mixed>>}>
     */
    protected function groupedUnits(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $periodHours = $this->hoursInRange($from, $to);
        $downByInventory = [];
        $downByUnit = [];

        $downtimeRows = $this->downtimeBaseQuery($filters, $user)
            ->whereBetween('mhe_downtimes.date_of_incident', [$from, $to])
            ->select([
                'mhe_downtimes.mhe_inventory_id',
                'mhe_downtimes.site_id',
                'mhe_downtimes.ref_unit_no',
                'mhe_downtimes.hours_down',
            ])
            ->get();

        foreach ($downtimeRows as $row) {
            $hours = (float) $row->hours_down;

            if ($row->mhe_inventory_id) {
                $downByInventory[$row->mhe_inventory_id] = ($downByInventory[$row->mhe_inventory_id] ?? 0) + $hours;

                continue;
            }

            $unitNo = strtolower(trim((string) $row->ref_unit_no));

            if ($unitNo === '') {
                continue;
            }

            $key = $row->site_id.'|'.$unitNo;
            $downByUnit[$key] = ($downByUnit[$key] ?? 0) + $hours;
        }

        $units = $this->inventoryBaseQuery($filters, $user)
            ->join('sites as s', 's.id', '=', 'mhe_inventories.site_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('mhe_types as mt', 'mt.id', '=', 'mhe_inventories.mhe_type_id')
            ->leftJoin('suppliers as sup', 'sup.id', '=', 'mhe_inventories.supplier_id')
            ->select([
                'mhe_inventories.id',
                'mhe_inventories.site_id',
                'mhe_inventories.unit_no',
            ])
            ->selectRaw('s.site_name')
            ->selectRaw('COALESCE(d.district_name, ?) as district_name', ['Unassigned'])
            ->selectRaw('COALESCE(mt.description, ?) as mhe_type_name', ['Unassigned'])
            ->selectRaw('COALESCE(sup.supplier_name, ?) as supplier_name', ['Unassigned'])
            ->get();

        $tree = [];

        foreach ($units as $unit) {
            $unitKey = $unit->site_id.'|'.strtolower(trim((string) $unit->unit_no));
            $hoursDown = ($downByInventory[$unit->id] ?? 0) + ($downByUnit[$unitKey] ?? 0);
            $available = $periodHours;
            $uptimeHours = max(0, $available - $hoursDown);
            $uptimePct = $this->uptimePercent($hoursDown, $available);

            $tree[$unit->district_name][$unit->site_name][$unit->supplier_name][] = [
                'unit_no' => (string) $unit->unit_no,
                'mhe_type_name' => (string) $unit->mhe_type_name,
                'available_hours' => round($available, 2),
                'hours_down' => round($hoursDown, 2),
                'uptime_hours' => round($uptimeHours, 2),
                'uptime_pct' => round($uptimePct, 1),
                'status' => $this->statusLabel($uptimePct),
            ];
        }

        return $this->sortGroups($tree);
    }

    /**
     * @param  array<string, array<string, array<string, list<array<string, mixed>>>>>  $tree
     * @return list<array{name: string, count: int, sites: list<array<string, mixed>>}>
     */
    protected function sortGroups(array $tree): array
    {
        $districtNames = array_keys($tree);
        usort($districtNames, 'strcasecmp');

        $groups = [];

        foreach ($districtNames as $districtName) {
            $siteNames = array_keys($tree[$districtName]);
            usort($siteNames, 'strcasecmp');

            $sites = [];
            $districtCount = 0;

            foreach ($siteNames as $siteName) {
                $supplierNames = array_keys($tree[$districtName][$siteName]);
                usort($supplierNames, 'strcasecmp');

                $suppliers = [];
                $siteCount = 0;

                foreach ($supplierNames as $supplierName) {
                    $unitRows = $tree[$districtName][$siteName][$supplierName];
                    usort($unitRows, fn (array $a, array $b) => strcasecmp($a['unit_no'], $b['unit_no']));

                    $count = count($unitRows);
                    $siteCount += $count;
                    $suppliers[] = [
                        'name' => $supplierName,
                        'count' => $count,
                        'units' => $unitRows,
                    ];
                }

                $districtCount += $siteCount;
                $sites[] = [
                    'name' => $siteName,
                    'count' => $siteCount,
                    'suppliers' => $suppliers,
                ];
            }

            $groups[] = [
                'name' => $districtName,
                'count' => $districtCount,
                'sites' => $sites,
            ];
        }

        return $groups;
    }

    protected function hoursInRange(Carbon $from, Carbon $to): float
    {
        $days = (int) abs($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) + 1;

        return max(0, $days) * (int) config('mhe.default_daily_hours', 24);
    }

    protected function uptimePercent(float $hoursDown, float $availableHours): float
    {
        if ($availableHours <= 0) {
            return 0.0;
        }

        return max(0, 100 - ($hoursDown / $availableHours * 100));
    }

    protected function statusLabel(float $uptimePct): string
    {
        $target = (float) config('mhe.uptime_target_pct', 95);

        if ($uptimePct >= $target) {
            return 'On target';
        }

        if ($uptimePct >= $target - 5) {
            return 'At risk';
        }

        return 'Critical';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function downtimeBaseQuery(array $filters, User $user): Builder
    {
        $query = DB::table('mhe_downtimes')
            ->where('mhe_downtimes.status', DowntimeStatus::Posted->value)
            ->where('mhe_downtimes.w_spare_unit', false)
            ->whereNull('mhe_downtimes.deleted_at');

        $this->applyDimensionFilters($query, $filters, 'mhe_downtimes');
        $this->restrictToUserAccess($query, $user, 'mhe_downtimes');

        return $query;
    }

    /**
     * Active inventory units that form the 100% hours base.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function inventoryBaseQuery(array $filters, User $user): Builder
    {
        $query = DB::table('mhe_inventories')
            ->where('mhe_inventories.equipment_status', RecordStatus::Active->value)
            ->whereNotNull('mhe_inventories.site_id')
            ->whereNull('mhe_inventories.deleted_at');

        $this->applyDimensionFilters($query, $filters, 'mhe_inventories');
        $this->restrictToUserAccess($query, $user, 'mhe_inventories');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyDimensionFilters(Builder $query, array $filters, string $table): void
    {
        if (! empty($filters['site_id'])) {
            $query->where($table.'.site_id', $filters['site_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where($table.'.supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['district_id'])) {
            $siteIds = Site::query()->where('district_id', $filters['district_id'])->pluck('id');
            $query->whereIn($table.'.site_id', $siteIds);
        }
    }

    /**
     * Supplier users see their supplier and sites. FAST administrators see every
     * supplier at their assigned sites. Super admins see every supplier and site.
     */
    protected function restrictToUserAccess(Builder $query, User $user, string $table): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $query->whereIn($table.'.site_id', $user->assignedSiteIds());

        if ($user->isFastAdmin()) {
            return;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn($table.'.supplier_id', $supplierIds);
    }
}
