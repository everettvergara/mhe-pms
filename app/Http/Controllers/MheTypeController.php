<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreMheTypeRequest;
use App\Http\Requests\UpdateMheTypeRequest;
use App\Models\MheType;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheTypeController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(MheType::class, 'mhe_type');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'mhe-types', ['sort' => 'code', 'direction' => 'asc']);
        $mheTypes = $this->paginateList($this->applyListQuery(MheType::query(), $state), $state);

        return view('mhe-types.index', compact('mheTypes', 'state'));
    }

    public function create(): View
    {
        return view('mhe-types.form', [
            'mheType' => new MheType(['status' => RecordStatus::Active]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreMheTypeRequest $request): RedirectResponse
    {
        $mheType = MheType::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'mhe_types', 'create', $mheType->id, "Created MHE type {$mheType->code}.");

        return redirect()->route('mhe-types.show', $mheType)->with('success', 'MHE Type created successfully.');
    }

    public function show(MheType $mheType): View
    {
        return view('mhe-types.show', compact('mheType'));
    }

    public function edit(MheType $mheType): View
    {
        return view('mhe-types.form', ['mheType' => $mheType, 'isEdit' => true]);
    }

    public function update(UpdateMheTypeRequest $request, MheType $mheType): RedirectResponse
    {
        $mheType->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'mhe_types', 'update', $mheType->id, "Updated MHE type {$mheType->code}.");

        return redirect()->route('mhe-types.show', $mheType)->with('success', 'MHE Type updated successfully.');
    }

    public function destroy(Request $request, MheType $mheType): RedirectResponse
    {
        $mheType->update(['updated_by' => $request->user()->id]);
        $mheType->delete();
        $this->activityLogService->log($request->user(), 'mhe_types', 'delete', $mheType->id, "Deleted MHE type {$mheType->code}.");

        return redirect()->route('mhe-types.index')->with('success', 'MHE Type deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'code';
    }

    protected function sortableColumns(): array
    {
        return ['code', 'description', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['code', 'description'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
