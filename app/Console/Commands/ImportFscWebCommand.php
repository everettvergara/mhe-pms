<?php

namespace App\Console\Commands;

use App\Enums\MheDowntimeImportSource;
use App\Services\FscWebImport\FscWebConnection;
use App\Services\FscWebImport\FscWebImportOrchestrator;
use Illuminate\Console\Command;

class ImportFscWebCommand extends Command
{
    protected $signature = 'fsc:import
                            {--source=eagle_eye_mysql : Import source (eagle_eye_mysql, eagle_eye_json_seed, eagle_eye_sql_dump)}
                            {--host= : FSC Web MySQL host}
                            {--port=3306 : FSC Web MySQL port}
                            {--database= : FSC Web database name}
                            {--username= : FSC Web database user}
                            {--password= : FSC Web database password}
                            {--seed-path= : JSON seed directory}
                            {--sql-file= : Path to SQL dump}
                            {--users : Import MHE-access users from FSC Web}
                            {--downtimes : Import MHE downtimes and action plans}
                            {--dry-run : Validate import without persisting data}
                            {--confirm : Required for live import; acknowledges destructive purge}';

    protected $description = 'Purge local MHE users/downtimes, reseed defaults, and import from fsc_web.';

    public function handle(FscWebImportOrchestrator $orchestrator): int
    {
        $source = MheDowntimeImportSource::from($this->option('source'));
        $dryRun = (bool) $this->option('dry-run');
        $importUsers = (bool) $this->option('users');
        $importDowntimes = (bool) $this->option('downtimes');

        if (! $importUsers && ! $importDowntimes) {
            $importUsers = true;
            $importDowntimes = true;
        }

        if (! $dryRun && ! $this->option('confirm')) {
            $phrase = config('fsc_web_import.confirmation_phrase');
            $this->error("Live import is destructive. Re-run with --confirm after reviewing the purge scope.");
            $this->line("You must understand this will delete all users, MHE downtimes, and MHE action plans, then recreate default users (admin/password, etc.).");
            $this->line("Confirmation phrase: {$phrase}");

            return self::FAILURE;
        }

        $config = $this->resolveConfig($source);

        if ($source === MheDowntimeImportSource::EagleEyeMysql && (! $config['host'] || ! $config['database'] || ! $config['username'])) {
            $this->error('MySQL source requires --host, --database, and --username (or FSC_WEB_DB_* env vars).');

            return self::FAILURE;
        }

        if ($source === MheDowntimeImportSource::EagleEyeSqlDump && empty($config['sql_file'])) {
            $this->error('SQL dump source requires --sql-file.');

            return self::FAILURE;
        }

        $this->warn($dryRun ? 'Running dry-run FSC Web import...' : 'Running destructive FSC Web import...');

        try {
            $result = $orchestrator->run(
                $source,
                $config,
                [
                    'dry_run' => $dryRun,
                    'import_users' => $importUsers,
                    'import_downtimes' => $importDowntimes,
                    'confirmed' => (bool) $this->option('confirm'),
                ],
            );
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            collect($result)->map(fn ($count, $key) => [$key, $count])->values()->all(),
        );

        $this->info($dryRun ? 'Dry-run complete. No data was saved.' : 'FSC Web import complete.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(MheDowntimeImportSource $source): array
    {
        $envDefaults = FscWebConnection::configFromEnv();

        return match ($source) {
            MheDowntimeImportSource::EagleEyeJsonSeed => [
                'seed_path' => $this->option('seed-path') ?: config('mhe_downtime_import.default_seed_path'),
            ],
            MheDowntimeImportSource::EagleEyeMysql => [
                'host' => $this->option('host') ?: $envDefaults['host'] ?? null,
                'port' => $this->option('port') ?: $envDefaults['port'] ?? 3306,
                'database' => $this->option('database') ?: $envDefaults['database'] ?? null,
                'username' => $this->option('username') ?: $envDefaults['username'] ?? null,
                'password' => $this->option('password') ?? $envDefaults['password'] ?? '',
            ],
            MheDowntimeImportSource::EagleEyeSqlDump => [
                'sql_file' => $this->option('sql-file'),
            ],
        };
    }
}
