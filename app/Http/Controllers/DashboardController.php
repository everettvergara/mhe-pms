<?php

namespace App\Http\Controllers;

use App\Http\Requests\PmsScheduleReportRequest;
use App\Services\DashboardService;
use App\Services\PmsScheduleReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected PmsScheduleReportService $pmsScheduleReportService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSupplier()) {
            return view('dashboard.supplier', [
                'data' => $this->dashboardService->forSupplier($user),
            ]);
        }

        return view('dashboard.admin', [
            'data' => $this->dashboardService->forAdmin($user),
        ]);
    }

    public function pmsSchedule(PmsScheduleReportRequest $request): View
    {
        $user = $request->user();
        $filters = $request->filters();
        $options = $this->pmsScheduleReportService->options($user);

        return view('dashboard.pms-schedule', [
            'filters' => $filters,
            'districts' => $options['districts'],
            'sites' => $options['sites'],
            'years' => $options['years'],
            'months' => $options['months'],
            'groups' => $this->pmsScheduleReportService->groups($user, $filters),
        ]);
    }

    public function units(Request $request): View
    {
        return view('dashboard.supplier-units', [
            'data' => $this->dashboardService->supplierUnits($request->user()),
        ]);
    }
}
