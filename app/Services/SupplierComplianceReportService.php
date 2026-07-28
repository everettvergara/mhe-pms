<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierComplianceReportService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function build(array $filters, User $user): Collection
    {
        [$periodFrom, $periodTo] = $this->resolvePeriodRange($filters);
        $periodKeys = $this->periodKeysBetween($periodFrom, $periodTo);

        $suppliers = $this->resolveSuppliers($filters, $user);
        $sites = $this->resolveSites($filters, $user);

        if ($suppliers->isEmpty() || $sites->isEmpty() || $periodKeys === []) {
            return collect();
        }

        $aggregates = $this->aggregateActionPlans(
            $periodFrom,
            $periodTo,
            $suppliers->pluck('id')->all(),
            $sites->pluck('id')->all(),
        );

        $rows = collect();

        foreach ($suppliers as $supplier) {
            foreach ($periodKeys as $periodKey) {
                $monthLabel = Carbon::createFromFormat('Y-m', $periodKey)->format('F Y');

                foreach ($sites as $site) {
                    $aggregateKey = $supplier->id.'-'.$site->id.'-'.$periodKey;
                    $aggregate = $aggregates->get($aggregateKey);
                    $completedOnTime = (int) ($aggregate->completed_on_time ?? 0);
                    $itemsToComplete = (int) ($aggregate->items_to_complete ?? 0);

                    $rows->push((object) [
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->supplier_name,
                        'district_id' => $site->district_id,
                        'district_name' => $site->district?->district_name ?? '',
                        'site_id' => $site->id,
                        'site_name' => $site->site_name,
                        'month_key' => $periodKey,
                        'month_label' => $monthLabel,
                        'completed_on_time' => $completedOnTime,
                        'items_to_complete' => $itemsToComplete,
                        'compliance_percent' => $this->compliancePercent($completedOnTime, $itemsToComplete),
                        'show_supplier' => false,
                        'show_month' => false,
                        'show_district' => false,
                    ]);
                }
            }
        }

        return $this->applyGroupingFlags(
            $rows->sortBy([
                ['supplier_name', 'asc'],
                ['month_key', 'asc'],
                ['district_name', 'asc'],
                ['site_name', 'asc'],
            ])->values()
        );
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    public function summaryChartData(Collection $rows): array
    {
        $monthKeys = $rows->pluck('month_key')->unique()->sort()->values();

        if ($monthKeys->count() <= 1) {
            $bySupplier = $rows->groupBy('supplier_id');
            $labels = [];
            $values = [];

            foreach ($bySupplier as $supplierRows) {
                $first = $supplierRows->first();
                $completed = $supplierRows->sum('completed_on_time');
                $toComplete = $supplierRows->sum('items_to_complete');

                $labels[] = strtoupper($first->supplier_name);
                $values[] = $this->compliancePercent($completed, $toComplete);
            }

            return [
                'title' => 'MHE MONTHLY PMS COMPLIANCE',
                'mode' => 'single_month',
                'labels' => $labels,
                'values' => $values,
                'datasets' => [],
            ];
        }

        $labels = $monthKeys
            ->map(fn (string $key) => Carbon::createFromFormat('Y-m', $key)->format('M Y'))
            ->all();

        $datasets = [];

        foreach ($rows->groupBy('supplier_id') as $supplierRows) {
            $first = $supplierRows->first();
            $data = [];

            foreach ($monthKeys as $monthKey) {
                $monthRows = $supplierRows->where('month_key', $monthKey);
                $completed = $monthRows->sum('completed_on_time');
                $toComplete = $monthRows->sum('items_to_complete');
                $data[] = $this->compliancePercent($completed, $toComplete);
            }

            $datasets[] = [
                'label' => strtoupper($first->supplier_name),
                'data' => $data,
            ];
        }

        return [
            'title' => 'MHE MONTHLY PMS COMPLIANCE',
            'mode' => 'multi_month',
            'labels' => $labels,
            'values' => [],
            'datasets' => $datasets,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array{title: string, labels: array<int, string>, values: array<int, int>}>
     */
    public function supplierSiteChartsData(Collection $rows): array
    {
        $charts = [];

        $grouped = $rows->groupBy(fn (object $row) => $row->supplier_id.'|'.$row->month_key);

        foreach ($grouped->sortKeys() as $group) {
            $sorted = $group->sortBy('site_name')->values();
            $first = $sorted->first();

            $charts[] = [
                'title' => strtoupper($first->supplier_name).' MONTHLY PMS COMPLIANCE — '.$first->month_label,
                'labels' => $sorted->pluck('site_name')->map(fn (string $name) => strtoupper($name))->all(),
                'values' => $sorted->pluck('compliance_percent')->all(),
            ];
        }

        return $charts;
    }

    public function compliancePercent(int $completedOnTime, int $itemsToComplete): int
    {
        if ($itemsToComplete === 0) {
            return 100;
        }

        return (int) round(($completedOnTime / $itemsToComplete) * 100);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriodRange(array $filters): array
    {
        $default = now()->format('Y-m');
        $from = Carbon::createFromFormat('Y-m', $filters['period_from'] ?? $default)->startOfMonth();
        $to = Carbon::createFromFormat('Y-m', $filters['period_to'] ?? $default)->endOfMonth();

        if ($to->lt($from)) {
            $to = $from->copy()->endOfMonth();
        }

        return [$from, $to];
    }

    /**
     * @return array<int, string>
     */
    protected function periodKeysBetween(Carbon $from, Carbon $to): array
    {
        $keys = [];

        foreach (CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth()) as $month) {
            $keys[] = $month->format('Y-m');
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function resolveSuppliers(array $filters, User $user): Collection
    {
        $query = Supplier::query()
            ->where('status', RecordStatus::Active)
            ->orderBy('supplier_name');

        $this->userDataScopeService->scopeSupplier($query, $user);

        if (! empty($filters['supplier_id'])) {
            $query->where('id', $filters['supplier_id']);
        }

        if (! empty($filters['supplier_ids'])) {
            $query->whereIn('id', $filters['supplier_ids']);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function resolveSites(array $filters, User $user): Collection
    {
        $query = Site::query()
            ->with('district')
            ->where('status', RecordStatus::Active)
            ->orderBy('site_name');

        $this->userDataScopeService->scopeSite($query, $user);

        if (! empty($filters['site_ids'])) {
            $query->whereIn('id', $filters['site_ids']);
        }

        return $query->get();
    }

    /**
     * @param  array<int, int>  $supplierIds
     * @param  array<int, int>  $siteIds
     */
    protected function aggregateActionPlans(Carbon $periodFrom, Carbon $periodTo, array $supplierIds, array $siteIds): Collection
    {
        $confirmedStatus = ActionPlanStatus::Confirmed->value;
        $periodExpression = $this->periodKeyExpression();

        $results = ActionPlan::query()
            ->join('pms_details', 'action_plans.pms_detail_id', '=', 'pms_details.id')
            ->join('pms_headers', 'pms_details.pms_header_id', '=', 'pms_headers.id')
            ->where('action_plans.status', '!=', ActionPlanStatus::Cancelled)
            ->whereBetween('action_plans.timeline_to', [
                $periodFrom->toDateString(),
                $periodTo->toDateString(),
            ])
            ->whereIn('pms_headers.supplier_id', $supplierIds)
            ->whereIn('pms_headers.site_id', $siteIds)
            ->select([
                'pms_headers.supplier_id',
                'pms_headers.site_id',
                DB::raw("{$periodExpression} as period_key"),
                DB::raw('COUNT(*) as items_to_complete'),
                DB::raw('SUM(CASE WHEN action_plans.status = ? AND action_plans.confirmed_at <= action_plans.timeline_to THEN 1 ELSE 0 END) as completed_on_time'),
            ])
            ->addBinding($confirmedStatus, 'select')
            ->groupBy('pms_headers.supplier_id', 'pms_headers.site_id', DB::raw($periodExpression))
            ->get();

        return $results->keyBy(fn ($row) => $row->supplier_id.'-'.$row->site_id.'-'.$row->period_key);
    }

    protected function periodKeyExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', action_plans.timeline_to)",
            'mysql', 'mariadb' => "DATE_FORMAT(action_plans.timeline_to, '%Y-%m')",
            'pgsql' => "to_char(action_plans.timeline_to, 'YYYY-MM')",
            default => "DATE_FORMAT(action_plans.timeline_to, '%Y-%m')",
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function applyGroupingFlags(Collection $rows): Collection
    {
        $lastSupplierId = null;
        $lastMonthKey = null;
        $lastDistrictId = null;

        return $rows->map(function (object $row) use (&$lastSupplierId, &$lastMonthKey, &$lastDistrictId) {
            $row->show_supplier = $row->supplier_id !== $lastSupplierId;
            $row->show_month = $row->supplier_id !== $lastSupplierId || $row->month_key !== $lastMonthKey;
            $row->show_district = $row->show_month || $row->district_id !== $lastDistrictId;

            $lastSupplierId = $row->supplier_id;
            $lastMonthKey = $row->month_key;
            $lastDistrictId = $row->district_id;

            return $row;
        });
    }
}
