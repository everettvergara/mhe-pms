<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreActionPlanCommentRequest;
use App\Http\Requests\StoreActionPlanRequest;
use App\Http\Requests\UpdateActionPlanRequest;
use App\Models\ActionPlan;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Supplier;
use App\Services\ActionPlanService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionPlanController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActionPlanService $actionPlanService,
        protected UserDataScopeService $userDataScopeService,
    ) {
        $this->authorizeResource(ActionPlan::class, 'action_plan');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'action-plans', ['sort' => 'created_at', 'direction' => 'desc']);

        $user = $request->user();

        $query = ActionPlan::query()
            ->with(['pmsDetail.checklistItem', 'pmsDetail.pmsHeader.supplier', 'pmsDetail.pmsHeader.site']);

        $this->userDataScopeService->scopeActionPlan($query, $user);

        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        $suppliersQuery = Supplier::query()->orderBy('supplier_name');
        $this->userDataScopeService->scopeSupplier($suppliersQuery, $user);

        return view('action-plans.index', [
            'records' => $records,
            'state' => $state,
            'statuses' => ActionPlanStatus::cases(),
            'suppliers' => $suppliersQuery->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $pmsDetailId = $request->query('pms_detail_id');
        $pmsDetail = $pmsDetailId ? PmsDetail::query()->with('checklistItem', 'pmsHeader')->findOrFail($pmsDetailId) : null;

        if ($pmsDetail !== null && ! $this->canManageActionPlansOnPms($pmsDetail->pmsHeader)) {
            abort(403, 'Action plans cannot be added to this PMS.');
        }

        return view('action-plans.form', [
            'actionPlan' => new ActionPlan,
            'pmsDetail' => $pmsDetail,
            'isEdit' => false,
            'canEdit' => true,
        ]);
    }

    public function store(StoreActionPlanRequest $request): RedirectResponse|JsonResponse
    {
        $pmsDetail = PmsDetail::query()->with('pmsHeader')->findOrFail($request->validated('pms_detail_id'));

        try {
            $actionPlan = $this->actionPlanService->create($request->user(), $pmsDetail, $request->validated());
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action Plan created successfully.']);
        }

        return redirect()->route('pms.show', $pmsDetail->pmsHeader)
            ->with('success', 'Action Plan created successfully.');
    }

    public function show(ActionPlan $actionPlan): View
    {
        $actionPlan->load([
            'pmsDetail.checklistItem.checklistGroup',
            'pmsDetail.pmsHeader',
            'comments.creator',
            'creator',
            'updater',
            'confirmer',
            'rejecter',
        ]);

        $pms = $actionPlan->pmsDetail?->pmsHeader;
        if ($pms !== null) {
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
        }

        $canManageActionPlans = $pms !== null
            && auth()->user()->can('create', ActionPlan::class)
            && $this->canManageActionPlansOnPms($pms);

        return view('action-plans.show', [
            'actionPlan' => $actionPlan,
            'pms' => $pms,
            'highlightPmsDetailId' => $actionPlan->pms_detail_id,
            'progressStatuses' => ProgressStatus::cases(),
            'canManageActionPlans' => $canManageActionPlans,
            'canEdit' => auth()->user()->can('update', $actionPlan),
        ]);
    }

    public function edit(ActionPlan $actionPlan): View
    {
        $actionPlan->load([
            'pmsDetail.checklistItem.checklistGroup',
            'pmsDetail.pmsHeader',
            'comments.creator',
        ]);

        $pms = $actionPlan->pmsDetail?->pmsHeader;
        if ($pms !== null) {
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
        }

        $canManageActionPlans = $pms !== null
            && auth()->user()->can('create', ActionPlan::class)
            && $this->canManageActionPlansOnPms($pms);

        return view('action-plans.form', [
            'actionPlan' => $actionPlan,
            'pmsDetail' => $actionPlan->pmsDetail,
            'pms' => $pms,
            'highlightPmsDetailId' => $actionPlan->pms_detail_id,
            'progressStatuses' => ProgressStatus::cases(),
            'canManageActionPlans' => $canManageActionPlans,
            'isEdit' => true,
            'canEdit' => in_array($actionPlan->status, [ActionPlanStatus::Pending, ActionPlanStatus::Rejected], true),
        ]);
    }

    public function update(UpdateActionPlanRequest $request, ActionPlan $actionPlan): RedirectResponse|JsonResponse
    {
        try {
            $this->actionPlanService->update($request->user(), $actionPlan, $request->validated());
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action Plan updated successfully.']);
        }

        return $this->redirectToContext($actionPlan, $request)
            ->with('success', 'Action Plan updated successfully.');
    }

    public function comment(StoreActionPlanCommentRequest $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('comment', $actionPlan);

        $this->actionPlanService->addComment(
            $request->user(),
            $actionPlan,
            $request->validated('comment'),
            ProgressStatus::from($request->validated('progress_status')),
        );

        return $this->redirectToContext($actionPlan, $request)
            ->with('success', 'Comment added successfully.');
    }

    public function markImplemented(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('markImplemented', $actionPlan);

        try {
            $this->actionPlanService->markImplemented($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToContext($actionPlan, $request)
            ->with('success', 'Action Plan marked as implemented.');
    }

    public function cancel(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('cancel', $actionPlan);

        try {
            $this->actionPlanService->cancel($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToContext($actionPlan, $request)
            ->with('success', 'Action Plan cancelled successfully.');
    }

    public function destroy(Request $request, ActionPlan $actionPlan): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $actionPlan);

        try {
            $actionPlan->loadMissing('pmsDetail.pmsHeader');
            $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

            $this->actionPlanService->destroy($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action Plan deleted successfully.']);
        }

        if ($request->input('return_to') === 'pms' && $pmsHeader !== null) {
            return redirect()->route('pms.show', $pmsHeader)->with('success', 'Action Plan deleted successfully.');
        }

        return redirect()->route('pms.index')->with('success', 'Action Plan deleted successfully.');
    }

    protected function canManageActionPlansOnPms(?PmsHeader $pmsHeader): bool
    {
        return $pmsHeader !== null
            && ($pmsHeader->isDraft() || $pmsHeader->status === PmsStatus::WithFindings);
    }

    protected function redirectToContext(ActionPlan $actionPlan, Request $request): RedirectResponse
    {
        if ($request->input('return_to') === 'pms') {
            $actionPlan->loadMissing('pmsDetail.pmsHeader');

            return redirect()->route('pms.show', $actionPlan->pmsDetail->pmsHeader);
        }

        return redirect()->route('action-plans.show', $actionPlan);
    }

    protected function sortableColumns(): array
    {
        return ['action_plan_no', 'title', 'status', 'timeline_to', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['action_plan_no', 'title', 'responsible_person', 'description'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']));
        }

        return $query;
    }
}
