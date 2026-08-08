<?php



namespace App\Http\Controllers;



use App\Enums\FscWebImportPhase;
use App\Enums\FscWebImportStatus;

use App\Enums\MheDowntimeImportSource;

use App\Http\Requests\RunMheDowntimeImportRequest;

use App\Jobs\RunFscWebImportJob;

use App\Models\EagleEyeImportLog;

use App\Models\MheDowntimeImportBatch;

use App\Services\FscWebImport\FscWebConnection;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Response;

use Illuminate\Support\Str;

use Illuminate\View\View;



class MheDowntimeImportController extends Controller

{

    public function index(): View

    {

        $batches = MheDowntimeImportBatch::query()

            ->with('creator')

            ->latest()

            ->paginate(20);



        return view('mhe-downtimes.import.index', [

            'batches' => $batches,

        ]);

    }



    public function create(): View

    {

        return view('mhe-downtimes.import.create', [

            'mysqlDefaults' => FscWebConnection::configFromEnv(),

            'confirmationPhrase' => config('fsc_web_import.confirmation_phrase'),

        ]);

    }



    public function store(RunMheDowntimeImportRequest $request): RedirectResponse

    {

        $validated = $request->validated();

        $dryRun = (bool) ($validated['dry_run'] ?? false);

        $importUsers = (bool) ($validated['import_users'] ?? true);

        $importDowntimes = (bool) ($validated['import_downtimes'] ?? true);



        $config = [

            'host' => $validated['host'],

            'port' => $validated['port'] ?? 3306,

            'database' => $validated['database'],

            'username' => $validated['username'],

            'password' => $validated['password'] ?? '',

        ];



        $batchId = (string) Str::uuid();

        $scopeParts = array_filter([

            $importUsers ? 'users' : null,

            $importDowntimes ? 'downtimes' : null,

        ]);



        MheDowntimeImportBatch::query()->create([

            'batch_id' => $batchId,

            'source' => MheDowntimeImportSource::EagleEyeMysql,

            'source_summary' => sprintf(

                'FSC Web MySQL %s/%s (%s)',

                $config['host'],

                $config['database'],

                $scopeParts === [] ? 'purge-only' : implode('+', $scopeParts),

            ),

            'dry_run' => $dryRun,

            'status' => FscWebImportStatus::Pending,

            'created_by' => $request->user()->id,

        ]);



        RunFscWebImportJob::dispatch(

            $batchId,

            $config,

            [

                'dry_run' => $dryRun,

                'import_users' => $importUsers,

                'import_downtimes' => $importDowntimes,

                'confirmed' => ! $dryRun,

            ],

            $request->user()->id,

        );



        return redirect()->route('mhe-downtimes.import.progress', $batchId);

    }



    public function progress(string $batchId): View

    {

        $batch = MheDowntimeImportBatch::query()

            ->where('batch_id', $batchId)

            ->firstOrFail();



        return view('mhe-downtimes.import.progress', [

            'batch' => $batch,

        ]);

    }



    public function status(string $batchId): JsonResponse

    {

        $batch = MheDowntimeImportBatch::query()

            ->where('batch_id', $batchId)

            ->firstOrFail();



        return response()->json([

            'status' => $batch->status?->value ?? FscWebImportStatus::Pending->value,

            'phase' => $batch->phase,

            'phase_label' => $batch->phase

                ? (FscWebImportPhase::tryFrom($batch->phase)?->label() ?? $batch->phase)

                : null,

            'progress_percent' => $batch->progress_percent,

            'processed_count' => $batch->processed_count,

            'total_count' => $batch->total_count,

            'status_message' => $batch->status_message,

            'result' => $batch->result,

            'error_message' => $batch->error_message,

            'dry_run' => $batch->dry_run,

        ]);

    }



    public function downloadLog(string $batchId): Response

    {

        $lines = EagleEyeImportLog::query()

            ->where('batch_id', $batchId)

            ->orderBy('id')

            ->get()

            ->map(fn (EagleEyeImportLog $log) => sprintf(

                '[%s] %s %s legacy_id=%s %s',

                $log->created_at?->toDateTimeString(),

                strtoupper($log->level),

                $log->entity_type,

                $log->legacy_id ?? '-',

                $log->message,

            ))

            ->implode(PHP_EOL);



        return response($lines, 200, [

            'Content-Type' => 'text/plain',

            'Content-Disposition' => "attachment; filename=\"fsc-web-import-{$batchId}.log\"",

        ]);

    }

}


