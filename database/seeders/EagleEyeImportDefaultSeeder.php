<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheType;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EagleEyeImportDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('eagle_eye.defaults');

        $district = District::query()->first();

        if (! $district) {
            throw new \RuntimeException('EagleEyeImportDefaultSeeder requires at least one district.');
        }

        Site::query()->updateOrCreate(
            ['site_code' => $defaults['site_code']],
            [
                'site_name' => 'Eagle Eye Unmapped Site',
                'district_id' => $district->id,
                'description' => 'Fallback site for Eagle Eye imports when a site cannot be resolved.',
                'status' => RecordStatus::Active,
            ],
        );

        MheType::query()->updateOrCreate(
            ['code' => $defaults['mhe_type_code']],
            [
                'description' => 'Eagle Eye Unknown Type',
                'status' => RecordStatus::Active,
            ],
        );

        MheCategory::query()->updateOrCreate(
            ['code' => $defaults['mhe_category_code']],
            [
                'name' => 'Eagle Eye Unknown Category',
                'remarks' => 'Fallback category for Eagle Eye imports.',
                'status' => RecordStatus::Active,
            ],
        );

        $role = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->first();

        if (! $role) {
            throw new \RuntimeException('EagleEyeImportDefaultSeeder requires FAST Administrator role.');
        }

        User::query()->updateOrCreate(
            ['email' => $defaults['import_user_email']],
            [
                'username' => 'import.system',
                'name' => 'Eagle Eye Import',
                'password' => Hash::make(str()->random(32)),
                'role_id' => $role?->id,
                'status' => UserStatus::Active,
                'is_super_admin' => true,
            ],
        );
    }
}
