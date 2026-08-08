<?php

namespace App\Http\Controllers;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Controllers\Concerns\NormalizesUploadedFiles;
use App\Http\Requests\StoreMheDowntimeRequest;
use App\Http\Requests\UpdateMheDowntimeRequest;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\AttachmentService;
use App\Services\MheDowntimeService;
use App\Services\MheInventoryLookupService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MheDowntimeController extends Controller
{
    use HandlesListPage;
    use NormalizesUploadedFiles;

    public function __construct(
        protected MheDowntimeService $mheDowntimeService,
        protected UserDataScopeService $userDataScopeService,
        protected AttachmentService $attachmentService,
        protected MheInventoryLookupService $mheInventoryLookupService,
    ) {
        $this->authorizeResource(MheDowntime::class, 'mhe_downtime');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $sitesQuery = Site::query()->with('district')->orderBy('site_name');
        $this->userDataScopeService->scopeSite($sitesQuery, $user);
        $sites = $sitesQuery->get();

        $state = $this->resolveListState($request, 'mhe-downtimes', ['sort' => 'created_at', 'direction' => 'desc']);
        $state['filters'] = $this->sanitizeListFilters($state['filters'] ?? [], $sites->pluck('id')->all());

        $query = MheDowntime::query()
            ->with(['site.district', 'mheType', 'mheCategory', 'creator'])
            ->withCount('actionPlans');

        $this->userDataScopeService->scopeMheDowntime($query, $user);
        $this->applyDowntimeFilters($query, $state['filters'] ?? []);

        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('mhe-downtimes.index', [
            'records' => $records,
            'state' => $state,
            'sites' => $sites,
            'districts' => District::query()->orderBy('district_name')->get(),
            'mheTypes' => MheType::query()->where('status', RecordStatus::Active)->orderBy('code')->get(),
            'mheCategories' => MheCategory::query()->where('status', RecordStatus::Active)->orderBy('code')->get(),
            'statuses' => DowntimeStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('mhe-downtimes.show', $this->showViewData($request, new MheDowntime, isNew: true));
    }

    public function store(StoreMheDowntimeRequest $request): RedirectResponse
    {
        $downtime = $this->mheDowntimeService->createDraft($request->user(), $request->validated());

        try {
            $this->storeUploadedAttachments($request, $downtime);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return redirect()
                ->route('mhe-downtimes.show', $downtime)
                ->withErrors(['files' => $exception->getMessage()]);
        }

        return redirect()->route('mhe-downtimes.show', $downtime)->with('success', 'MHE downtime draft created successfully.');
    }

    public function show(Request $request, MheDowntime $mheDowntime): View
    {
        return view('mhe-downtimes.show', $this->showViewData($request, $mheDowntime, isNew: false));
    }

    public function edit(MheDowntime $mheDowntime): RedirectResponse
    {
        return redirect()->route('mhe-downtimes.show', $mheDowntime);
    }

    public function update(UpdateMheDowntimeRequest $request, MheDowntime $mheDowntime): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $posted = false;

        try {
            if ($mheDowntime->isPosted()) {
                $this->mheDowntimeService->savePostedDatetimes($mheDowntime, $user, $data);
            } else {
                $saveAs = $request->input('save_as', 'draft');

                if (in_array($saveAs, ['post', 'final'], true)) {
                    $this->authorize('post', $mheDowntime);
                    $this->mheDowntimeService->saveAndPost($mheDowntime, $user, $data);
                    $posted = true;
                } else {
                    $this->mheDowntimeService->saveDraft($mheDowntime, $user, $data);
                }
            }
        } catch (RuntimeException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $message = $posted
            ? 'MHE downtime posted successfully.'
            : 'MHE downtime saved successfully.';

        return redirect()->route('mhe-downtimes.show', $mheDowntime)->with('success', $message);
    }

    public function destroy(Request $request, MheDowntime $mheDowntime): RedirectResponse
    {
        if (! $mheDowntime->isDraft()) {
            return redirect()->back()->with('error', 'Only draft downtime records can be deleted.');
        }

        $mheDowntime->update(['updated_by' => $request->user()->id]);
        $mheDowntime->delete();

        return redirect()->route('mhe-downtimes.index')->with('success', 'MHE downtime deleted successfully.');
    }

    public function cancel(Request $request, MheDowntime $mheDowntime): RedirectResponse
    {
        $this->authorize('cancel', $mheDowntime);

        try {
            $this->mheDowntimeService->cancel($mheDowntime, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('mhe-downtimes.show', $mheDowntime)->with('success', 'MHE downtime cancelled successfully.');
    }

    public function revertToDraft(Request $request, MheDowntime $mheDowntime): RedirectResponse
    {
        $this->authorize('revertToDraft', $mheDowntime);

        try {
            $this->mheDowntimeService->revertToDraft($mheDowntime, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('mhe-downtimes.show', $mheDowntime)->with('success', 'MHE downtime reverted to draft.');
    }

    public function searchSites(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MheDowntime::class);

        return response()->json(
            $this->mheInventoryLookupService->searchSites($request->user(), $request->query('q'))
        );
    }

    public function lookupSite(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MheDowntime::class);

        return response()->json(
            $this->mheInventoryLookupService->lookupSite($request->user(), (string) $request->query('q', ''))
        );
    }

    public function searchUnits(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MheDowntime::class);

        return response()->json(
            $this->mheInventoryLookupService->searchUnits(
                $request->user(),
                (int) $request->query('site_id'),
                $request->query('mhe_type_id') ? (int) $request->query('mhe_type_id') : null,
                $request->query('q'),
            )
        );
    }

    public function lookupUnit(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MheDowntime::class);

        $result = $this->mheInventoryLookupService->lookupUnit(
            $request->user(),
            (int) $request->query('site_id'),
            (string) $request->query('ref_unit_no', ''),
        );

        return response()->json([
            'matched' => $result['matched'],
            'supplier_id' => $result['supplier_id'],
            'unit_no' => $result['unit_no'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function showViewData(Request $request, MheDowntime $downtime, bool $isNew): array
    {
        if (! $isNew) {
            $downtime->load([
                'site.district',
                'mheType',
                'mheCategory',
                'mheInventory',
                'supplier',
                'actionPlans.attachments',
                'actionPlans.comments.creator',
                'attachments',
                'creator',
                'updater',
                'poster',
            ]);
        }

        $user = $request->user();
        $sitesQuery = Site::query()->with('district')->orderBy('site_name');
        $this->userDataScopeService->scopeSite($sitesQuery, $user);

        if (! $isNew) {
            $sitesQuery->orWhere('id', $downtime->site_id);
        }

        $suppliersQuery = Supplier::query()
            ->where('status', RecordStatus::Active)
            ->orderBy('supplier_name');
        $this->userDataScopeService->scopeSupplier($suppliersQuery, $user);

        $canEdit = $isNew || ($request->user()->can('update', $downtime) && $downtime->isDraft());
        $canEditTimes = ! $isNew && $downtime->isPosted() && $request->user()->can('update', $downtime);
        $canManageActionPlans = ! $isNew
            && $downtime->isPosted()
            && $request->user()->can('create', \App\Models\MheDowntimeActionPlan::class);

        return [
            'downtime' => $downtime,
            'isNew' => $isNew,
            'sites' => $sitesQuery->get(),
            'suppliers' => $suppliersQuery->get(),
            'mheTypes' => MheType::query()->where('status', RecordStatus::Active)->orderBy('code')->get(),
            'mheCategories' => MheCategory::query()->where('status', RecordStatus::Active)->orderBy('code')->get(),
            'canEdit' => $canEdit,
            'canEditTimes' => $canEditTimes,
            'canPost' => ! $isNew && $request->user()->can('post', $downtime),
            'canCancel' => ! $isNew && $request->user()->can('cancel', $downtime),
            'canRevertToDraft' => ! $isNew && $request->user()->can('revertToDraft', $downtime),
            'canUploadAttachments' => $canEdit,
            'canManageActionPlans' => $canManageActionPlans,
            'progressStatuses' => \App\Enums\ProgressStatus::cases(),
        ];
    }

    protected function storeUploadedAttachments(Request $request, MheDowntime $downtime): void
    {
        $files = $this->normalizeUploadedFiles($request->file('files'));

        if ($files === []) {
            return;
        }

        $this->attachmentService->storeMany($downtime, $request->user(), $files);
    }

    /**
     * @param  array<int, int>  $allowedSiteIds
     * @return array<string, mixed>
     */
    protected function sanitizeListFilters(array $filters, array $allowedSiteIds): array
    {
        if (! empty($filters['site_id']) && ! in_array((int) $filters['site_id'], $allowedSiteIds, true)) {
            unset($filters['site_id']);
        }

        return $filters;
    }

    /**
     * @param  Builder<MheDowntime>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyDowntimeFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['district_id'])) {
            $query->whereHas('site', fn (Builder $q) => $q->where('district_id', $filters['district_id']));
        }

        if (! empty($filters['mhe_type_id'])) {
            $query->where('mhe_type_id', $filters['mhe_type_id']);
        }

        if (! empty($filters['mhe_category_id'])) {
            $query->where('mhe_category_id', $filters['mhe_category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_of_incident', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_of_incident', '<=', $filters['date_to']);
        }

        if (! empty($filters['needs_action_plan'])) {
            $query->needsActionPlan();
        }
    }

    protected function defaultSortColumn(): string
    {
        return 'created_at';
    }

    protected function sortableColumns(): array
    {
        return ['id', 'title', 'date_of_incident', 'hours_down', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['title', 'ref_unit_no', 'root_cause', 'description'];
    }
}
