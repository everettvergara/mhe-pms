<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\PmsStatus;
use App\Models\ActionPlan;
use App\Models\ActivityLog;
use App\Models\PmsHeader;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forAdmin(User $user): array
    {
        $pmsQuery = PmsHeader::query();
        $this->userDataScopeService->scopePmsHeader($pmsQuery, $user);

        $actionPlanQuery = ActionPlan::query()
            ->whereHas('pmsDetail.pmsHeader');
        $this->userDataScopeService->scopeActionPlan($actionPlanQuery, $user);

        return [
            'kpis' => [
                'total_pms' => (clone $pmsQuery)->count(),
                'draft_pms' => (clone $pmsQuery)->where('status', PmsStatus::Draft)->count(),
                'no_findings' => (clone $pmsQuery)->where('status', PmsStatus::NoFindings)->count(),
                'with_findings' => (clone $pmsQuery)->where('status', PmsStatus::WithFindings)->count(),
                'cancelled_pms' => (clone $pmsQuery)->where('status', PmsStatus::Cancelled)->count(),
                'pending_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Pending)->count(),
                'waiting_confirmation' => (clone $actionPlanQuery)
                    ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
                    ->wherePmsSubmitted()
                    ->count(),
                'confirmed_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Confirmed)->count(),
                'rejected_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Rejected)->count(),
            ],
            'pending_confirmations' => $this->pendingConfirmations($user),
            'pending_checklists' => $this->pendingChecklists($user),
            'recent_pms' => $this->recentPms($user),
            'recent_confirmations' => $this->recentConfirmations($user),
            'charts' => [
                'pms_status' => $this->pmsStatusDistribution($user),
                'action_plan_status' => $this->actionPlanStatusDistribution($user),
            ],
            'recent_activities' => ActivityLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(10)
                ->get(),
            'upcoming_pms_schedule' => $this->upcomingPmsSchedule($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forSupplier(User $user): array
    {
        $pmsQuery = PmsHeader::query();
        $this->userDataScopeService->scopePmsHeader($pmsQuery, $user);

        $actionPlanQuery = ActionPlan::query()
            ->whereHas('pmsDetail.pmsHeader');
        $this->userDataScopeService->scopeActionPlan($actionPlanQuery, $user);

        return [
            'kpis' => [
                'draft_pms' => (clone $pmsQuery)->where('status', PmsStatus::Draft)->count(),
                'submitted_pms' => (clone $pmsQuery)->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])->count(),
                'with_findings' => (clone $pmsQuery)->where('status', PmsStatus::WithFindings)->count(),
                'pending_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Pending)->count(),
                'waiting_confirmation' => (clone $actionPlanQuery)
                    ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
                    ->wherePmsSubmitted()
                    ->count(),
                'confirmed_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Confirmed)->count(),
                'rejected_action_plans' => (clone $actionPlanQuery)->where('status', ActionPlanStatus::Rejected)->count(),
            ],
            'draft_checklists' => (clone $pmsQuery)
                ->where('status', PmsStatus::Draft)
                ->with(['site', 'mheType'])
                ->latest('updated_at')
                ->limit(10)
                ->get(),
            'pending_checklists' => (clone $pmsQuery)
                ->where('status', PmsStatus::WithFindings)
                ->with('site')
                ->latest('submitted_at')
                ->limit(10)
                ->get(),
            'action_plans_attention' => (clone $actionPlanQuery)
                ->whereIn('status', [ActionPlanStatus::Pending, ActionPlanStatus::Rejected])
                ->with(['pmsDetail.checklistItem', 'pmsDetail.pmsHeader.site', 'pmsDetail.pmsHeader.mheType'])
                ->orderBy('timeline_to')
                ->limit(10)
                ->get(),
            'waiting_confirmation' => (clone $actionPlanQuery)
                ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
                ->wherePmsSubmitted()
                ->with(['pmsDetail.pmsHeader.site'])
                ->latest('updated_at')
                ->limit(10)
                ->get(),
            'recently_confirmed' => (clone $actionPlanQuery)
                ->where('status', ActionPlanStatus::Confirmed)
                ->with(['pmsDetail.pmsHeader.site'])
                ->latest('confirmed_at')
                ->limit(10)
                ->get(),
            'upcoming_pms_schedule' => $this->upcomingPmsSchedule($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pmsSchedule(User $user): array
    {
        $query = $this->scheduledPmsQuery($user);
        $today = Carbon::today();
        $endOfWeek = $today->copy()->endOfWeek();
        $endOfMonth = $today->copy()->endOfMonth();

        return [
            'kpis' => [
                'overdue' => (clone $query)->whereDate('next_schedule_date', '<', $today)->count(),
                'due_today' => (clone $query)->whereDate('next_schedule_date', $today)->count(),
                'due_this_week' => (clone $query)
                    ->whereDate('next_schedule_date', '>=', $today)
                    ->whereDate('next_schedule_date', '<=', $endOfWeek)
                    ->count(),
                'due_this_month' => (clone $query)
                    ->whereDate('next_schedule_date', '>=', $today)
                    ->whereDate('next_schedule_date', '<=', $endOfMonth)
                    ->count(),
                'total_scheduled' => (clone $query)->count(),
            ],
            'records' => (clone $query)
                ->orderBy('next_schedule_date')
                ->get(),
        ];
    }

    /**
     * @return Collection<int, PmsHeader>
     */
    protected function upcomingPmsSchedule(?User $user = null, int $limit = 10): Collection
    {
        return $this->scheduledPmsQuery($user)
            ->orderBy('next_schedule_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<PmsHeader>
     */
    protected function scheduledPmsQuery(?User $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = PmsHeader::query()
            ->whereNotNull('next_schedule_date')
            ->where('status', '!=', PmsStatus::Cancelled)
            ->with(['supplier', 'site', 'mheType']);

        if ($user !== null) {
            $this->userDataScopeService->scopePmsHeader($query, $user);
        }

        return $query;
    }

    /**
     * @return Collection<int, ActionPlan>
     */
    protected function pendingConfirmations(User $user): Collection
    {
        $query = ActionPlan::query()
            ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
            ->wherePmsSubmitted()
            ->with([
                'pmsDetail.checklistItem',
                'pmsDetail.pmsHeader.supplier',
                'pmsDetail.pmsHeader.site',
                'pmsDetail.pmsHeader.mheType',
            ]);

        $this->userDataScopeService->scopeActionPlan($query, $user);

        return $query
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, PmsHeader>
     */
    protected function pendingChecklists(User $user): Collection
    {
        $query = PmsHeader::query()
            ->where('status', PmsStatus::WithFindings)
            ->with(['supplier', 'site', 'mheType'])
            ->withCount(['pmsDetails as findings_count' => function ($q): void {
                $q->where('answer', 'No Good');
            }]);

        $this->userDataScopeService->scopePmsHeader($query, $user);

        return $query
            ->latest('submitted_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, PmsHeader>
     */
    protected function recentPms(User $user): Collection
    {
        $query = PmsHeader::query()
            ->with(['supplier', 'site', 'mheType']);

        $this->userDataScopeService->scopePmsHeader($query, $user);

        return $query
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, ActionPlan>
     */
    protected function recentConfirmations(User $user): Collection
    {
        $query = ActionPlan::query()
            ->where('status', ActionPlanStatus::Confirmed)
            ->with([
                'pmsDetail.pmsHeader.supplier',
                'pmsDetail.pmsHeader.site',
                'confirmer',
            ]);

        $this->userDataScopeService->scopeActionPlan($query, $user);

        return $query
            ->latest('confirmed_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    protected function pmsStatusDistribution(User $user): array
    {
        $query = PmsHeader::query();
        $this->userDataScopeService->scopePmsHeader($query, $user);

        return $query
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [(string) $status => (int) $total])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function actionPlanStatusDistribution(User $user): array
    {
        $query = ActionPlan::query();
        $this->userDataScopeService->scopeActionPlan($query, $user);

        return $query
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [(string) $status => (int) $total])
            ->all();
    }
}
