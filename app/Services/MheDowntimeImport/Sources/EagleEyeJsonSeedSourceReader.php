<?php

namespace App\Services\MheDowntimeImport\Sources;

use App\Enums\MheDowntimeImportSource;
use App\Services\EagleEye\EagleEyeSeedExporter;
use App\Services\MheDowntimeImport\Contracts\MheDowntimeSourceReader;
use App\Services\MheDowntimeImport\MheDowntimeImportPayload;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EagleEyeJsonSeedSourceReader implements MheDowntimeSourceReader
{
    public function source(): MheDowntimeImportSource
    {
        return MheDowntimeImportSource::EagleEyeJsonSeed;
    }

    public function read(array $config): MheDowntimeImportPayload
    {
        $seedPath = $config['seed_path'] ?? config('mhe_downtime_import.default_seed_path');

        return $this->loadFromPath($seedPath);
    }

    public function summarizeSource(array $config): string
    {
        $seedPath = $config['seed_path'] ?? config('mhe_downtime_import.default_seed_path');

        return "JSON seed: {$seedPath}";
    }

    public function loadFromPath(string $seedPath): MheDowntimeImportPayload
    {
        $this->assertSeedFilesExist($seedPath);

        return new MheDowntimeImportPayload(
            downtimes: json_decode(File::get("{$seedPath}/downtimes.json"), true, 512, JSON_THROW_ON_ERROR),
            actionPlans: json_decode(File::get("{$seedPath}/action_plans.json"), true, 512, JSON_THROW_ON_ERROR),
            downtimeAttachments: json_decode(File::get("{$seedPath}/downtime_attachments.json"), true, 512, JSON_THROW_ON_ERROR),
            actionPlanAttachments: json_decode(File::get("{$seedPath}/action_plan_attachments.json"), true, 512, JSON_THROW_ON_ERROR),
            seedPath: $seedPath,
        );
    }

    protected function assertSeedFilesExist(string $seedPath): void
    {
        foreach (['downtimes.json', 'action_plans.json', 'downtime_attachments.json', 'action_plan_attachments.json'] as $file) {
            if (! File::exists("{$seedPath}/{$file}")) {
                throw new RuntimeException("Missing seed file: {$seedPath}/{$file}");
            }
        }
    }
}
