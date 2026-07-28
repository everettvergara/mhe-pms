<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\RejectActionPlanRequest;
use App\Models\ActionPlan;
use App\Services\ActionPlanService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionPlanConfirmationController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActionPlanService $actionPlanService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActionPlan::class);

        if (! $request->user()->isFastAdmin()) {
            abort(403);
        }

        $user = $request->user();

        $state = $this->resolveListState($request, 'action-plan-confirmations', ['sort' => 'updated_at', 'direction' => 'desc']);

        $query = ActionPlan::query()
            ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
            ->wherePmsSubmitted()
            ->with(['pmsDetail.checklistItem', 'pmsDetail.pmsHeader.supplier', 'pmsDetail.pmsHeader.site']);

        $this->userDataScopeService->scopeActionPlan($query, $user);

        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('action-plan-confirmations.index', compact('records', 'state'));
    }

    public function show(ActionPlan $actionPlan): View
    {
        if (! auth()->user()->isFastAdmin()) {
            abort(403);
        }

        $this->authorize('view', $actionPlan);

        if ($actionPlan->status !== ActionPlanStatus::WaitingForFastConfirmation) {
            return redirect()->route('action-plan-confirmations.index');
        }

        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        if (! $actionPlan->pmsDetail?->pmsHeader?->isSubmitted()) {
            return redirect()->route('action-plan-confirmations.index');
        }

        $actionPlan->load([
            'pmsDetail.checklistItem.checklistGroup',
            'comments.creator',
            'creator',
            'updater',
            'confirmer',
            'rejecter',
        ]);

        $pms = $actionPlan->pmsDetail->pmsHeader;
        $pms->load([
            'supplier',
            'site',
            'mheType',
            'attachments',
            'pmsDetails.checklistItem.checklistGroup',
            'pmsDetails.actionPlans.comments.creator',
            'pmsDetails.attachments',
            'creator',
            'updater',
            'submitter',
        ]);

        $highlightPmsDetailId = $actionPlan->pms_detail_id;
        $progressStatuses = ProgressStatus::cases();

        return view('action-plan-confirmations.show', compact(
            'actionPlan',
            'pms',
            'highlightPmsDetailId',
            'progressStatuses',
        ));
    }

    public function confirm(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('confirm', $actionPlan);

        try {
            $this->actionPlanService->confirm($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('action-plan-confirmations.index')->with('success', 'Action Plan confirmed successfully.');
    }

    public function reject(RejectActionPlanRequest $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('reject', $actionPlan);

        try {
            $this->actionPlanService->reject($request->user(), $actionPlan, $request->validated('rejection_remarks'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('action-plan-confirmations.index')->with('success', 'Action Plan rejected.');
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
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']));
        }

        return $query;
    }
}
