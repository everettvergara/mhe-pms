<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Models\Region;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegionController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(Region::class, 'region');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'regions', ['sort' => 'region_code', 'direction' => 'asc']);
        $regions = $this->paginateList($this->applyListQuery(Region::query(), $state), $state);

        return view('regions.index', compact('regions', 'state'));
    }

    public function create(): View
    {
        return view('regions.form', [
            'region' => new Region(['status' => RecordStatus::Active]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreRegionRequest $request): RedirectResponse
    {
        $region = Region::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'regions', 'create', $region->id, "Created region {$region->region_code}.");

        return redirect()->route('regions.show', $region)->with('success', 'Region created successfully.');
    }

    public function show(Region $region): View
    {
        return view('regions.show', compact('region'));
    }

    public function edit(Region $region): View
    {
        return view('regions.form', ['region' => $region, 'isEdit' => true]);
    }

    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse
    {
        $region->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'regions', 'update', $region->id, "Updated region {$region->region_code}.");

        return redirect()->route('regions.show', $region)->with('success', 'Region updated successfully.');
    }

    public function destroy(Request $request, Region $region): RedirectResponse
    {
        $region->update(['updated_by' => $request->user()->id]);
        $region->delete();
        $this->activityLogService->log($request->user(), 'regions', 'delete', $region->id, "Deleted region {$region->region_code}.");

        return redirect()->route('regions.index')->with('success', 'Region deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'region_code';
    }

    protected function sortableColumns(): array
    {
        return ['region_code', 'region_name', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['region_code', 'region_name'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
