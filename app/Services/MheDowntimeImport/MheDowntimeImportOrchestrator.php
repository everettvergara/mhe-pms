<?php

namespace App\Services\MheDowntimeImport;

use App\Enums\FscWebImportPhase;
use App\Enums\MheDowntimeImportSource;
use App\Models\MheDowntimeImportBatch;
use App\Models\User;
use App\Services\EagleEye\EagleEyeDowntimeImportService;
use App\Services\FscWebImport\FscWebImportProgressReporter;
use App\Services\MheDowntimeImport\Contracts\MheDowntimeSourceReader;
use App\Services\MheDowntimeImport\Sources\EagleEyeJsonSeedSourceReader;
use App\Services\MheDowntimeImport\Sources\EagleEyeMysqlSourceReader;
use App\Services\MheDowntimeImport\Sources\EagleEyeSqlDumpSourceReader;
use Database\Seeders\EagleEyeImportDefaultSeeder;
use InvalidArgumentException;

class MheDowntimeImportOrchestrator
{
    public function __construct(
        protected EagleEyeDowntimeImportService $importService,
        protected EagleEyeJsonSeedSourceReader $jsonSeedReader,
        protected EagleEyeMysqlSourceReader $mysqlSourceReader,
        protected EagleEyeSqlDumpSourceReader $sqlDumpSourceReader,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array<string, int|string>
     */
    public function run(MheDowntimeImportSource $source, array $config, array $options = [], ?User $user = null): array
    {
        if (! ($options['skip_default_seeder'] ?? false)) {
            (new EagleEyeImportDefaultSeeder)->run();
        }

        /** @var FscWebImportProgressReporter|null $progress */
        $progress = $options['progress'] ?? null;

        if ($progress && $source === MheDowntimeImportSource::EagleEyeMysql) {
            $progress->startPhase(FscWebImportPhase::ExportingDowntimes, 1, 'Exporting downtimes from Eagle Eye…');
        }

        $reader = $this->readerFor($source);
        $payload = $reader->read($config);

        if ($progress && $source === MheDowntimeImportSource::EagleEyeMysql) {
            $progress->tick(1, 'Export complete. Importing downtime records…', 1);
        }

        $dryRun = (bool) ($options['dry_run'] ?? false);

        $result = $this->importService->importFromPayload($payload->toArray(), [
            'dry_run' => $dryRun,
            'seed_path' => $payload->seedPath ?? ($config['seed_path'] ?? config('mhe_downtime_import.default_seed_path')),
            'batch_id' => $options['batch_id'] ?? null,
            'progress' => $progress,
        ]);

        if (! $dryRun && ! ($options['skip_batch'] ?? false)) {
            MheDowntimeImportBatch::query()->create([
                'batch_id' => $result['batch_id'],
                'source' => $source,
                'source_summary' => $reader->summarizeSource($config),
                'dry_run' => false,
                'downtimes' => $result['downtimes'],
                'action_plans' => $result['action_plans'],
                'downtime_attachments' => $result['downtime_attachments'],
                'action_plan_attachments' => $result['action_plan_attachments'],
                'warnings' => $result['warnings'],
                'errors' => $result['errors'],
                'created_by' => $user?->id,
            ]);
        }

        return $result;
    }

    protected function readerFor(MheDowntimeImportSource $source): MheDowntimeSourceReader
    {
        return match ($source) {
            MheDowntimeImportSource::EagleEyeJsonSeed => $this->jsonSeedReader,
            MheDowntimeImportSource::EagleEyeMysql => $this->mysqlSourceReader,
            MheDowntimeImportSource::EagleEyeSqlDump => $this->sqlDumpSourceReader,
        };
    }
}
