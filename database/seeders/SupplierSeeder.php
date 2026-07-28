<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['supplier_code' => 'TOY', 'supplier_name' => 'Toyota', 'contact_person' => 'Toyota Contact'],
            ['supplier_code' => 'GBL', 'supplier_name' => 'Global', 'contact_person' => 'Global Contact'],
            ['supplier_code' => 'BOE', 'supplier_name' => 'Boeing', 'contact_person' => 'Boeing Contact'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(
                ['supplier_code' => $supplier['supplier_code']],
                array_merge($supplier, ['status' => RecordStatus::Active->value]),
            );
        }
    }
}
