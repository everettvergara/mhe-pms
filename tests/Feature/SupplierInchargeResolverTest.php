<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierInchargeResolver;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierInchargeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Supplier $otherSupplier;

    protected Site $site;

    protected User $inchargeUser;

    protected User $otherSiteUser;

    protected MheType $mheType;

    protected MheCategory $mheCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);

        $this->otherSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP2',
            'supplier_name' => 'Supplier Two',
            'status' => RecordStatus::Active,
        ]);

        $district = District::query()->first();

        $this->site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE1',
            'site_name' => 'Site One',
            'status' => RecordStatus::Active,
        ]);

        $otherSite = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE2',
            'site_name' => 'Site Two',
            'status' => RecordStatus::Active,
        ]);

        $this->inchargeUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->inchargeUser->suppliers()->attach($this->supplier->id);
        $this->inchargeUser->sites()->attach($this->site->id);

        $this->otherSiteUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->otherSiteUser->suppliers()->attach($this->supplier->id);
        $this->otherSiteUser->sites()->attach($otherSite->id);

        $this->mheType = MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);

        $this->mheCategory = MheCategory::query()->create([
            'code' => 'Damage',
            'name' => 'Damage',
            'status' => RecordStatus::Active,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function downtimeAttributes(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Broken mast',
            'site_id' => $this->site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => $this->mheCategory->id,
            'ref_unit_no' => 'FL-001',
            'date_of_incident' => now(),
            'status' => DowntimeStatus::Posted,
            'created_by' => $this->inchargeUser->id,
            'updated_by' => $this->inchargeUser->id,
        ], $overrides);
    }

    public function test_resolver_returns_supplier_incharge_for_matching_site_and_supplier(): void
    {
        $downtime = MheDowntime::query()->create($this->downtimeAttributes([
            'supplier_id' => $this->supplier->id,
        ]));

        $resolved = app(SupplierInchargeResolver::class)->resolve($downtime);

        $this->assertCount(1, $resolved);
        $this->assertTrue($resolved->first()->is($this->inchargeUser));
    }

    public function test_resolver_returns_empty_when_no_matching_supplier_user(): void
    {
        $downtime = MheDowntime::query()->create($this->downtimeAttributes([
            'supplier_id' => $this->otherSupplier->id,
        ]));

        $resolved = app(SupplierInchargeResolver::class)->resolve($downtime);

        $this->assertCount(0, $resolved);
    }

    public function test_resolver_falls_back_to_inventory_supplier_id(): void
    {
        $inventory = MheInventory::query()->create([
            'unit_no' => 'FL-002',
            'site_id' => $this->site->id,
            'supplier_id' => $this->supplier->id,
            'mhe_type_id' => $this->mheType->id,
            'equipment_status' => RecordStatus::Active,
        ]);

        $downtime = MheDowntime::query()->create($this->downtimeAttributes([
            'mhe_inventory_id' => $inventory->id,
            'ref_unit_no' => 'FL-002',
        ]));

        $resolved = app(SupplierInchargeResolver::class)->resolve($downtime);

        $this->assertCount(1, $resolved);
        $this->assertTrue($resolved->first()->is($this->inchargeUser));
    }
}
