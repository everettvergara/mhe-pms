<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $district = District::query()->where('district_code', 'PH')->first();

        $sites = [
            ['site_code' => 'MNL', 'site_name' => 'FAST Manila', 'description' => 'Manila warehouse'],
            ['site_code' => 'LAG', 'site_name' => 'FAST Laguna', 'description' => 'Laguna warehouse'],
            ['site_code' => 'CEB', 'site_name' => 'FAST Cebu', 'description' => 'Cebu warehouse'],
            ['site_code' => 'CLK', 'site_name' => 'FAST Clark', 'description' => 'Clark warehouse'],
            ['site_code' => 'DVO', 'site_name' => 'FAST Davao', 'description' => 'Davao warehouse'],
        ];

        foreach ($sites as $site) {
            Site::query()->updateOrCreate(
                ['site_code' => $site['site_code']],
                array_merge($site, [
                    'district_id' => $district?->id,
                    'status' => RecordStatus::Active->value,
                ]),
            );
        }
    }
}
