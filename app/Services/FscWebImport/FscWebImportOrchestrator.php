<?php

namespace App\Services\FscWebImport;

use App\Enums\FscWebImportPhase;
use App\Enums\MheDowntimeImportSource;
use App\Models\MheDowntimeImportBatch;
use App\Models\User;
use App\Services\MheDowntimeImport\MheDowntimeImportOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FscWebImportOrchestrator
{
    public function __construct(
        protected FscWebImportPurgeService $purgeService,
        protected FscUserImportService $userImportService,
        protected MheDowntimeImportOrchestrator $downtimeImportOrchestrator,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array<string, int|string>
     */
    public function run(
        MheDowntimeImportSource $source,
        array $config,
        array $options = [],
        ?FscWebImportProgressReporter $progress = null,
    ): array {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $importUsers = (bool) ($options['import_users'] ?? true);
        $importDowntimes = (bool) ($options['import_downtimes'] ?? true);
        $confirmed = (bool) ($options['confirmed'] ?? false);
        $manageBatch = (bool) ($options['manage_batch'] ?? true);

        if (! $dryRun && ! $confirmed) {
            throw new InvalidArgumentException(
                'Import requires confirmation phrase: '.config('fsc_web_import.confirmation_phrase'),
            );
        }

        if ($importUsers && $source !== MheDowntimeImportSource::EagleEyeMysql) {
            throw new InvalidArgumentException('User import requires the Eagle Eye live MySQL source.');
        }

        $batchId = (string) ($options['batch_id'] ?? Str::uuid());

        $result = [
            'attachments_purged' => 0,
            'action_plans_purged' => 0,
            'downtimes_purged' => 0,
            'users_purged' => 0,
            'default_users_created' => 0,
            'users' => 0,
            'users_skipped' => 0,
            'site_assignments' => 0,
            'sites_unmapped' => 0,
            'users_without_sites' => 0,
            'downtimes' => 0,
            'action_plans' => 0,
            'downtime_attachments' => 0,
            'action_plan_attachments' => 0,
            'warnings' => 0,
            'errors' => 0,
            'batch_id' => $batchId,
        ];

        $execute = function () use ($source, $config, $importUsers, $importDowntimes, $batchId, $progress, &$result): void {
            $progress?->startPhase(FscWebImportPhase::Purging, 1, 'Purging local users and downtimes…');
            $purgeCounts = $this->purgeService->purgeAndReseedDefaults();
            $result = array_merge($result, $purgeCounts);
            $progress?->tick(1, 'Purge complete.');

            if ($importUsers) {
                $pdo = FscWebConnection::connect($config);
                $userRows = $this->userImportService->fetchMheUsersForImport($pdo);
                $progress?->startPhase(
                    FscWebImportPhase::ImportingUsers,
                    count($userRows),
                    'Importing users from Eagle Eye…',
                );
                $userCounts = $this->userImportService->import($pdo, $progress);
                $result['users'] = $userCounts['users'];
                $result['users_skipped'] = $userCounts['users_skipped'];
                $result['site_assignments'] = $userCounts['site_assignments'];
                $result['sites_unmapped'] = $userCounts['sites_unmapped'];
                $result['users_without_sites'] = $userCounts['users_without_sites'];
            }

            if ($importDowntimes) {
                $downtimeResult = $this->downtimeImportOrchestrator->run(
                    $source,
                    $config,
                    [
                        'dry_run' => false,
                        'batch_id' => $batchId,
                        'skip_batch' => true,
                        'skip_default_seeder' => true,
                        'progress' => $progress,
                    ],
                );

                foreach ([
                    'downtimes',
                    'action_plans',
                    'downtime_attachments',
                    'action_plan_attachments',
                    'warnings',
                    'errors',
                ] as $key) {
                    $result[$key] = $downtimeResult[$key] ?? $result[$key];
                }
            }

            $progress?->startPhase(FscWebImportPhase::Finalizing, 1, 'Finalizing import…');
            $progress?->tick(1, 'Finalizing import…');
        };

        if ($dryRun) {
            DB::beginTransaction();

            try {
                $execute();
            } finally {
                DB::rollBack();
            }

            $result['batch_id'] = 'dry-run';
        } else {
            DB::transaction($execute);

            if ($manageBatch) {
                MheDowntimeImportBatch::query()->create([
                    'batch_id' => $batchId,
                    'source' => $source,
                    'source_summary' => $this->summarizeSource($source, $config, $importUsers, $importDowntimes),
                    'dry_run' => false,
                    'downtimes' => $result['downtimes'],
                    'action_plans' => $result['action_plans'],
                    'downtime_attachments' => $result['downtime_attachments'],
                    'action_plan_attachments' => $result['action_plan_attachments'],
                    'warnings' => $result['warnings'],
                    'errors' => $result['errors'],
                    'users' => $result['users'],
                    'users_purged' => $result['users_purged'],
                    'downtimes_purged' => $result['downtimes_purged'],
                    'action_plans_purged' => $result['action_plans_purged'],
                    'created_by' => $options['created_by'] ?? null,
                ]);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function summarizeSource(
        MheDowntimeImportSource $source,
        array $config,
        bool $importUsers,
        bool $importDowntimes,
    ): string {
        $parts = [];

        if ($importUsers) {
            $parts[] = 'users';
        }

        if ($importDowntimes) {
            $parts[] = 'downtimes';
        }

        $scope = $parts === [] ? 'purge-only' : implode('+', $parts);

        return match ($source) {
            MheDowntimeImportSource::EagleEyeMysql => sprintf(
                'FSC Web MySQL %s/%s (%s)',
                $config['host'] ?? 'unknown',
                $config['database'] ?? 'unknown',
                $scope,
            ),
            MheDowntimeImportSource::EagleEyeJsonSeed => sprintf(
                'JSON seed %s (%s)',
                $config['seed_path'] ?? config('mhe_downtime_import.default_seed_path'),
                $scope,
            ),
            MheDowntimeImportSource::EagleEyeSqlDump => sprintf(
                'SQL dump %s (%s)',
                $config['sql_file'] ?? 'unknown',
                $scope,
            ),
        };
    }
}
