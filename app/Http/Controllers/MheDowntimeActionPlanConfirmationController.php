<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmMheDowntimeActionPlanRequest;
use App\Http\Requests\RejectActionPlanRequest;
use App\Models\MheDowntimeActionPlan;
use App\Services\MheDowntimeActionPlanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class MheDowntimeActionPlanConfirmationController extends Controller
{
    public function __construct(
        protected MheDowntimeActionPlanService $actionPlanService,
    ) {}

    public function show(MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        if (! auth()->user()->isFastAdmin()) {
            abort(403);
        }

        $this->authorize('view', $actionPlan);

        return $this->redirectToParent($actionPlan);
    }

    public function confirm(ConfirmMheDowntimeActionPlanRequest $request, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('confirm', $actionPlan);

        try {
            $this->actionPlanService->confirm(
                $request->user(),
                $actionPlan,
                Carbon::parse($request->validated('date_implemented')),
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToParent($actionPlan)->with('success', 'Action item confirmed successfully.');
    }

    public function reject(RejectActionPlanRequest $request, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('reject', $actionPlan);

        try {
            $this->actionPlanService->reject($request->user(), $actionPlan, $request->validated('rejection_remarks'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToParent($actionPlan)->with('success', 'Action item rejected.');
    }

    protected function redirectToParent(MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $url = $actionPlan->parentShowUrl();
        abort_if($url === null, 404);

        return redirect()->to($url);
    }
}
