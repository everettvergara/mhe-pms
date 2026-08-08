<?php

namespace App\Console\Commands;

use App\Services\EagleEye\EagleEyeSeedExporter;
use Illuminate\Console\Command;
use PDO;

class ExportEagleEyeSeedCommand extends Command
{
    protected $signature = 'mhe:export-eagle-eye-seed
                            {--sql-file= : Path to Eagle Eye SQL dump}
                            {--host= : Eagle Eye MySQL host}
                            {--port=3306 : Eagle Eye MySQL port}
                            {--database= : Eagle Eye database name}
                            {--username= : Eagle Eye database user}
                            {--password= : Eagle Eye database password}
                            {--output= : Output directory (defaults to database/data/eagle_eye)}';

    protected $description = 'Export Eagle Eye downtime tables to JSON seed files for mhe-pms.';

    public function handle(EagleEyeSeedExporter $exporter): int
    {
        $output = $this->option('output') ?: config('eagle_eye.seed_path');

        if ($sqlFile = $this->option('sql-file')) {
            $counts = $exporter->exportFromSqlDump($sqlFile, $output);
        } elseif ($this->option('host') && $this->option('database') && $this->option('username')) {
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $this->option('host'),
                    $this->option('port'),
                    $this->option('database'),
                ),
                (string) $this->option('username'),
                (string) $this->option('password'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            $counts = $exporter->exportFromConnection($pdo, $output);
        } else {
            $this->error('Provide --sql-file or database connection options (--host, --database, --username).');

            return self::FAILURE;
        }

        $this->info("Seed files written to {$output}");
        $this->table(['Entity', 'Exported'], collect($counts)->map(fn ($count, $key) => [$key, $count])->values()->all());

        return self::SUCCESS;
    }
}
