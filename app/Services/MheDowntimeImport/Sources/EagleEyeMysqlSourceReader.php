<?php

namespace App\Services\MheDowntimeImport\Sources;

use App\Enums\MheDowntimeImportSource;
use App\Services\EagleEye\EagleEyeSeedExporter;
use App\Services\MheDowntimeImport\Contracts\MheDowntimeSourceReader;
use App\Services\MheDowntimeImport\MheDowntimeImportPayload;
use PDO;
use RuntimeException;

class EagleEyeMysqlSourceReader implements MheDowntimeSourceReader
{
    public function __construct(
        protected EagleEyeSeedExporter $exporter,
        protected EagleEyeJsonSeedSourceReader $jsonSeedReader,
    ) {}

    public function source(): MheDowntimeImportSource
    {
        return MheDowntimeImportSource::EagleEyeMysql;
    }

    public function read(array $config): MheDowntimeImportPayload
    {
        $seedPath = $config['seed_path'] ?? $this->temporarySeedPath();

        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database'],
            ),
            (string) $config['username'],
            (string) ($config['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $this->exporter->exportFromConnection($pdo, $seedPath);

        return $this->jsonSeedReader->loadFromPath($seedPath);
    }

    public function summarizeSource(array $config): string
    {
        $host = $config['host'] ?? 'unknown';
        $database = $config['database'] ?? 'unknown';

        return "MySQL {$host}/{$database}";
    }

    protected function temporarySeedPath(): string
    {
        $path = storage_path('app/'.config('mhe_downtime_import.upload_directory').'/'.uniqid('mysql_', true));

        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Unable to create temporary seed directory: {$path}");
        }

        return $path;
    }
}
