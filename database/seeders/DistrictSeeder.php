<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        District::query()->updateOrCreate(
            ['district_code' => 'PH'],
            [
                'district_name' => 'Philippines',
                'description' => 'Default district for all warehouses',
                'status' => RecordStatus::Active->value,
            ],
        );
    }
}
