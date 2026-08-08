<?php

namespace App\Http\Controllers;

use App\Http\Requests\MheUptimeDashboardRequest;
use App\Models\District;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\MheUptimeDashboardService;
use App\Services\UserDataScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheUptimeDashboardController extends Controller
{
    public function __construct(
        protected MheUptimeDashboardService $dashboardService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function index(MheUptimeDashboardRequest $request): View
    {
        $filters = $request->validated();
        $data = $this->dashboardService->build($request->user(), $filters);

        return view('dashboard.mhe-uptime', [
            'filters' => $filters,
            'data' => $data,
            'districts' => District::query()->orderBy('district_name')->get(),
            'sites' => $this->sitesForUser($request),
            'suppliers' => $this->suppliersForUser($request),
            'mheTypes' => MheType::query()->orderBy('description')->get(),
            'chartColors' => ['#005BAC', '#4F9DDA', '#F58220', '#198754', '#6c757d', '#6610f2', '#dc3545', '#0dcaf0'],
        ]);
    }

    protected function sitesForUser(Request $request)
    {
        $query = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSite($query, $request->user());

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }

        return $query->get();
    }

    protected function suppliersForUser(Request $request)
    {
        $query = Supplier::query()->orderBy('supplier_name');
        $this->userDataScopeService->scopeSupplier($query, $request->user());

        return $query->get();
    }
}
