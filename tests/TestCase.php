<?php

namespace Tests;

use Database\Seeders\DistrictSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(PermissionSeeder::class)) {
            $this->seed(PermissionSeeder::class);
            $this->seed(RoleSeeder::class);
            $this->seed(RegionSeeder::class);
            $this->seed(DistrictSeeder::class);
        }
    }
}
