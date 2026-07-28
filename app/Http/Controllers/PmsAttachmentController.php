<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePmsAttachmentRequest;
use App\Models\Attachment;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use RuntimeException;

class PmsAttachmentController extends Controller
{
    public function __construct(
        protected AttachmentService $attachmentService,
    ) {}

    public function storeForPms(StorePmsAttachmentRequest $request, PmsHeader $pms): RedirectResponse
    {
        $this->authorize('update', $pms);

        try {
            $files = $request->file('files', []);
            $this->attachmentService->storeMany(
                $pms,
                $request->user(),
                is_array($files) ? $files : [$files],
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        $count = count($request->file('files', []));
        $message = $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.";

        return back()->with('success', $message);
    }

    public function storeForDetail(StorePmsAttachmentRequest $request, PmsDetail $pmsDetail): RedirectResponse
    {
        $pmsDetail->loadMissing('pmsHeader');
        $this->authorize('update', $pmsDetail->pmsHeader);

        try {
            $files = $request->file('files', []);
            $this->attachmentService->storeMany(
                $pmsDetail,
                $request->user(),
                is_array($files) ? $files : [$files],
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        $count = count($request->file('files', []));
        $message = $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.";

        return back()->with('success', $message);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        try {
            $this->attachmentService->destroy($attachment, request()->user());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        return back()->with('success', 'Photo removed.');
    }
}
