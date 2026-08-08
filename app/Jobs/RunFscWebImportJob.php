<?php

namespace App\Jobs;

use App\Enums\MheDowntimeImportSource;
use App\Models\MheDowntimeImportBatch;
use App\Services\FscWebImport\FscWebImportOrchestrator;
use App\Services\FscWebImport\FscWebImportProgressReporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunFscWebImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $batchId,
        public array $config,
        public array $options,
        public ?int $userId = null,
    ) {}

    public function handle(FscWebImportOrchestrator $orchestrator): void
    {
        $batch = MheDowntimeImportBatch::query()
            ->where('batch_id', $this->batchId)
            ->firstOrFail();

        $progress = FscWebImportProgressReporter::forOptions(
            $this->batchId,
            (bool) ($this->options['import_users'] ?? true),
            (bool) ($this->options['import_downtimes'] ?? true),
        );

        try {
            $progress->markRunning();

            $result = $orchestrator->run(
                MheDowntimeImportSource::EagleEyeMysql,
                $this->config,
                array_merge($this->options, [
                    'batch_id' => $this->batchId,
                    'manage_batch' => false,
                ]),
                $progress,
            );

            $result['batch_id'] = $this->batchId;
            $progress->complete($result);
        } catch (Throwable $exception) {
            Log::error('FSC Web import failed', [
                'batch_id' => $this->batchId,
                'message' => $exception->getMessage(),
            ]);

            $progress->fail($exception->getMessage());

            throw $exception;
        }
    }
}
