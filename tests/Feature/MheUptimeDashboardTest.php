<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheInventory;
use App\Models\MheMonthlyCapacity;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MheUptimeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Site $site;

    protected MheType $mheType;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);

        $district = District::query()->first();

        $this->site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'UP1',
            'site_name' => 'Uptime Site',
            'status' => RecordStatus::Active,
        ]);

        $this->mheType = MheType::query()->create([
            'code' => 'RT',
            'description' => 'Reach Truck',
            'status' => RecordStatus::Active,
        ]);

        $category = MheCategory::query()->create([
            'code' => 'MECH',
            'name' => 'Mechanical',
            'status' => RecordStatus::Active,
        ]);

        $inventory = MheInventory::query()->create([
            'district' => $district->district_name,
            'site' => $this->site->site_name,
            'site_id' => $this->site->id,
            'provider' => 'Provider',
            'supplier_id' => $this->supplier->id,
            'mhe_type_id' => $this->mheType->id,
            'unit_no' => 'RT-01',
            'equipment_status' => RecordStatus::Active,
        ]);

        $yyyymm = (int) now()->format('Ym');

        MheMonthlyCapacity::query()->create([
            'yyyymm' => $yyyymm,
            'mhe_inventory_id' => $inventory->id,
            'site_id' => $this->site->id,
            'mhe_type_id' => $this->mheType->id,
            'supplier_id' => $this->supplier->id,
            'unit_no' => 'RT-01',
            'available_hours' => 744,
            'generated_at' => now(),
        ]);

        MheDowntime::query()->create([
            'title' => 'Down event',
            'site_id' => $this->site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => $category->id,
            'supplier_id' => $this->supplier->id,
            'mhe_inventory_id' => $inventory->id,
            'ref_unit_no' => 'RT-01',
            'date_of_incident' => now()->startOfMonth()->addDays(2),
            'hours_down' => 74.4,
            'status' => DowntimeStatus::Posted,
        ]);

        $this->user = User::factory()->create();
    }

    public function test_uptime_dashboard_renders_with_kpis(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard.mhe-uptime', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'grain' => 'monthly',
        ]));

        $response->assertOk();
        $response->assertSee('MHE Uptime');
        $response->assertSee('Hours Down');
    }

    public function test_generate_monthly_capacity_command_is_idempotent(): void
    {
        $this->artisan('mhe:generate-monthly-capacity', ['yyyymm' => now()->format('Ym')])
            ->assertSuccessful();

        $countAfterFirst = MheMonthlyCapacity::query()->count();

        $this->artisan('mhe:generate-monthly-capacity', ['yyyymm' => now()->format('Ym')])
            ->assertSuccessful();

        $this->assertEquals($countAfterFirst, MheMonthlyCapacity::query()->count());
    }
}
