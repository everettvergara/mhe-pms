<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreMheCategoryRequest;
use App\Http\Requests\UpdateMheCategoryRequest;
use App\Models\MheCategory;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheCategoryController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(MheCategory::class, 'mhe_category');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'mhe-categories', ['sort' => 'code', 'direction' => 'asc']);
        $mheCategories = $this->paginateList($this->applyListQuery(MheCategory::query(), $state), $state);

        return view('mhe-categories.index', compact('mheCategories', 'state'));
    }

    public function create(): View
    {
        return view('mhe-categories.form', [
            'mheCategory' => new MheCategory(['status' => RecordStatus::Active]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreMheCategoryRequest $request): RedirectResponse
    {
        $mheCategory = MheCategory::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'mhe_categories', 'create', $mheCategory->id, "Created MHE category {$mheCategory->code}.");

        return redirect()->route('mhe-categories.show', $mheCategory)->with('success', 'MHE Category created successfully.');
    }

    public function show(MheCategory $mheCategory): View
    {
        return view('mhe-categories.show', compact('mheCategory'));
    }

    public function edit(MheCategory $mheCategory): View
    {
        return view('mhe-categories.form', ['mheCategory' => $mheCategory, 'isEdit' => true]);
    }

    public function update(UpdateMheCategoryRequest $request, MheCategory $mheCategory): RedirectResponse
    {
        $mheCategory->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'mhe_categories', 'update', $mheCategory->id, "Updated MHE category {$mheCategory->code}.");

        return redirect()->route('mhe-categories.show', $mheCategory)->with('success', 'MHE Category updated successfully.');
    }

    public function destroy(Request $request, MheCategory $mheCategory): RedirectResponse
    {
        $mheCategory->update(['updated_by' => $request->user()->id]);
        $mheCategory->delete();
        $this->activityLogService->log($request->user(), 'mhe_categories', 'delete', $mheCategory->id, "Deleted MHE category {$mheCategory->code}.");

        return redirect()->route('mhe-categories.index')->with('success', 'MHE Category deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'code';
    }

    protected function sortableColumns(): array
    {
        return ['code', 'name', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['code', 'name', 'remarks'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
