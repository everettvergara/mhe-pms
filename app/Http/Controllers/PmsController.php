<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\PmsStatus;
use App\Enums\ProgressStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StorePmsRequest;
use App\Http\Requests\UpdatePmsRequest;
use App\Models\ActionPlan;
use App\Models\MheType;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\PmsService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmsController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected PmsService $pmsService,
        protected UserDataScopeService $userDataScopeService,
    ) {
        $this->authorizeResource(PmsHeader::class, 'pms');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $suppliersQuery = Supplier::query()->orderBy('supplier_name');
        $sitesQuery = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSupplier($suppliersQuery, $user);
        $this->userDataScopeService->scopeSite($sitesQuery, $user);

        $suppliers = $suppliersQuery->get();
        $sites = $sitesQuery->get();

        $state = $this->resolveListState($request, 'pms', ['sort' => 'created_at', 'direction' => 'desc']);
        $state['filters'] = $this->sanitizeListFilters(
            $state['filters'] ?? [],
            $suppliers->pluck('id')->all(),
            $sites->pluck('id')->all(),
        );

        $query = PmsHeader::query()
            ->with(['supplier', 'site', 'mheType', 'creator'])
            ->withCount(array_merge(
                ['actionPlans as action_plans_count'],
                collect(ActionPlanStatus::cases())->mapWithKeys(
                    fn (ActionPlanStatus $status) => [
                        "actionPlans as {$status->countAttribute()}"
                            => fn (Builder $q) => $q->where('action_plans.status', $status->value),
                    ]
                )->all()
            ));

        $this->userDataScopeService->scopePmsHeader($query, $user);

        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('pms.index', [
            'records' => $records,
            'state' => $state,
            'suppliers' => $suppliers,
            'sites' => $sites,
            'statuses' => PmsStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('pms.show', $this->showViewData($request, new PmsHeader, isNew: true));
    }

    public function store(StorePmsRequest $request): RedirectResponse
    {
        $pms = $this->pmsService->createDraft($request->user(), $request->validated());

        return redirect()->route('pms.show', $pms)->with('success', 'PMS draft created successfully.');
    }

    public function show(Request $request, PmsHeader $pms): View
    {
        return view('pms.show', $this->showViewData($request, $pms, isNew: false));
    }

    public function edit(PmsHeader $pms): RedirectResponse
    {
        return redirect()->route('pms.show', $pms);
    }

    public function update(UpdatePmsRequest $request, PmsHeader $pms): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $details = $data['details'] ?? [];
        unset($data['details']);
        $saveAs = $request->input('save_as', 'draft');

        try {
            if ($saveAs === 'final') {
                $this->authorize('finalize', $pms);
                $this->pmsService->saveAndFinalize($pms, $request->user(), $data, $details);

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'PMS saved as final.']);
                }

                return redirect()->route('pms.show', $pms)->with('success', 'PMS saved as final.');
            }

            $this->pmsService->saveDraft($pms, $request->user(), $data, $details);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'PMS draft saved successfully.']);
        }

        return redirect()->route('pms.show', $pms)->with('success', 'PMS draft saved successfully.');
    }

    public function cancel(Request $request, PmsHeader $pms): RedirectResponse
    {
        $this->authorize('cancel', $pms);

        try {
            $this->pmsService->cancel($pms, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pms.show', $pms)->with('success', 'PMS cancelled successfully.');
    }

    public function revertToDraft(Request $request, PmsHeader $pms): RedirectResponse
    {
        $this->authorize('revertToDraft', $pms);

        try {
            $this->pmsService->revertToDraft($pms, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pms.show', $pms)->with('success', 'PMS reverted to draft successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function showViewData(Request $request, PmsHeader $pms, bool $isNew): array
    {
        if (! $isNew) {
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

        $canEdit = ! $isNew && $request->user()->can('update', $pms) && $pms->isDraft();
        $canFinalize = $canEdit;
        $formOptions = ($isNew || $canEdit) ? $this->formOptions($request) : ['sites' => collect(), 'suppliers' => collect(), 'mheTypes' => collect()];

        return [
            'pms' => $pms,
            'isNew' => $isNew,
            'canEdit' => $isNew || $canEdit,
            'canFinalize' => $canFinalize,
            'canRevertToDraft' => ! $isNew && $request->user()->can('revertToDraft', $pms),
            'canUploadAttachments' => $canEdit,
            'canCreateActionPlans' => ! $isNew
                && $request->user()->can('create', ActionPlan::class)
                && $pms->status === PmsStatus::WithFindings,
            'canManageActionPlans' => ! $isNew
                && $request->user()->can('create', ActionPlan::class)
                && ($pms->isDraft() || $pms->status === PmsStatus::WithFindings),
            'progressStatuses' => ProgressStatus::cases(),
            'sites' => $formOptions['sites'],
            'suppliers' => $formOptions['suppliers'],
            'mheTypes' => $formOptions['mheTypes'],
        ];
    }

    /**
     * @return array{sites: \Illuminate\Support\Collection, suppliers: \Illuminate\Support\Collection, mheTypes: \Illuminate\Support\Collection}
     */
    protected function formOptions(Request $request): array
    {
        $user = $request->user();

        $sitesQuery = Site::query()
            ->where('status', RecordStatus::Active)
            ->orderBy('site_name');

        $this->userDataScopeService->scopeSite($sitesQuery, $user);

        $suppliersQuery = Supplier::query()
            ->where('status', RecordStatus::Active)
            ->orderBy('supplier_name');

        $this->userDataScopeService->scopeSupplier($suppliersQuery, $user);

        return [
            'sites' => $sitesQuery->get(),
            'suppliers' => $suppliersQuery->get(),
            'mheTypes' => MheType::query()
                ->where('status', RecordStatus::Active)
                ->orderBy('code')
                ->get(),
        ];
    }

    protected function sortableColumns(): array
    {
        return ['pms_no', 'technician_name', 'status', 'created_at', 'submitted_at'];
    }

    protected function searchableColumns(): array
    {
        return ['pms_no', 'technician_name', 'unit_number', 'serial_number'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_from', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_to', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $allowedSupplierIds
     * @param  array<int, int>  $allowedSiteIds
     * @return array<string, mixed>
     */
    protected function sanitizeListFilters(array $filters, array $allowedSupplierIds, array $allowedSiteIds): array
    {
        if (! empty($filters['supplier_id']) && ! in_array((int) $filters['supplier_id'], $allowedSupplierIds, true)) {
            unset($filters['supplier_id']);
        }

        if (! empty($filters['site_id']) && ! in_array((int) $filters['site_id'], $allowedSiteIds, true)) {
            unset($filters['site_id']);
        }

        return $filters;
    }
}
