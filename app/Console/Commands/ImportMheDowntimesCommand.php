<?php

namespace App\Console\Commands;

use App\Enums\MheDowntimeImportSource;
use App\Services\MheDowntimeImport\MheDowntimeImportOrchestrator;
use Illuminate\Console\Command;

class ImportMheDowntimesCommand extends Command
{
    protected $signature = 'mhe:import-downtimes
                            {--source=eagle_eye_json_seed : Import source (eagle_eye_json_seed, eagle_eye_mysql, eagle_eye_sql_dump)}
                            {--host= : Eagle Eye MySQL host}
                            {--port=3306 : Eagle Eye MySQL port}
                            {--database= : Eagle Eye database name}
                            {--username= : Eagle Eye database user}
                            {--password= : Eagle Eye database password}
                            {--sql-file= : Path to Eagle Eye SQL dump}
                            {--seed-path= : JSON seed directory}
                            {--dry-run : Validate import without persisting data}';

    protected $description = 'Copy MHE downtime records from an external source into mhe-pms.';

    public function handle(MheDowntimeImportOrchestrator $orchestrator): int
    {
        $source = MheDowntimeImportSource::from($this->option('source'));
        $dryRun = (bool) $this->option('dry-run');

        $config = match ($source) {
            MheDowntimeImportSource::EagleEyeJsonSeed => [
                'seed_path' => $this->option('seed-path') ?: config('mhe_downtime_import.default_seed_path'),
            ],
            MheDowntimeImportSource::EagleEyeMysql => [
                'host' => $this->option('host'),
                'port' => $this->option('port'),
                'database' => $this->option('database'),
                'username' => $this->option('username'),
                'password' => $this->option('password'),
            ],
            MheDowntimeImportSource::EagleEyeSqlDump => [
                'sql_file' => $this->option('sql-file'),
            ],
        };

        if ($source === MheDowntimeImportSource::EagleEyeMysql && (! $config['host'] || ! $config['database'] || ! $config['username'])) {
            $this->error('MySQL source requires --host, --database, and --username.');

            return self::FAILURE;
        }

        if ($source === MheDowntimeImportSource::EagleEyeSqlDump && ! $config['sql_file']) {
            $this->error('SQL dump source requires --sql-file.');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Running dry-run import...' : 'Importing MHE downtime data...');

        $result = $orchestrator->run($source, $config, ['dry_run' => $dryRun]);

        $this->table(
            ['Metric', 'Count'],
            collect($result)->map(fn ($count, $key) => [$key, $count])->values()->all(),
        );

        $this->info($dryRun ? 'Dry-run complete. No data was saved.' : 'Import complete.');

        return self::SUCCESS;
    }
}
