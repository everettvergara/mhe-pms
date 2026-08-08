<?php

namespace Database\Seeders;

use App\Enums\MheDowntimeImportSource;
use App\Services\MheDowntimeImport\MheDowntimeImportOrchestrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EagleEyeDowntimeSeeder extends Seeder
{
    public function run(): void
    {
        $seedPath = config('mhe_downtime_import.default_seed_path');

        if (! File::exists("{$seedPath}/downtimes.json")) {
            $this->command?->warn("Skipping Eagle Eye downtime seed; {$seedPath}/downtimes.json not found.");

            return;
        }

        $result = app(MheDowntimeImportOrchestrator::class)->run(
            MheDowntimeImportSource::EagleEyeJsonSeed,
            ['seed_path' => $seedPath],
        );

        $this->command?->info(sprintf(
            'Eagle Eye downtime seed imported: %d downtimes, %d action plans, %d warnings.',
            $result['downtimes'],
            $result['action_plans'],
            $result['warnings'],
        ));
    }
}
