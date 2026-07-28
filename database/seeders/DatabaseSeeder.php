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
            DistrictSeeder::class,
            SiteSeeder::class,
            MheTypeSeeder::class,
            ChecklistSeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
