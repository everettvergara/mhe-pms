<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SupplierSeeder::class,
            RegionSeeder::class,
            DistrictSeeder::class,
            SiteSeeder::class,
            MheTypeSeeder::class,
            MheCategorySeeder::class,
            MheInventorySeeder::class,
            ChecklistSeeder::class,
            UserSeeder::class,
        ]);
    }
}
