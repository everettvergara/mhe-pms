<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\MheCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MheCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('data/mhe_categories.json')), true, 512, JSON_THROW_ON_ERROR);
        $codes = [];

        foreach ($rows as $row) {
            $codes[] = $row['code'];
            $category = MheCategory::withTrashed()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'remarks' => $row['remarks'],
                    'status' => RecordStatus::Active,
                ],
            );

            if ($category->trashed()) {
                $category->restore();
            }
        }

        MheCategory::query()->whereNotIn('code', $codes)->delete();
    }
}
