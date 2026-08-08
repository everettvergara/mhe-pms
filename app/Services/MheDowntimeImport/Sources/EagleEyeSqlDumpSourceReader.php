<?php

namespace App\Services\MheDowntimeImport\Sources;

use App\Enums\MheDowntimeImportSource;
use App\Services\EagleEye\EagleEyeSeedExporter;
use App\Services\MheDowntimeImport\Contracts\MheDowntimeSourceReader;
use App\Services\MheDowntimeImport\MheDowntimeImportPayload;
use RuntimeException;

class EagleEyeSqlDumpSourceReader implements MheDowntimeSourceReader
{
    public function __construct(
        protected EagleEyeSeedExporter $exporter,
        protected EagleEyeJsonSeedSourceReader $jsonSeedReader,
    ) {}

    public function source(): MheDowntimeImportSource
    {
        return MheDowntimeImportSource::EagleEyeSqlDump;
    }

    public function read(array $config): MheDowntimeImportPayload
    {
        $sqlPath = $config['sql_file'] ?? null;

        if (! $sqlPath || ! is_readable($sqlPath)) {
            throw new RuntimeException('SQL dump file is missing or not readable.');
        }

        $seedPath = $config['seed_path'] ?? $this->temporarySeedPath();

        $this->exporter->exportFromSqlDump($sqlPath, $seedPath);

        return $this->jsonSeedReader->loadFromPath($seedPath);
    }

    public function summarizeSource(array $config): string
    {
        $sqlPath = $config['sql_file'] ?? 'unknown';

        return 'SQL dump: '.basename((string) $sqlPath);
    }

    protected function temporarySeedPath(): string
    {
        $path = storage_path('app/'.config('mhe_downtime_import.upload_directory').'/'.uniqid('sql_', true));

        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Unable to create temporary seed directory: {$path}");
        }

        return $path;
    }
}
