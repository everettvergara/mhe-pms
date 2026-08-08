<?php

namespace App\Http\Controllers;

use App\Enums\ProgressStatus;
use App\Http\Requests\StoreMheDowntimeActionPlanCommentRequest;
use App\Http\Requests\StoreMheDowntimeActionPlanRequest;
use App\Http\Requests\UpdateMheDowntimeActionPlanRequest;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Services\MheDowntimeActionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MheDowntimeActionPlanController extends Controller
{
    public function __construct(
        protected MheDowntimeActionPlanService $actionPlanService,
    ) {}

    public function store(StoreMheDowntimeActionPlanRequest $request, MheDowntime $mheDowntime): RedirectResponse|JsonResponse
    {
        $this->authorize('create', MheDowntimeActionPlan::class);

        try {
            $this->actionPlanService->create($mheDowntime, $request->user(), $request->validated());
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action item created successfully.']);
        }

        return redirect()->route('mhe-downtimes.show', $mheDowntime)->with('success', 'Action item created successfully.');
    }

    public function update(UpdateMheDowntimeActionPlanRequest $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse|JsonResponse
    {
        $this->ensureBelongsToDowntime($mheDowntime, $actionPlan);

        try {
            $this->actionPlanService->update($actionPlan, $request->user(), $request->validated());
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action item updated successfully.']);
        }

        return $this->redirectToContext($mheDowntime, $request)->with('success', 'Action item updated successfully.');
    }

    public function comment(StoreMheDowntimeActionPlanCommentRequest $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->ensureBelongsToDowntime($mheDowntime, $actionPlan);
        $this->authorize('comment', $actionPlan);

        $this->actionPlanService->addComment(
            $request->user(),
            $actionPlan,
            $request->validated('comment'),
            ProgressStatus::from($request->validated('progress_status')),
        );

        return $this->redirectToContext($mheDowntime, $request)->with('success', 'Comment added successfully.');
    }

    public function markImplemented(Request $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->ensureBelongsToDowntime($mheDowntime, $actionPlan);
        $this->authorize('markImplemented', $actionPlan);

        try {
            $this->actionPlanService->markImplemented($actionPlan, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToContext($mheDowntime, $request)->with('success', 'Action item marked as implemented.');
    }

    public function cancel(Request $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        $this->ensureBelongsToDowntime($mheDowntime, $actionPlan);
        $this->authorize('cancel', $actionPlan);

        try {
            $this->actionPlanService->cancel($actionPlan, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToContext($mheDowntime, $request)->with('success', 'Action item cancelled successfully.');
    }

    public function destroy(Request $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse|JsonResponse
    {
        $this->ensureBelongsToDowntime($mheDowntime, $actionPlan);
        $this->authorize('delete', $actionPlan);

        try {
            $this->actionPlanService->delete($actionPlan, $request->user());
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action item deleted successfully.']);
        }

        return redirect()->route('mhe-downtimes.show', $mheDowntime)->with('success', 'Action item deleted successfully.');
    }

    protected function ensureBelongsToDowntime(MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): void
    {
        if ($actionPlan->mhe_downtime_id !== $mheDowntime->id) {
            abort(404);
        }
    }

    protected function redirectToContext(MheDowntime $mheDowntime, Request $request): RedirectResponse
    {
        if ($request->input('return_to') === 'downtime') {
            return redirect()->route('mhe-downtimes.show', $mheDowntime);
        }

        return redirect()->route('mhe-downtimes.show', $mheDowntime);
    }
}
