<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Requests\ImportUserMigrationRequest;
use App\Http\Requests\PreviewUserMigrationRequest;
use App\Models\District;
use App\Services\FscWebImport\FscDistrictUserImportService;
use App\Services\FscWebImport\FscWebConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class UserMigrationToolController extends Controller
{
    public function index(): View
    {
        return view('system.user-migration-tool.index', $this->pageData());
    }

    public function preview(PreviewUserMigrationRequest $request, FscDistrictUserImportService $importService): View|RedirectResponse
    {
        $connection = FscWebConnection::configFromEnv();

        try {
            $pdo = FscWebConnection::connect($connection);
            $preview = $importService->preview($pdo, $request->validated('districts'), $connection);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Could not read fsc_web: '.$exception->getMessage());
        }

        return view('system.user-migration-tool.index', array_merge(
            $this->pageData(),
            ['preview' => $preview],
        ));
    }

    public function import(ImportUserMigrationRequest $request, FscDistrictUserImportService $importService): RedirectResponse
    {
        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $result = $importService->import(
                $pdo,
                $request->validated('preview_token'),
                $request->boolean('deactivate'),
                $request->user()?->id,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('system.user-migration-tool.index')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return redirect()
                ->route('system.user-migration-tool.index')
                ->with('error', 'Import failed: '.$exception->getMessage());
        }

        $message = sprintf('Imported %d user(s) into mhe-pms.', $result['users_created']);

        if ($request->boolean('deactivate')) {
            $message .= sprintf(' Deactivated %d user(s) in fsc_web.', $result['deactivated']);

            if ($result['deactivate_failed_ids'] !== []) {
                $message .= ' Failed to deactivate fsc_web ids: '.implode(', ', $result['deactivate_failed_ids']).'.';
            }
        }

        return redirect()
            ->route('system.user-migration-tool.index')
            ->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageData(): array
    {
        return [
            'districts' => District::query()
                ->where('status', RecordStatus::Active)
                ->orderBy('district_code')
                ->get(['id', 'district_code', 'district_name']),
            'allowDeactivate' => (bool) config('fsc_web_import.allow_source_deactivate'),
            'confirmationPhrase' => config('fsc_web_import.confirmation_phrase'),
            'preview' => null,
        ];
    }
}
