<?php

namespace App\Http\Controllers;

use App\Http\Requests\MigrateDowntimeRequest;
use App\Http\Requests\PreviewDowntimeMigrationRequest;
use App\Services\FscWebImport\FscDowntimeMigrationService;
use App\Services\FscWebImport\FscWebConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class DowntimeMigrationToolController extends Controller
{
    public function index(): View
    {
        return view('system.downtime-migration-tool.index', $this->pageData());
    }

    public function testConnection(): RedirectResponse
    {
        $connection = FscWebConnection::configFromEnv();

        try {
            $pdo = FscWebConnection::connect($connection);
            $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
            $count = (int) $pdo->query('SELECT COUNT(*) FROM tb_sf_tr_mhe')->fetchColumn();
        } catch (Throwable $exception) {
            return back()->with('error', 'fsc_web connection failed: '.$exception->getMessage());
        }

        return back()->with(
            'success',
            sprintf(
                'Connected to fsc_web at %s:%s. Database "%s" has %d MHE downtime row(s).',
                $connection['host'] ?? '',
                $connection['port'] ?? '',
                $database,
                $count,
            ),
        );
    }

    public function sites(Request $request, FscDowntimeMigrationService $migrationService): JsonResponse
    {
        $district = trim((string) $request->query('district', ''));

        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $sites = $migrationService->fetchSites($pdo, $district);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Could not read fsc_web: '.$exception->getMessage()], 422);
        }

        return response()->json(['sites' => $sites]);
    }

    public function count(PreviewDowntimeMigrationRequest $request, FscDowntimeMigrationService $migrationService): View|RedirectResponse
    {
        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $count = $migrationService->count($pdo, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Could not read fsc_web: '.$exception->getMessage());
        }

        return view('system.downtime-migration-tool.index', array_merge($this->pageData(), [
            'filter' => $count['filter'],
            'summary' => $count['summary'],
        ]));
    }

    public function preview(PreviewDowntimeMigrationRequest $request, FscDowntimeMigrationService $migrationService): View|RedirectResponse
    {
        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $preview = $migrationService->preview(
                $pdo,
                $request->validated(),
                (string) $request->input('tab', FscDowntimeMigrationService::STATUS_READY),
                (int) $request->input('page', 1),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Could not read fsc_web: '.$exception->getMessage());
        }

        return view('system.downtime-migration-tool.index', array_merge($this->pageData(), [
            'filter' => $preview['filter'],
            'summary' => $preview['summary'],
            'preview' => $preview,
        ]));
    }

    public function migrate(MigrateDowntimeRequest $request, FscDowntimeMigrationService $migrationService): RedirectResponse
    {
        set_time_limit(0);

        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $result = $migrationService->migrate(
                $pdo,
                $request->validated('preview_token'),
                $request->selectedLegacyIds(),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('system.downtime-migration-tool.index')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return redirect()
                ->route('system.downtime-migration-tool.index')
                ->with('error', 'Migration failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('system.downtime-migration-tool.index')
            ->with('success', sprintf(
                'Migrated %d downtime(s), %d action item(s), and %d attachment record(s). Skipped %d already migrated.',
                $result['downtimes_created'],
                $result['action_plans_created'],
                $result['downtime_attachments_created'] + $result['action_plan_attachments_created'],
                $result['skipped'],
            ))
            ->with('migration_result', $result);
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageData(): array
    {
        $districts = [];
        $connectionError = null;

        try {
            $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
            $districts = app(FscDowntimeMigrationService::class)->fetchDistricts($pdo);
        } catch (Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        return [
            'districts' => $districts,
            'connectionError' => $connectionError,
            'fscConnection' => FscWebConnection::configFromEnv(),
            'confirmationPhrase' => config('fsc_web_import.confirmation_phrase'),
            'maxRows' => (int) config('fsc_web_import.downtime_max_rows_per_run', 2000),
            'filter' => null,
            'summary' => null,
            'preview' => null,
            'legacyStatuses' => [
                1 => 'New',
                2 => 'Posted',
                3 => 'Cancelled',
                4 => 'For Approval',
            ],
        ];
    }
}
