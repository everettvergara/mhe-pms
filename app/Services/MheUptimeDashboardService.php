<?php

namespace App\Services;

use App\Enums\DowntimeStatus;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MheUptimeDashboardService
{
    public function __construct(
        protected MheMonthlyCapacityService $capacityService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     kpis: array<string, float|int|string>,
     *     barChart: array{labels: list<string>, values: list<float>, target: float},
     *     pieSupplier: array{labels: list<string>, values: list<float>},
     *     pieMheType: array{labels: list<string>, values: list<float>},
     *     details: Collection<int, object>
     * }
     */
    public function build(User $user, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();
        $grain = $filters['grain'] ?? 'monthly';

        $this->capacityService->ensureSnapshotsForRange($from, $to);

        $availableHours = $this->capacityService->availableHoursForDateRange(
            $from,
            $to,
            fn (Builder $q) => $this->applyCapacityFilters($q, $filters, $user),
        );

        $hoursDown = (float) $this->downtimeBaseQuery($filters, $user)
            ->whereBetween('date_of_incident', [$from, $to])
            ->sum('hours_down');

        $unitQuery = DB::table('mhe_monthly_capacities')->whereIn('yyyymm', $this->capacityService->monthsInRange($from, $to));
        $this->applyCapacityFilters($unitQuery, $filters, $user);
        $unitCount = (int) $unitQuery->distinct()->count('mhe_inventory_id');

        $uptimePct = $this->uptimePercent($hoursDown, $availableHours);
        $target = (float) config('mhe.uptime_target_pct', 95);

        return [
            'kpis' => [
                'uptime_pct' => round($uptimePct, 1),
                'hours_down' => round($hoursDown, 2),
                'hours_available' => round($availableHours, 2),
                'unit_count' => $unitCount,
                'vs_target' => round($uptimePct - $target, 1),
                'target' => $target,
            ],
            'barChart' => $this->barChartData($user, $filters, $from, $to, $grain),
            'pieSupplier' => $this->pieByColumn($user, $filters, $from, $to, 'supplier'),
            'pieMheType' => $this->pieByColumn($user, $filters, $from, $to, 'mhe_type'),
            'details' => $this->detailRows($user, $filters, $from, $to),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{labels: list<string>, values: list<float>, target: float}
     */
    protected function barChartData(User $user, array $filters, Carbon $from, Carbon $to, string $grain): array
    {
        $buckets = $this->timeBuckets($from, $to, $grain);
        $labels = [];
        $values = [];

        foreach ($buckets as $bucket) {
            $bucketFrom = $bucket['from'];
            $bucketTo = $bucket['to'];

            $available = $this->capacityService->availableHoursForDateRange(
                $bucketFrom,
                $bucketTo,
                fn (Builder $q) => $this->applyCapacityFilters($q, $filters, $user),
            );

            $down = (float) $this->downtimeBaseQuery($filters, $user)
                ->whereBetween('date_of_incident', [$bucketFrom, $bucketTo->copy()->endOfDay()])
                ->sum('hours_down');

            $labels[] = $bucket['label'];
            $values[] = round($this->uptimePercent($down, $available), 1);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'target' => (float) config('mhe.uptime_target_pct', 95),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{labels: list<string>, values: list<float>}
     */
    protected function pieByColumn(User $user, array $filters, Carbon $from, Carbon $to, string $dimension): array
    {
        $query = $this->downtimeBaseQuery($filters, $user)
            ->whereBetween('mhe_downtimes.date_of_incident', [$from, $to->copy()->endOfDay()]);

        if ($dimension === 'supplier') {
            $rows = $query
                ->leftJoin('suppliers', 'suppliers.id', '=', 'mhe_downtimes.supplier_id')
                ->selectRaw('COALESCE(suppliers.supplier_name, ?) as label', ['Unassigned'])
                ->selectRaw('SUM(mhe_downtimes.hours_down) as total')
                ->groupBy('mhe_downtimes.supplier_id', 'suppliers.supplier_name')
                ->orderByDesc('total')
                ->get();
        } else {
            $rows = $query
                ->join('mhe_types', 'mhe_types.id', '=', 'mhe_downtimes.mhe_type_id')
                ->select('mhe_types.description as label')
                ->selectRaw('SUM(mhe_downtimes.hours_down) as total')
                ->groupBy('mhe_types.id', 'mhe_types.description')
                ->orderByDesc('total')
                ->get();
        }

        return [
            'labels' => $rows->pluck('label')->map(fn ($l) => (string) $l)->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function detailRows(User $user, array $filters, Carbon $from, Carbon $to): Collection
    {
        $months = $this->capacityService->monthsInRange($from, $to);

        $capacityQuery = DB::table('mhe_monthly_capacities as c')
            ->join('sites as s', 's.id', '=', 'c.site_id')
            ->join('mhe_types as mt', 'mt.id', '=', 'c.mhe_type_id')
            ->leftJoin('suppliers as sup', 'sup.id', '=', 'c.supplier_id')
            ->whereIn('c.yyyymm', $months)
            ->selectRaw('c.site_id, c.mhe_type_id, c.supplier_id')
            ->selectRaw('s.site_name, mt.description as mhe_type_name')
            ->selectRaw('COALESCE(sup.supplier_name, ?) as supplier_name', ['Unassigned'])
            ->selectRaw('SUM(c.available_hours) as raw_available_hours')
            ->groupBy('c.site_id', 'c.mhe_type_id', 'c.supplier_id', 's.site_name', 'mt.description', 'sup.supplier_name');

        $this->applyCapacityFiltersOnAlias($capacityQuery, $filters, $user, 'c');

        $proRate = $this->proRateFactor($from, $to, $months);

        $capacityRows = $capacityQuery->get()->keyBy(
            fn ($row) => "{$row->site_id}-{$row->mhe_type_id}-".($row->supplier_id ?? 'null')
        );

        $downtimeQuery = $this->downtimeBaseQuery($filters, $user)
            ->whereBetween('mhe_downtimes.date_of_incident', [$from, $to->copy()->endOfDay()])
            ->join('sites as s', 's.id', '=', 'mhe_downtimes.site_id')
            ->join('mhe_types as mt', 'mt.id', '=', 'mhe_downtimes.mhe_type_id')
            ->leftJoin('suppliers as sup', 'sup.id', '=', 'mhe_downtimes.supplier_id')
            ->selectRaw('mhe_downtimes.site_id, mhe_downtimes.mhe_type_id, mhe_downtimes.supplier_id')
            ->selectRaw('s.site_name, mt.description as mhe_type_name')
            ->selectRaw('COALESCE(sup.supplier_name, ?) as supplier_name', ['Unassigned'])
            ->selectRaw('SUM(mhe_downtimes.hours_down) as hours_down')
            ->groupBy('mhe_downtimes.site_id', 'mhe_downtimes.mhe_type_id', 'mhe_downtimes.supplier_id', 's.site_name', 'mt.description', 'sup.supplier_name');

        $downtimeRows = $downtimeQuery->get()->keyBy(
            fn ($row) => "{$row->site_id}-{$row->mhe_type_id}-".($row->supplier_id ?? 'null')
        );

        $keys = $capacityRows->keys()->merge($downtimeRows->keys())->unique();

        return $keys->map(function (string $key) use ($capacityRows, $downtimeRows, $proRate) {
            $cap = $capacityRows->get($key);
            $down = $downtimeRows->get($key);

            $available = $cap ? (float) $cap->raw_available_hours * $proRate : 0.0;
            $hoursDown = $down ? (float) $down->hours_down : 0.0;
            $uptime = $this->uptimePercent($hoursDown, $available);

            return (object) [
                'site_name' => $cap->site_name ?? $down->site_name,
                'mhe_type_name' => $cap->mhe_type_name ?? $down->mhe_type_name,
                'supplier_name' => $cap->supplier_name ?? $down->supplier_name,
                'available_hours' => round($available, 2),
                'hours_down' => round($hoursDown, 2),
                'uptime_pct' => round($uptime, 1),
                'status' => $this->statusLabel($uptime),
            ];
        })->sortByDesc('hours_down')->values();
    }

    /**
     * @param  list<int>  $months
     */
    protected function proRateFactor(Carbon $from, Carbon $to, array $months): float
    {
        $selectedDays = 0;
        $fullMonthDays = 0;

        foreach ($months as $yyyymm) {
            $daysInMonth = $this->capacityService->daysInMonth($yyyymm);
            $monthStart = Carbon::createFromFormat('Ym-d', $yyyymm.'-01')->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
            $rangeStart = $from->copy()->startOfDay()->max($monthStart);
            $rangeEnd = $to->copy()->startOfDay()->min($monthEnd);
            $selectedDays += max(0, $rangeStart->diffInDays($rangeEnd) + 1);
            $fullMonthDays += $daysInMonth;
        }

        if ($fullMonthDays <= 0) {
            return 1.0;
        }

        return $selectedDays / $fullMonthDays;
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
     * @return list<array{from: Carbon, to: Carbon, label: string}>
     */
    protected function timeBuckets(Carbon $from, Carbon $to, string $grain): array
    {
        return match ($grain) {
            'daily' => collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
                ->map(fn (Carbon $day) => [
                    'from' => $day->copy()->startOfDay(),
                    'to' => $day->copy()->startOfDay(),
                    'label' => $day->format('M j'),
                ])->all(),
            'weekly' => $this->weeklyBuckets($from, $to),
            'quarterly' => $this->quarterlyBuckets($from, $to),
            default => $this->monthlyBuckets($from, $to),
        };
    }

    /**
     * @return list<array{from: Carbon, to: Carbon, label: string}>
     */
    protected function weeklyBuckets(Carbon $from, Carbon $to): array
    {
        $buckets = [];
        $cursor = $from->copy()->startOfDay()->startOfWeek(Carbon::MONDAY);
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $bucketFrom = $cursor->copy()->max($from->copy()->startOfDay());
            $bucketTo = $weekEnd->min($end);

            $buckets[] = [
                'from' => $bucketFrom,
                'to' => $bucketTo,
                'label' => 'W'.$bucketFrom->isoWeek().' '.$bucketFrom->format('Y'),
            ];

            $cursor = $weekEnd->copy()->addDay();
        }

        return $buckets;
    }

    /**
     * @return list<array{from: Carbon, to: Carbon, label: string}>
     */
    protected function monthlyBuckets(Carbon $from, Carbon $to): array
    {
        $buckets = [];
        foreach ($this->capacityService->monthsInRange($from, $to) as $yyyymm) {
            $monthStart = Carbon::createFromFormat('Ym-d', $yyyymm.'-01')->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
            $buckets[] = [
                'from' => $monthStart->max($from->copy()->startOfDay()),
                'to' => $monthEnd->min($to->copy()->startOfDay()),
                'label' => $monthStart->format('M Y'),
            ];
        }

        return $buckets;
    }

    /**
     * @return list<array{from: Carbon, to: Carbon, label: string}>
     */
    protected function quarterlyBuckets(Carbon $from, Carbon $to): array
    {
        $buckets = [];
        $cursor = $from->copy()->startOfQuarter();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $qStart = $cursor->copy()->startOfQuarter();
            $qEnd = $cursor->copy()->endOfQuarter()->startOfDay();
            $bucketFrom = $qStart->max($from->copy()->startOfDay());
            $bucketTo = $qEnd->min($end);

            $buckets[] = [
                'from' => $bucketFrom,
                'to' => $bucketTo,
                'label' => 'Q'.$bucketFrom->quarter.' '.$bucketFrom->format('Y'),
            ];

            $cursor = $qEnd->copy()->addDay()->startOfQuarter();
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function downtimeBaseQuery(array $filters, User $user): Builder
    {
        $query = DB::table('mhe_downtimes')
            ->where('mhe_downtimes.status', DowntimeStatus::Posted->value)
            ->whereNull('mhe_downtimes.deleted_at');

        if (! empty($filters['site_id'])) {
            $query->where('mhe_downtimes.site_id', $filters['site_id']);
        }

        if (! empty($filters['mhe_type_id'])) {
            $query->where('mhe_downtimes.mhe_type_id', $filters['mhe_type_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('mhe_downtimes.supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['district_id'])) {
            $siteIds = Site::query()->where('district_id', $filters['district_id'])->pluck('id');
            $query->whereIn('mhe_downtimes.site_id', $siteIds);
        }

        if ($this->userDataScopeService->applies($user)) {
            $siteIds = $user->assignedSiteIds();
            if ($siteIds !== []) {
                $query->whereIn('mhe_downtimes.site_id', $siteIds);
            }

            $supplierIds = $user->assignedSupplierIds();
            if ($supplierIds !== []) {
                $query->whereIn('mhe_downtimes.supplier_id', $supplierIds);
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyCapacityFilters(Builder $query, array $filters, User $user): void
    {
        $this->applyCapacityFiltersOnAlias($query, $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyCapacityFiltersOnAlias(Builder $query, array $filters, User $user, string $alias = ''): void
    {
        $prefix = $alias !== '' ? $alias.'.' : '';

        if (! empty($filters['site_id'])) {
            $query->where($prefix.'site_id', $filters['site_id']);
        }

        if (! empty($filters['mhe_type_id'])) {
            $query->where($prefix.'mhe_type_id', $filters['mhe_type_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where($prefix.'supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['district_id'])) {
            $siteIds = Site::query()->where('district_id', $filters['district_id'])->pluck('id');
            $query->whereIn($prefix.'site_id', $siteIds);
        }

        if ($this->userDataScopeService->applies($user)) {
            $siteIds = $user->assignedSiteIds();
            if ($siteIds !== []) {
                $query->whereIn($prefix.'site_id', $siteIds);
            }

            $supplierIds = $user->assignedSupplierIds();
            if ($supplierIds !== []) {
                $query->whereIn($prefix.'supplier_id', $supplierIds);
            }
        }
    }
}
