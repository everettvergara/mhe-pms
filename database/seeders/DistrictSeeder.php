<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('data/districts.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            District::query()->updateOrCreate(
                ['district_code' => $row['district_code']],
                [
                    'district_name' => $row['district_name'],
                    'description' => $row['description'],
                    'status' => $row['status'] ?? RecordStatus::Active->value,
                ],
            );
        }
    }
}
