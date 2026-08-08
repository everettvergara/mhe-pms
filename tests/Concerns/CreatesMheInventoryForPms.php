<?php

namespace Tests\Concerns;

use App\Enums\RecordStatus;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;

trait CreatesMheInventoryForPms
{
    protected function createInventoryForPms(
        Site $site,
        Supplier $supplier,
        MheType $mheType,
        string $unitNo = 'U-001',
    ): MheInventory {
        return MheInventory::query()->create([
            'site_id' => $site->id,
            'site' => $site->site_name,
            'supplier_id' => $supplier->id,
            'provider' => $supplier->supplier_name,
            'mhe_type_id' => $mheType->id,
            'equipment_type' => $mheType->description,
            'unit_no' => $unitNo,
            'equipment_status' => RecordStatus::Active,
        ]);
    }
}
