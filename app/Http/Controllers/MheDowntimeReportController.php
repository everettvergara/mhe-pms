<?php

namespace App\Http\Controllers;

use App\Http\Requests\MheDowntimeSummaryReportRequest;
use App\Http\Requests\MheDowntimeUtilizationReportRequest;
use App\Models\District;
use App\Models\Site;
use App\Services\MheDowntimeReportService;
use App\Services\UserDataScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheDowntimeReportController extends Controller
{
    public function __construct(
        protected MheDowntimeReportService $reportService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    public function summary(MheDowntimeSummaryReportRequest $request): View
    {
        $filters = $request->validated();
        $charts = $this->reportService->summaryChartData($request->user(), $filters);

        return view('mhe-reports.summary', [
            'filters' => $filters,
            'charts' => $charts,
            'districts' => District::query()->orderBy('district_name')->get(),
            'sites' => $this->sitesForUser($request),
        ]);
    }

    public function summaryActionPlans(MheDowntimeSummaryReportRequest $request): View
    {
        $filters = $request->validated();
        $groups = $this->reportService->actionPlanGroups($request->user(), $filters);

        return view('mhe-reports.partials.action-plans', [
            'groups' => $groups,
            'filters' => $filters,
        ]);
    }

    public function utilization(MheDowntimeUtilizationReportRequest $request): View
    {
        $filters = $request->validated();
        $pivot = $this->reportService->utilizationPivot($request->user(), $filters);

        return view('mhe-reports.utilization', [
            'filters' => $filters,
            'pivot' => $pivot,
            'districts' => District::query()->orderBy('district_name')->get(),
            'sites' => $this->sitesForUser($request),
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
}
