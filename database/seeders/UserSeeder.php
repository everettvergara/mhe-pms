<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->firstOrFail();
        $supplierRole = Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->firstOrFail();

        $superAdmins = [
            'admin' => 'FAST Administrator',
            'andyF' => 'Andy F',
            'CharlesM' => 'Charles M',
            'markt' => 'Mark T',
        ];

        foreach ($superAdmins as $username => $name) {
            $user = User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $username.'@example.com',
                    'password' => Hash::make($username),
                    'role_id' => $adminRole->id,
                    'supplier_id' => null,
                    'is_super_admin' => true,
                    'status' => UserStatus::Active,
                ],
            );

            $user->suppliers()->sync([]);
        }

        $assignments = [
            'toyota' => ['supplier' => 'TOY', 'sites' => ['SDC', 'D&L Pasig']],
            'global' => ['supplier' => 'GBL', 'sites' => ['DMPICGY']],
            'boeing' => ['supplier' => 'BOE', 'sites' => ['Alabang', 'PepSi']],
        ];

        foreach ($assignments as $username => $data) {
            $supplier = Supplier::query()->where('supplier_code', $data['supplier'])->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => ucfirst($username).' User',
                    'email' => $username.'@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $supplierRole->id,
                    'supplier_id' => $supplier->id,
                    'is_super_admin' => false,
                    'status' => UserStatus::Active,
                ],
            );

            $user->suppliers()->sync([$supplier->id]);

            $siteIds = Site::query()->whereIn('site_code', $data['sites'])->pluck('id');
            $user->sites()->sync($siteIds);
        }
    }
}
