<?php

namespace App\Http\Controllers;

use App\Http\Requests\MheUptimeDashboardRequest;
use App\Models\District;
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
        ]);
    }

    protected function sitesForUser(Request $request)
    {
        $query = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSite($query, $request->user());

        return $query->get();
    }

    protected function suppliersForUser(Request $request)
    {
        $user = $request->user();
        $query = Supplier::query()->orderBy('supplier_name');

        if ($user->isSuperAdmin() || $user->isFastAdmin()) {
            return $query->get();
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds === []) {
            return $query->whereRaw('0 = 1')->get();
        }

        return $query->whereIn('id', $supplierIds)->get();
    }
}
