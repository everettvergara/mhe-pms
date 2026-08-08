<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\MheType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MheTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('data/mhe_types.json')), true, 512, JSON_THROW_ON_ERROR);
        $codes = [];

        foreach ($rows as $row) {
            $codes[] = $row['code'];

            $type = MheType::withTrashed()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'description' => $row['description'],
                    'status' => $row['status'] ?? RecordStatus::Active->value,
                ],
            );

            if ($type->trashed()) {
                $type->restore();
            }
        }

        MheType::query()
            ->whereNotIn('code', $codes)
            ->delete();
    }
}
