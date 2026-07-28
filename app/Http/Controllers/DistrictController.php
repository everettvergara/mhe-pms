<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreDistrictRequest;
use App\Http\Requests\UpdateDistrictRequest;
use App\Models\District;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistrictController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(District::class, 'district');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'districts', ['sort' => 'district_code', 'direction' => 'asc']);
        $districts = $this->paginateList($this->applyListQuery(District::query(), $state), $state);

        return view('districts.index', compact('districts', 'state'));
    }

    public function create(): View
    {
        return view('districts.form', [
            'district' => new District(['status' => RecordStatus::Active]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreDistrictRequest $request): RedirectResponse
    {
        $district = District::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'districts', 'create', $district->id, "Created district {$district->district_code}.");

        return redirect()->route('districts.show', $district)->with('success', 'District created successfully.');
    }

    public function show(District $district): View
    {
        return view('districts.show', compact('district'));
    }

    public function edit(District $district): View
    {
        return view('districts.form', ['district' => $district, 'isEdit' => true]);
    }

    public function update(UpdateDistrictRequest $request, District $district): RedirectResponse
    {
        $district->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'districts', 'update', $district->id, "Updated district {$district->district_code}.");

        return redirect()->route('districts.show', $district)->with('success', 'District updated successfully.');
    }

    public function destroy(Request $request, District $district): RedirectResponse
    {
        $district->update(['updated_by' => $request->user()->id]);
        $district->delete();
        $this->activityLogService->log($request->user(), 'districts', 'delete', $district->id, "Deleted district {$district->district_code}.");

        return redirect()->route('districts.index')->with('success', 'District deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'district_code';
    }

    protected function sortableColumns(): array
    {
        return ['district_code', 'district_name', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['district_code', 'district_name'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
