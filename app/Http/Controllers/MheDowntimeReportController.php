<?php

namespace App\Http\Controllers;

use App\Http\Requests\MheDowntimeSummaryActionPlanRequest;
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
        $validated = $request->validated();
        $filters = [
            'as_of_date' => $validated['as_of_date'],
            'district_id' => $validated['district_id'] ?? null,
            'site_id' => $validated['site_id'] ?? null,
        ];
        $actionPlanFilters = [
            'date_from' => $validated['ap_date_from'],
            'date_to' => $validated['ap_date_to'],
            'district_id' => $validated['ap_district_id'] ?? null,
            'site_id' => $validated['ap_site_id'] ?? null,
            'is_pending' => (bool) $validated['ap_is_pending'],
            'is_implemented' => (bool) $validated['ap_is_implemented'],
            'is_no_action_plan' => (bool) $validated['ap_is_no_action_plan'],
        ];
        $charts = $this->reportService->summaryChartData($request->user(), $filters);
        $sites = $this->sitesForUser($request);

        return view('mhe-reports.summary', [
            'filters' => $filters,
            'charts' => $charts,
            'districts' => District::query()->orderBy('district_name')->get(),
            'sites' => $sites,
            'actionPlanSites' => $sites,
            'actionPlanFilters' => $actionPlanFilters,
            'actionPlanGroups' => $this->reportService->actionPlanGroups($request->user(), $actionPlanFilters),
        ]);
    }

    public function summaryActionPlans(MheDowntimeSummaryActionPlanRequest $request): View
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
        $utilization = $this->reportService->utilizationBubbles($request->user(), $filters);

        return view('mhe-reports.utilization', [
            'filters' => $filters,
            'utilization' => $utilization,
            'districts' => District::query()->orderBy('district_name')->get(),
            'sites' => $this->sitesForUser($request),
        ]);
    }

    protected function sitesForUser(Request $request)
    {
        $query = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSite($query, $request->user());

        return $query->get();
    }
}
