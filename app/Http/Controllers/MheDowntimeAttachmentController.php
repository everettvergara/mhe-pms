<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesUploadedFiles;
use App\Http\Requests\StoreMheDowntimeAttachmentRequest;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use RuntimeException;

class MheDowntimeAttachmentController extends Controller
{
    use NormalizesUploadedFiles;

    public function __construct(
        protected AttachmentService $attachmentService,
    ) {}

    public function storeForDowntime(StoreMheDowntimeAttachmentRequest $request, MheDowntime $mheDowntime): RedirectResponse
    {
        $this->authorize('update', $mheDowntime);

        $files = [];

        try {
            $files = $this->normalizeUploadedFiles($request->file('files'));

            if ($files !== []) {
                $this->attachmentService->storeMany($mheDowntime, $request->user(), $files);
            }
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        return redirect()
            ->route('mhe-downtimes.show', $mheDowntime)
            ->with('success', $this->uploadSuccessMessage(count($files)));
    }

    public function storeForActionPlan(StoreMheDowntimeAttachmentRequest $request, MheDowntime $mheDowntime, MheDowntimeActionPlan $actionPlan): RedirectResponse
    {
        if ($actionPlan->mhe_downtime_id !== $mheDowntime->id) {
            abort(404);
        }

        $this->authorize('update', $actionPlan);

        $files = [];

        try {
            $files = $this->normalizeUploadedFiles($request->file('files'));

            if ($files !== []) {
                $this->attachmentService->storeMany($actionPlan, $request->user(), $files);
            }
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        return redirect()
            ->route('mhe-downtimes.show', $mheDowntime)
            ->with('success', $this->uploadSuccessMessage(count($files)));
    }

    protected function uploadSuccessMessage(int $count): string
    {
        return $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.";
    }
}
