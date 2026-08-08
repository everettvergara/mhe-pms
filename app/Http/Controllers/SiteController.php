<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\District;
use App\Models\Region;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected UserDataScopeService $userDataScopeService,
    ) {
        $this->authorizeResource(Site::class, 'site');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'sites', ['sort' => 'site_code', 'direction' => 'asc']);

        $query = Site::query()->with(['district', 'region']);
        $this->userDataScopeService->scopeSite($query, $request->user());

        $sites = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('sites.index', compact('sites', 'state'));
    }

    public function create(): View
    {
        return view('sites.form', [
            'site' => new Site(['status' => RecordStatus::Active]),
            'districts' => District::query()->orderBy('district_name')->get(),
            'regions' => Region::query()->orderBy('region_name')->get(),
            'isEdit' => false,
        ]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = Site::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'sites', 'create', $site->id, "Created site {$site->site_code}.");

        return redirect()->route('sites.show', $site)->with('success', 'Site created successfully.');
    }

    public function show(Site $site): View
    {
        $site->load(['district', 'region']);

        return view('sites.show', compact('site'));
    }

    public function edit(Site $site): View
    {
        return view('sites.form', [
            'site' => $site,
            'districts' => District::query()->orderBy('district_name')->get(),
            'regions' => Region::query()->orderBy('region_name')->get(),
            'isEdit' => true,
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'sites', 'update', $site->id, "Updated site {$site->site_code}.");

        return redirect()->route('sites.show', $site)->with('success', 'Site updated successfully.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $site->update(['updated_by' => $request->user()->id]);
        $site->delete();
        $this->activityLogService->log($request->user(), 'sites', 'delete', $site->id, "Deleted site {$site->site_code}.");

        return redirect()->route('sites.index')->with('success', 'Site deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'site_code';
    }

    protected function sortableColumns(): array
    {
        return ['site_code', 'site_name', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['site_code', 'site_name'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
