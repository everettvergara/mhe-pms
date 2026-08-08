<?php

namespace App\Services;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Models\MheDowntime;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, array{district: string, dates: array<string, int>}>>
     */
    public function utilizationPivot(User $user, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->startOfDay();
        $siteIds = $this->resolvedSiteIds($user, $filters);

        $sites = Site::query()
            ->with('district')
            ->whereIn('id', $siteIds)
            ->orderBy('site_name')
            ->get();

        $incidents = $this->postedQuery($user, $filters)
            ->whereIn('site_id', $siteIds)
            ->whereBetween('date_of_incident', [$from, $to->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (MheDowntime $d) => $d->site_id)
            ->map(fn (Collection $rows) => $rows->groupBy(fn (MheDowntime $d) => $d->date_of_incident->toDateString())->map->count());

        $pivot = [];
        foreach ($sites as $site) {
            $dates = [];
            foreach (CarbonPeriod::create($from, $to) as $day) {
                $dateKey = $day->format('Y-m-d');
                $row = $incidents->get($site->id)?->get($dateKey);
                $dates[$dateKey] = $row && (int) $row > 0 ? 1 : 0;
            }

            $pivot[$site->site_name] = [
                'district' => $site->district?->district_name ?? '',
                'dates' => $dates,
            ];
        }

        return $pivot;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function actionPlanGroups(User $user, array $filters): Collection
    {
        $asOf = Carbon::parse($filters['as_of_date']);
        $from = $asOf->copy()->subYear()->startOfDay();
        $isPending = (bool) ($filters['is_pending'] ?? true);
        $isImplemented = (bool) ($filters['is_implemented'] ?? true);
        $isNoActionPlan = (bool) ($filters['is_no_action_plan'] ?? true);

        $downtimes = $this->postedQuery($user, $filters)
            ->with(['mheCategory', 'site', 'actionPlans'])
            ->whereBetween('date_of_incident', [$from, $asOf->copy()->endOfDay()])
            ->orderBy('date_of_incident')
            ->get();

        return $downtimes->groupBy('mhe_category_id')->map(function (Collection $group) use ($isPending, $isImplemented, $isNoActionPlan) {
            $first = $group->first();

            return [
                'mhe_category' => $first->mheCategory?->name ?? 'Uncategorized',
                'count' => $group->count(),
                'mhes' => $group->map(function (MheDowntime $mhe) use ($isPending, $isImplemented, $isNoActionPlan) {
                    $plans = $mhe->actionPlans->map(function ($plan) use ($isPending, $isImplemented) {
                        $isResolved = in_array($plan->status, [
                            DowntimeActionPlanStatus::WaitingForFastConfirmation,
                            DowntimeActionPlanStatus::Confirmed,
                        ], true);

                        $show = ($plan->status === DowntimeActionPlanStatus::Pending && $isPending)
                            || ($isResolved && $isImplemented);

                        if (! $show) {
                            return null;
                        }

                        $highlightClass = match ($plan->status) {
                            DowntimeActionPlanStatus::Pending => 'bg-warning',
                            DowntimeActionPlanStatus::WaitingForFastConfirmation => 'bg-info text-dark',
                            DowntimeActionPlanStatus::Confirmed => 'bg-success',
                            DowntimeActionPlanStatus::Rejected => 'bg-danger',
                            default => 'bg-secondary',
                        };

                        return [
                            'highlight_class' => $highlightClass,
                            'name' => $plan->status->value,
                            'date' => $plan->timeline_to?->format('Y-m-d') ?? $plan->created_at?->format('Y-m-d'),
                            'responsible' => $plan->responsible_person ?? '',
                            'text' => str($plan->title)->limit(50)->toString(),
                        ];
                    })->filter()->values();

                    $header = sprintf('%06d', $mhe->id)
                        .' ('.($mhe->site?->site_name).') '
                        .' ('.$mhe->ref_unit_no.') - '
                        .$mhe->date_of_incident->toDateString()
                        .' - '.str($mhe->title)->limit(50);

                    if ($plans->isEmpty()) {
                        $header .= ' - '.$mhe->date_of_incident->diffInDays(now()).' Day/s elapsed';
                    }

                    return [
                        'header' => $header,
                        'plans' => $plans,
                        'no_action_plan' => $isNoActionPlan && $plans->isEmpty(),
                    ];
                })->values(),
            ];
        })->sortByDesc('count')->values();
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
