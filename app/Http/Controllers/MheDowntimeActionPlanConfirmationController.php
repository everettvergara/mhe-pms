<?php

namespace App\Http\Controllers;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\RejectActionPlanRequest;
use App\Models\MheDowntimeActionPlan;
use App\Services\MheDowntimeActionPlanService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheDowntimeActionPlanConfirmationController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected MheDowntimeActionPlanService $actionPlanService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function index(Request $request): View
    {
        if (! $request->user()->isFastAdmin()) {
            abort(403);
        }

        $user = $request->user();
        $state = $this->resolveListState($request, 'mhe-downtime-action-plan-confirmations', ['sort' => 'updated_at', 'direction' => 'desc']);

        $query = MheDowntimeActionPlan::query()
            ->where('status', DowntimeActionPlanStatus::WaitingForFastConfirmation)
            ->with(['mheDowntime.supplier', 'mheDowntime.site', 'mheDowntime.mheInventory']);

        $this->userDataScopeService->scopeMheDowntimeActionPlan($query, $user);

        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('mhe-downtime-action-plan-confirmations.index', compact('records', 'state'));
    }

    public function show(MheDowntimeActionPlan $actionPlan): View|RedirectResponse
    {
        if (! auth()->user()->isFastAdmin()) {
            abort(403);
        }

        $this->authorize('view', $actionPlan);

        if ($actionPlan->status !== DowntimeActionPlanStatus::WaitingForFastConfirmation) {
            return redirect()->route('mhe-downtime-action-plan-confirmations.index');
        }

        $actionPlan->load([
            'mheDowntime.supplier',
            'mheDowntime.site',
            'mheDowntime.mheType',
            'mheDowntime.mheInventory',
            'comments.creator',
            'creator',
            'updater',
            'confirmer',
            'rejecter',
            'attachments',
        ]);

        $downtime = $actionPlan->mheDowntime;
        $progressStatuses = ProgressStatus::cases();

        return view('mhe-downtime-action-plan-confirmations.show', compact(
            'actionPlan',
            'downtime',
            'progressStatuses',
        ));
    }

    public function confirm(Request $request, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('confirm', $actionPlan);

        try {
            $this->actionPlanService->confirm($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mhe-downtime-action-plan-confirmations.index')->with('success', 'Action item confirmed successfully.');
    }

    public function reject(RejectActionPlanRequest $request, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('reject', $actionPlan);

        try {
            $this->actionPlanService->reject($request->user(), $actionPlan, $request->validated('rejection_remarks'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mhe-downtime-action-plan-confirmations.index')->with('success', 'Action item rejected.');
    }

    protected function sortableColumns(): array
    {
        return ['action_plan_no', 'updated_at', 'timeline_to'];
    }

    protected function searchableColumns(): array
    {
        return ['action_plan_no', 'title', 'responsible_person'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['supplier_id'])) {
            $query->whereHas('mheDowntime', fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']));
        }

        return $query;
    }
}
