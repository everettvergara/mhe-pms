<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['module' => 'Dashboard', 'action' => 'view', 'slug' => 'dashboard.view', 'description' => 'View dashboard'],
            ['module' => 'Suppliers', 'action' => 'view', 'slug' => 'suppliers.view', 'description' => 'View suppliers'],
            ['module' => 'Suppliers', 'action' => 'manage', 'slug' => 'suppliers.manage', 'description' => 'Manage suppliers'],
            ['module' => 'Districts', 'action' => 'view', 'slug' => 'districts.view', 'description' => 'View districts'],
            ['module' => 'Districts', 'action' => 'manage', 'slug' => 'districts.manage', 'description' => 'Manage districts'],
            ['module' => 'Sites', 'action' => 'view', 'slug' => 'sites.view', 'description' => 'View sites'],
            ['module' => 'Sites', 'action' => 'manage', 'slug' => 'sites.manage', 'description' => 'Manage sites'],
            ['module' => 'MHE Types', 'action' => 'view', 'slug' => 'mhe-types.view', 'description' => 'View MHE types'],
            ['module' => 'MHE Types', 'action' => 'manage', 'slug' => 'mhe-types.manage', 'description' => 'Manage MHE types'],
            ['module' => 'Checklist Groups', 'action' => 'view', 'slug' => 'checklist-groups.view', 'description' => 'View checklist groups'],
            ['module' => 'Checklist Groups', 'action' => 'manage', 'slug' => 'checklist-groups.manage', 'description' => 'Manage checklist groups'],
            ['module' => 'Checklist Items', 'action' => 'view', 'slug' => 'checklist-items.view', 'description' => 'View checklist items'],
            ['module' => 'Checklist Items', 'action' => 'manage', 'slug' => 'checklist-items.manage', 'description' => 'Manage checklist items'],
            ['module' => 'Users', 'action' => 'view', 'slug' => 'users.view', 'description' => 'View users'],
            ['module' => 'Users', 'action' => 'manage', 'slug' => 'users.manage', 'description' => 'Manage users'],
            ['module' => 'Roles', 'action' => 'view', 'slug' => 'roles.view', 'description' => 'View roles'],
            ['module' => 'Roles', 'action' => 'manage', 'slug' => 'roles.manage', 'description' => 'Manage roles'],
            ['module' => 'PMS', 'action' => 'view', 'slug' => 'pms.view', 'description' => 'View PMS'],
            ['module' => 'PMS', 'action' => 'manage', 'slug' => 'pms.manage', 'description' => 'Manage PMS'],
            ['module' => 'Action Plans', 'action' => 'view', 'slug' => 'action-plans.view', 'description' => 'View action plans'],
            ['module' => 'Action Plans', 'action' => 'manage', 'slug' => 'action-plans.manage', 'description' => 'Manage action plans'],
            ['module' => 'Action Plans', 'action' => 'confirm', 'slug' => 'action-plans.confirm', 'description' => 'Confirm or reject action plans'],
            ['module' => 'Reports', 'action' => 'view', 'slug' => 'reports.view', 'description' => 'View reports'],
            ['module' => 'Activity Logs', 'action' => 'view', 'slug' => 'activity-logs.view', 'description' => 'View activity logs'],
            ['module' => 'Profile', 'action' => 'manage', 'slug' => 'profile.manage', 'description' => 'Manage own profile'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            );
        }
    }
}
