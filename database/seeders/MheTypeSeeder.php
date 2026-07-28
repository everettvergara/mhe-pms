<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\MheType;
use Illuminate\Database\Seeder;

class MheTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'FL', 'description' => 'Forklift'],
            ['code' => 'RT', 'description' => 'Reach Truck'],
            ['code' => 'PT', 'description' => 'Pallet Truck'],
            ['code' => 'ES', 'description' => 'Electric Stacker'],
        ];

        foreach ($types as $type) {
            MheType::query()->updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['status' => RecordStatus::Active->value]),
            );
        }
    }
}
