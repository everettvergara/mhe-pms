<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\Region;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('data/sites.json')), true, 512, JSON_THROW_ON_ERROR);

        $districtIds = District::query()->pluck('id', 'district_code');
        $regionIds = Region::query()->pluck('id', 'region_code');

        foreach ($rows as $row) {
            $districtId = $districtIds[$row['district_code']] ?? null;

            if (! $districtId) {
                throw new \RuntimeException("Site {$row['site_code']} references missing district code {$row['district_code']}.");
            }

            $regionId = null;
            if (! empty($row['region_code'])) {
                $regionId = $regionIds[$row['region_code']] ?? null;
                if (! $regionId) {
                    throw new \RuntimeException("Site {$row['site_code']} references missing region code {$row['region_code']}.");
                }
            }

            Site::query()->updateOrCreate(
                ['site_code' => $row['site_code']],
                [
                    'site_name' => $row['site_name'],
                    'district_id' => $districtId,
                    'region_id' => $regionId,
                    'description' => $row['description'],
                    'status' => $row['status'] ?? RecordStatus::Active->value,
                ],
            );
        }
    }
}
