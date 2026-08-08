<?php

namespace App\Console\Commands;

use App\Enums\MheDowntimeImportSource;
use App\Services\MheDowntimeImport\MheDowntimeImportOrchestrator;
use Illuminate\Console\Command;

class ImportEagleEyeDowntimesCommand extends Command
{
    protected $signature = 'mhe:import-eagle-eye-downtimes
                            {--host= : Eagle Eye MySQL host}
                            {--port=3306 : Eagle Eye MySQL port}
                            {--database= : Eagle Eye database name}
                            {--username= : Eagle Eye database user}
                            {--password= : Eagle Eye database password}
                            {--sql-file= : Path to Eagle Eye SQL dump instead of live connection}
                            {--seed-path= : Import from exported JSON seed directory}
                            {--dry-run : Validate import without persisting data}';

    protected $description = 'Import MHE downtime history from Eagle Eye (alias for mhe:import-downtimes).';

    public function handle(MheDowntimeImportOrchestrator $orchestrator): int
    {
        if ($this->option('sql-file')) {
            $source = MheDowntimeImportSource::EagleEyeSqlDump;
            $config = ['sql_file' => $this->option('sql-file')];
        } elseif ($this->option('host') && $this->option('database') && $this->option('username')) {
            $source = MheDowntimeImportSource::EagleEyeMysql;
            $config = [
                'host' => $this->option('host'),
                'port' => $this->option('port'),
                'database' => $this->option('database'),
                'username' => $this->option('username'),
                'password' => $this->option('password'),
            ];
        } else {
            $source = MheDowntimeImportSource::EagleEyeJsonSeed;
            $config = [
                'seed_path' => $this->option('seed-path') ?: config('mhe_downtime_import.default_seed_path'),
            ];
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Running dry-run import...' : 'Importing Eagle Eye downtime data...');

        $result = $orchestrator->run($source, $config, ['dry_run' => $dryRun]);

        $this->table(
            ['Metric', 'Count'],
            collect($result)->map(fn ($count, $key) => [$key, $count])->values()->all(),
        );

        $this->info($dryRun ? 'Dry-run complete. No data was saved.' : 'Import complete.');

        return self::SUCCESS;
    }
}
