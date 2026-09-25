<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectActionPlanRequest;
use App\Models\ActionPlan;
use App\Services\ActionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActionPlanConfirmationController extends Controller
{
    public function __construct(
        protected ActionPlanService $actionPlanService,
    ) {}

    public function show(ActionPlan $actionPlan): RedirectResponse
    {
        if (! auth()->user()->isFastAdmin()) {
            abort(403);
        }

        $this->authorize('view', $actionPlan);

        return $this->redirectToParent($actionPlan);
    }

    public function confirm(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('confirm', $actionPlan);

        try {
            $this->actionPlanService->confirm($request->user(), $actionPlan);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToParent($actionPlan)->with('success', 'Action Plan confirmed successfully.');
    }

    public function reject(RejectActionPlanRequest $request, ActionPlan $actionPlan): RedirectResponse
    {
        $this->authorize('reject', $actionPlan);

        try {
            $this->actionPlanService->reject($request->user(), $actionPlan, $request->validated('rejection_remarks'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToParent($actionPlan)->with('success', 'Action Plan rejected.');
    }

    protected function redirectToParent(ActionPlan $actionPlan): RedirectResponse
    {
        $url = $actionPlan->parentShowUrl();
        abort_if($url === null, 404);

        return redirect()->to($url);
    }
}
