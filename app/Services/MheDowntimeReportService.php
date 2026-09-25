<?php

namespace App\Services;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\PmsStatus;
use App\Models\MheDowntime;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MheDowntimeReportService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, list<array{0: string, 1: int}>>
     */
    public function summaryChartData(User $user, array $filters): array
    {
        $asOf = Carbon::parse($filters['as_of_date'] ?? now('Asia/Manila')->toDateString());

        return [
            'yearly' => $this->summaryYearly($user, $filters, $asOf),
            'quarterly' => $this->summaryQuarterly($user, $filters, $asOf),
            'monthly' => $this->summaryMonthly($user, $filters, $asOf),
            'daily' => $this->summaryDaily($user, $filters, $asOf),
        ];
    }

    /**
     * Sites that submitted PMS or posted an MHE downtime in the range, and sites that did neither.
     *
     * @param  array<string, mixed>  $filters
     * @return array{used: list<array{site_code: string, site_name: string, district: string, pms: int, mhe: int, total: int}>, unused: list<array{site_code: string, site_name: string, district: string, pms: int, mhe: int, total: int}>}
     */
    public function utilizationBubbles(User $user, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();
        $siteIds = $this->resolvedSiteIds($user, $filters);

        if ($siteIds === []) {
            return ['used' => [], 'unused' => []];
        }

        $sites = Site::query()
            ->with('district')
            ->whereIn('id', $siteIds)
            ->orderBy('site_code')
            ->get();

        $mheCounts = $this->postedQuery($user, $filters)
            ->whereIn('site_id', $siteIds)
            ->whereBetween('date_of_incident', [$from, $to])
            ->selectRaw('site_id, COUNT(*) as aggregate')
            ->groupBy('site_id')
            ->pluck('aggregate', 'site_id');

        $pmsQuery = PmsHeader::query()
            ->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])
            ->whereIn('site_id', $siteIds)
            ->whereBetween('submitted_at', [$from, $to]);
        $this->userDataScopeService->scopePmsHeader($pmsQuery, $user);

        $pmsCounts = $pmsQuery
            ->selectRaw('site_id, COUNT(*) as aggregate')
            ->groupBy('site_id')
            ->pluck('aggregate', 'site_id');

        $used = [];
        $unused = [];

        foreach ($sites as $site) {
            $pms = (int) ($pmsCounts[$site->id] ?? 0);
            $mhe = (int) ($mheCounts[$site->id] ?? 0);
            $row = [
                'site_code' => $site->site_code,
                'site_name' => $site->site_name,
                'district' => $site->district?->district_name ?? '',
                'pms' => $pms,
                'mhe' => $mhe,
                'total' => $pms + $mhe,
            ];

            if ($row['total'] > 0) {
                $used[] = $row;
            } else {
                $unused[] = $row;
            }
        }

        usort($used, function (array $a, array $b): int {
            return $b['total'] <=> $a['total'] ?: strcmp($a['site_code'], $b['site_code']);
        });

        return ['used' => $used, 'unused' => $unused];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function actionPlanGroups(User $user, array $filters): Collection
    {
        $today = now('Asia/Manila');
        $fromDate = Carbon::parse($filters['date_from'] ?: $today->copy()->subYear()->toDateString())->toDateString();
        $toDate = Carbon::parse($filters['date_to'] ?: $today->toDateString())->toDateString();
        $from = $fromDate.' 00:00:00';
        $to = $toDate.' 23:59:59';
        $isPending = (bool) ($filters['is_pending'] ?? true);
        $isImplemented = (bool) ($filters['is_implemented'] ?? true);
        $isNoActionPlan = (bool) ($filters['is_no_action_plan'] ?? true);

        $downtimes = $this->postedQuery($user, $filters)
            ->with(['mheCategory', 'site', 'supplier', 'mheType', 'actionPlans'])
            ->whereBetween('date_of_incident', [$from, $to])
            ->orderBy('date_of_incident')
            ->get();

        return $downtimes
            ->groupBy('mhe_category_id')
            ->map(function (Collection $group) use ($isPending, $isImplemented, $isNoActionPlan) {
                /** @var array<string, list<array<string, mixed>>> $buckets */
                $buckets = [];
                $visibleDowntimeIds = [];

                foreach ($group as $mhe) {
                    $statuses = $this->visibleActionPlanStatuses($mhe, $isPending, $isImplemented, $isNoActionPlan);

                    if ($statuses === []) {
                        continue;
                    }

                    $visibleDowntimeIds[$mhe->id] = true;
                    $row = $this->actionPlanDetailRow($mhe);

                    foreach ($statuses as $status) {
                        $buckets[$status][] = $row;
                    }
                }

                if ($visibleDowntimeIds === []) {
                    return null;
                }

                $first = $group->first();
                $statuses = [];

                foreach ($this->actionPlanStatusOrder() as $status) {
                    if (! isset($buckets[$status])) {
                        continue;
                    }

                    $statuses[] = [
                        'status' => $status,
                        'status_key' => Str::slug($status),
                        'count' => count($buckets[$status]),
                        'rows' => $buckets[$status],
                    ];
                }

                return [
                    'mhe_category' => $first->mheCategory?->name ?? 'Uncategorized',
                    'category_key' => 'action-plan-cat-'.($first->mhe_category_id ?? 'uncategorized'),
                    'count' => count($visibleDowntimeIds),
                    'statuses' => $statuses,
                ];
            })
            ->filter()
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @return list<string>
     */
    protected function actionPlanStatusOrder(): array
    {
        return [
            'No Action Plan',
            DowntimeActionPlanStatus::Pending->value,
            DowntimeActionPlanStatus::WaitingForFastConfirmation->value,
            DowntimeActionPlanStatus::Confirmed->value,
            DowntimeActionPlanStatus::Rejected->value,
            DowntimeActionPlanStatus::Cancelled->value,
        ];
    }

    /**
     * @return list<string>
     */
    protected function visibleActionPlanStatuses(
        MheDowntime $mhe,
        bool $isPending,
        bool $isImplemented,
        bool $isNoActionPlan,
    ): array {
        if ($mhe->actionPlans->isEmpty()) {
            return $isNoActionPlan ? ['No Action Plan'] : [];
        }

        $statuses = [];

        foreach ($mhe->actionPlans as $plan) {
            $include = match ($plan->status) {
                DowntimeActionPlanStatus::Pending => $isPending,
                DowntimeActionPlanStatus::WaitingForFastConfirmation,
                DowntimeActionPlanStatus::Confirmed => $isImplemented,
                DowntimeActionPlanStatus::Rejected,
                DowntimeActionPlanStatus::Cancelled => true,
                default => false,
            };

            if ($include) {
                $statuses[$plan->status->value] = $plan->status->value;
            }
        }

        return array_values($statuses);
    }

    /**
     * @return array{site: string, unit: string, supplier: string, mhe_type: string, downtime_id: int, date: string, what: string, root_cause: string, description: string}
     */
    protected function actionPlanDetailRow(MheDowntime $mhe): array
    {
        return [
            'site' => $mhe->site?->site_name ?? '',
            'unit' => $mhe->ref_unit_no ?? '',
            'supplier' => $mhe->supplier?->supplier_name ?? '',
            'mhe_type' => $mhe->mheType?->description ?: ($mhe->mheType?->code ?? ''),
            'downtime_id' => $mhe->id,
            'date' => $mhe->date_of_incident->toDateString(),
            'what' => $mhe->title ?? '',
            'root_cause' => $mhe->root_cause ?? '',
            'description' => $mhe->description ?? '',
        ];
    }

    /**
     * @return list<int>
     */
    public function accessibleSiteIds(User $user): array
    {
        $query = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSite($query, $user);

        return $query->pluck('id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{0: string, 1: int}>
     */
    protected function summaryYearly(User $user, array $filters, Carbon $asOf): array
    {
        $start = $asOf->copy()->subYear()->startOfDay();
        $counts = $this->postedQuery($user, $filters)
            ->whereBetween('date_of_incident', [$start, $asOf->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (MheDowntime $d) => (string) $d->date_of_incident->year)
            ->map->count();

        $currentYear = (string) $asOf->year;
        $previousYear = (string) ($asOf->year - 1);

        return [
            [$previousYear, (int) ($counts[$previousYear] ?? 0)],
            [$currentYear, (int) ($counts[$currentYear] ?? 0)],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{0: string, 1: int}>
     */
    protected function summaryQuarterly(User $user, array $filters, Carbon $asOf): array
    {
        $start = $asOf->copy()->subYear()->startOfDay();
        $rows = $this->postedQuery($user, $filters)
            ->whereBetween('date_of_incident', [$start, $asOf->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (MheDowntime $d) => $d->date_of_incident->year.'-Q'.$d->date_of_incident->quarter)
            ->map->count();

        $buckets = [];
        $cursor = $asOf->copy()->subQuarters(3)->startOfQuarter();
        for ($i = 0; $i < 4; $i++) {
            $key = $cursor->year.'-Q'.$cursor->quarter;
            $buckets[] = ['Q'.$cursor->quarter.' '.$cursor->year, (int) ($rows[$key] ?? 0)];
            $cursor->addQuarter();
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{0: string, 1: int}>
     */
    protected function summaryMonthly(User $user, array $filters, Carbon $asOf): array
    {
        $start = $asOf->copy()->subMonths(2)->startOfMonth();
        $rows = $this->postedQuery($user, $filters)
            ->whereBetween('date_of_incident', [$start, $asOf->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (MheDowntime $d) => $d->date_of_incident->format('Y-m'))
            ->map->count();

        $buckets = [];
        $cursor = $start->copy();
        while ($cursor->lte($asOf)) {
            $key = $cursor->format('Y-m');
            $buckets[] = [$cursor->format('F Y'), (int) ($rows[$key] ?? 0)];
            $cursor->addMonth();
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{0: string, 1: int}>
     */
    protected function summaryDaily(User $user, array $filters, Carbon $asOf): array
    {
        $start = $asOf->copy()->subDays(4)->startOfDay();
        $rows = $this->postedQuery($user, $filters)
            ->whereBetween('date_of_incident', [$start, $asOf->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (MheDowntime $d) => $d->date_of_incident->toDateString())
            ->map->count();

        $buckets = [];
        foreach (CarbonPeriod::create($start, $asOf) as $day) {
            $key = $day->toDateString();
            $buckets[] = [$key, (int) ($rows[$key] ?? 0)];
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    protected function resolvedSiteIds(User $user, array $filters): array
    {
        if (! empty($filters['site_id'])) {
            return [(int) $filters['site_id']];
        }

        $query = Site::query();
        $this->userDataScopeService->scopeSite($query, $user);

        if (! empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }

        return $query->pluck('id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function postedQuery(User $user, array $filters): Builder
    {
        $query = MheDowntime::query()
            ->where('status', DowntimeStatus::Posted);

        $this->userDataScopeService->scopeMheDowntime($query, $user);

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['district_id'])) {
            $query->whereHas('site', fn (Builder $q) => $q->where('district_id', $filters['district_id']));
        }

        return $query;
    }
}
