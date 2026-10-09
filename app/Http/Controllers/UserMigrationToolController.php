<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUserMigrationRequest;
use App\Http\Requests\PreviewUserMigrationRequest;
use App\Services\FscWebImport\FscDistrictImportToken;
use App\Services\FscWebImport\FscDistrictUserImportService;
use App\Services\FscWebImport\FscWebConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class UserMigrationToolController extends Controller
{
    public function index(FscDistrictUserImportService $importService): View
    {
        return view('system.user-migration-tool.index', $this->pageData($importService));
    }

    public function preview(PreviewUserMigrationRequest $request, FscDistrictUserImportService $importService): View|RedirectResponse
    {
        $connection = $this->connectionFromRequest($request->validated());

        try {
            $pdo = FscWebConnection::connect($connection);
            $preview = $importService->preview($pdo, $request->validated('districts'), $connection);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Could not read fsc_web: '.$exception->getMessage());
        }

        return view('system.user-migration-tool.index', array_merge(
            $this->pageData($importService, $connection),
            ['preview' => $preview],
        ));
    }

    public function import(ImportUserMigrationRequest $request, FscDistrictUserImportService $importService): RedirectResponse
    {
        try {
            $payload = FscDistrictImportToken::parse($request->validated('preview_token'));
            $pdo = FscWebConnection::connect($payload['connection'] ?? []);
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
     * @param  array<string, mixed>|null  $connection
     * @return array<string, mixed>
     */
    protected function pageData(FscDistrictUserImportService $importService, ?array $connection = null): array
    {
        $connection ??= FscWebConnection::configFromEnv();
        $districts = [];
        $connectionError = null;

        try {
            $districts = $importService->fetchDistricts(FscWebConnection::connect($connection));
        } catch (Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        return [
            'districts' => $districts,
            'connectionError' => $connectionError,
            'connection' => $connection,
            'allowDeactivate' => (bool) config('fsc_web_import.allow_source_deactivate'),
            'confirmationPhrase' => config('fsc_web_import.confirmation_phrase'),
            'preview' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function connectionFromRequest(array $input): array
    {
        $env = FscWebConnection::configFromEnv();

        return [
            'host' => ($input['host'] ?? '') !== '' ? $input['host'] : ($env['host'] ?? null),
            'port' => ($input['port'] ?? '') !== '' ? $input['port'] : ($env['port'] ?? 3306),
            'database' => ($input['database'] ?? '') !== '' ? $input['database'] : ($env['database'] ?? null),
            'username' => ($input['username'] ?? '') !== '' ? $input['username'] : ($env['username'] ?? null),
            'password' => ($input['password'] ?? '') !== '' ? $input['password'] : ($env['password'] ?? ''),
        ];
    }
}
