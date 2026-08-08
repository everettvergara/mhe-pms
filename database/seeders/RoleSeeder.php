<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->updateOrCreate(
            ['slug' => Role::SLUG_FAST_ADMINISTRATOR],
            [
                'name' => 'FAST Administrator',
                'description' => 'Full system administration',
                'is_system' => true,
            ],
        );

        $supplier = Role::query()->updateOrCreate(
            ['slug' => Role::SLUG_SUPPLIER_USER],
            [
                'name' => 'Supplier User',
                'description' => 'Supplier maintenance workflow',
                'is_system' => true,
            ],
        );

        $allPermissionIds = Permission::query()->pluck('id');
        $admin->permissions()->sync($allPermissionIds);

        $supplierSlugs = [
            'dashboard.view',
            'pms.view',
            'pms.manage',
            'action-plans.view',
            'action-plans.manage',
            'mhe-downtimes.view',
            'mhe-downtimes.manage',
            'mhe-downtimes.post',
            'reports.view',
            'profile.manage',
        ];

        $supplier->permissions()->sync(
            Permission::query()->whereIn('slug', $supplierSlugs)->pluck('id'),
        );
    }
}
