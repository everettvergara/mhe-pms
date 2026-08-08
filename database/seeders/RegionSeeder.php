<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('data/regions.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            Region::query()->updateOrCreate(
                ['region_code' => $row['region_code']],
                [
                    'region_name' => $row['region_name'],
                    'description' => $row['description'],
                    'status' => $row['status'] ?? RecordStatus::Active->value,
                ],
            );
        }
    }
}
