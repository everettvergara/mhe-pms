<?php

namespace Tests\Feature;

use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheType;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMheInventoryForPms;
use Tests\TestCase;

class PmsInventoryLookupTest extends TestCase
{
    use CreatesMheInventoryForPms;
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Supplier $otherSupplier;

    protected Site $site;

    protected MheType $mheType;

    protected MheType $otherMheType;

    protected User $supplierUser;

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

        $this->site = Site::query()->create([
            'district_id' => District::query()->first()->id,
            'site_code' => 'SITE1',
            'site_name' => 'Site One',
            'status' => RecordStatus::Active,
        ]);

        $this->mheType = MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);

        $this->otherMheType = MheType::query()->create([
            'code' => 'RT',
            'description' => 'Reach Truck',
            'status' => RecordStatus::Active,
        ]);

        $this->supplierUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->supplierUser->sites()->attach($this->site->id);

        $this->createInventoryForPms($this->site, $this->supplier, $this->mheType, 'U-001');
        $this->createInventoryForPms($this->site, $this->supplier, $this->otherMheType, 'U-002');
        $this->createInventoryForPms($this->site, $this->otherSupplier, $this->mheType, 'U-OTHER');
    }

    public function test_search_units_returns_only_supplier_scoped_inventory(): void
    {
        $response = $this->actingAs($this->supplierUser)->getJson(route('pms.search-units', [
            'site_id' => $this->site->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.unit_no', 'U-001')
            ->assertJsonPath('0.label', 'U-001 (FL — Forklift)')
            ->assertJsonPath('0.mhe_type_id', $this->mheType->id)
            ->assertJsonPath('1.unit_no', 'U-002')
            ->assertJsonPath('1.label', 'U-002 (RT — Reach Truck)')
            ->assertJsonPath('1.mhe_type_id', $this->otherMheType->id);

        $unitNumbers = collect($response->json())->pluck('unit_no');
        $this->assertFalse($unitNumbers->contains('U-OTHER'));
    }

    public function test_lookup_unit_returns_mhe_type_id(): void
    {
        $response = $this->actingAs($this->supplierUser)->getJson(route('pms.lookup-unit', [
            'site_id' => $this->site->id,
            'unit_number' => 'U-001',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('mhe_type_id', $this->mheType->id)
            ->assertJsonPath('supplier_id', $this->supplier->id);
    }

    public function test_lookup_unit_returns_unmatched_for_other_supplier_unit(): void
    {
        $response = $this->actingAs($this->supplierUser)->getJson(route('pms.lookup-unit', [
            'site_id' => $this->site->id,
            'unit_number' => 'U-OTHER',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('matched', false);
    }

    public function test_update_rejects_unit_outside_supplier_scope(): void
    {
        $pms = PmsHeader::query()->create([
            'pms_no' => 'PMS-TEST-001',
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'status' => PmsStatus::Draft,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        $response = $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), [
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
            'next_schedule_date' => now()->addMonth()->format('Y-m-d'),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-OTHER',
            'save_as' => 'draft',
        ]);

        $response->assertSessionHasErrors('unit_number');
    }

    public function test_create_form_defaults_the_only_assigned_site(): void
    {
        $response = $this->actingAs($this->supplierUser)->get(route('pms.create'));

        $response->assertOk();
        $response->assertSee('value="Site One (SITE1)"', false);
        $response->assertSee('id="pms_site_id" value="'.$this->site->id.'"', false);
        $response->assertSee('name="unit_number" id="pms_unit_number" class="form-select', false);
        $response->assertSee('name="date_from" class="form-control form-control-sm" value="'.now()->format('Y-m-d').'"', false);
        $response->assertSee('name="date_to" class="form-control form-control-sm" value="'.now()->format('Y-m-d').'"', false);
        $response->assertSee('name="next_schedule_date" class="form-control form-control-sm" value="'.now()->addMonth()->format('Y-m-d').'"', false);
        $response->assertDontSee('pms-unit-numbers', false);
    }

    public function test_create_form_leaves_site_blank_when_multiple_sites_are_assigned(): void
    {
        $secondSite = Site::query()->create([
            'district_id' => District::query()->first()->id,
            'site_code' => 'SITE2',
            'site_name' => 'Site Two',
            'status' => RecordStatus::Active,
        ]);
        $this->supplierUser->sites()->attach($secondSite->id);

        $response = $this->actingAs($this->supplierUser)->get(route('pms.create'));

        $response->assertOk();
        $response->assertSee('id="pms_site_id" value=""', false);
        $response->assertDontSee('value="Site One (SITE1)"', false);
        $response->assertDontSee('value="Site Two (SITE2)"', false);
    }
}
