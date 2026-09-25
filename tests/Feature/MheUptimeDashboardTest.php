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
use App\Services\MheUptimeDashboardService;
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

    public function test_uptime_dashboard_renders_unit_table(): void
    {
        $this->user->sites()->attach($this->site->id);

        $response = $this->actingAs($this->user)->get(route('dashboard.mhe-uptime', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('MHE Uptime Summary');
        $response->assertSee('Unit No');
        $response->assertSee('Available Hrs');
        $response->assertSee('Uptime %');
        $response->assertDontSee('name="mhe_type_id"', false);
        $response->assertSee('data-controls-site="uptime-site"', false);
        $response->assertSee('RT-01');
        $response->assertDontSee('Hours Down');
        $response->assertDontSee('uptimeBarChart');
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

    public function test_available_hours_are_units_times_period_and_spare_downtime_is_ignored(): void
    {
        $site = $this->makeSite('ISO');
        $otherSupplier = $this->makeSupplier('SUP2', 'Supplier Two');

        $this->makeUnit($site, $this->supplier, 'ISO-01');
        $this->makeUnit($site, $otherSupplier, 'ISO-02');
        $this->makeUnit($site, $this->supplier, 'ISO-OFF', RecordStatus::Inactive);

        $this->makeDowntime($site, $this->supplier, 'ISO-01', 48, false);
        $this->makeDowntime($site, $otherSupplier, 'ISO-02', 100, true);

        $admin = User::factory()->superAdmin()->create();
        $data = app(MheUptimeDashboardService::class)->build($admin, [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-10',
            'site_id' => $site->id,
        ]);

        $units = $this->unitsByNumber($data['groups']);

        $this->assertSame(2, $data['groups'][0]['count']);
        $this->assertSame(2, $data['groups'][0]['sites'][0]['count']);
        $this->assertArrayNotHasKey('ISO-OFF', $units);
        $this->assertEquals(240.0, $units['ISO-01']['available_hours']);
        $this->assertEquals(48.0, $units['ISO-01']['hours_down']);
        $this->assertEquals(192.0, $units['ISO-01']['uptime_hours']);
        $this->assertEquals(80.0, $units['ISO-01']['uptime_pct']);
        $this->assertEquals(240.0, $units['ISO-02']['available_hours']);
        $this->assertEquals(0.0, $units['ISO-02']['hours_down']);
        $this->assertEquals(100.0, $units['ISO-02']['uptime_pct']);
    }

    public function test_supplier_user_only_sees_assigned_supplier_and_sites(): void
    {
        $site = $this->makeSite('OWN');
        $otherSite = $this->makeSite('OTH');
        $otherSupplier = $this->makeSupplier('SUP2', 'Supplier Two');

        $this->makeUnit($site, $this->supplier, 'OWN-01');
        $this->makeUnit($site, $otherSupplier, 'OWN-02');
        $this->makeUnit($otherSite, $this->supplier, 'OTH-01');

        $this->makeDowntime($site, $this->supplier, 'OWN-01', 24, false);
        $this->makeDowntime($site, $otherSupplier, 'OWN-02', 80, false);
        $this->makeDowntime($otherSite, $this->supplier, 'OTH-01', 40, false);

        $supplierUser = User::factory()->supplier()->create();
        $supplierUser->suppliers()->attach($this->supplier->id);
        $supplierUser->sites()->attach($site->id);

        $data = app(MheUptimeDashboardService::class)->build($supplierUser, [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-10',
        ]);

        $units = $this->unitsByNumber($data['groups']);

        $this->assertSame(1, $data['groups'][0]['count']);
        $this->assertSame(['OWN-01'], array_keys($units));
        $this->assertEquals(240.0, $units['OWN-01']['available_hours']);
        $this->assertEquals(24.0, $units['OWN-01']['hours_down']);
        $this->assertEquals(216.0, $units['OWN-01']['uptime_hours']);
        $this->assertEquals(90.0, $units['OWN-01']['uptime_pct']);
    }

    public function test_fast_admin_sees_every_supplier_at_assigned_sites_only(): void
    {
        $site = $this->makeSite('FAST');
        $otherSite = $this->makeSite('AWAY');
        $otherSupplier = $this->makeSupplier('SUP2', 'Supplier Two');

        $this->makeUnit($site, $this->supplier, 'FAST-01');
        $this->makeUnit($site, $otherSupplier, 'FAST-02');
        $this->makeUnit($otherSite, $this->supplier, 'AWAY-01');

        $this->makeDowntime($site, $this->supplier, 'FAST-01', 12, false);
        $this->makeDowntime($site, $otherSupplier, 'FAST-02', 12, false);
        $this->makeDowntime($otherSite, $this->supplier, 'AWAY-01', 50, false);

        $fastAdmin = User::factory()->create();
        $fastAdmin->sites()->attach($site->id);

        $data = app(MheUptimeDashboardService::class)->build($fastAdmin, [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
        ]);

        $units = $this->unitsByNumber($data['groups']);

        $this->assertSame(2, $data['groups'][0]['count']);
        $this->assertSame(2, $data['groups'][0]['sites'][0]['count']);
        $this->assertArrayNotHasKey('AWAY-01', $units);
        $this->assertEquals(24.0, $units['FAST-01']['available_hours']);
        $this->assertEquals(12.0, $units['FAST-01']['hours_down']);
        $this->assertEquals(12.0, $units['FAST-01']['uptime_hours']);
        $this->assertEquals(50.0, $units['FAST-01']['uptime_pct']);
        $this->assertEquals(24.0, $units['FAST-02']['available_hours']);
        $this->assertEquals(12.0, $units['FAST-02']['hours_down']);
        $this->assertEquals(50.0, $units['FAST-02']['uptime_pct']);
        $this->assertSame(1, $data['groups'][0]['sites'][0]['suppliers'][0]['count']);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return array<string, array<string, mixed>>
     */
    protected function unitsByNumber(array $groups): array
    {
        $units = [];

        foreach ($groups as $district) {
            foreach ($district['sites'] as $site) {
                foreach ($site['suppliers'] as $supplier) {
                    foreach ($supplier['units'] as $unit) {
                        $units[$unit['unit_no']] = $unit;
                    }
                }
            }
        }

        return $units;
    }

    protected function makeSite(string $code): Site
    {
        return Site::query()->create([
            'district_id' => $this->site->district_id,
            'site_code' => $code,
            'site_name' => $code.' Site',
            'status' => RecordStatus::Active,
        ]);
    }

    protected function makeSupplier(string $code, string $name): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => $code,
            'supplier_name' => $name,
            'status' => RecordStatus::Active,
        ]);
    }

    protected function makeUnit(Site $site, Supplier $supplier, string $unitNo, RecordStatus $status = RecordStatus::Active): MheInventory
    {
        return MheInventory::query()->create([
            'district' => 'District',
            'site' => $site->site_name,
            'site_id' => $site->id,
            'provider' => 'Provider',
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $this->mheType->id,
            'unit_no' => $unitNo,
            'equipment_status' => $status,
        ]);
    }

    protected function makeDowntime(Site $site, Supplier $supplier, string $unitNo, float $hoursDown, bool $spare): void
    {
        MheDowntime::query()->create([
            'title' => 'Down '.$unitNo,
            'site_id' => $site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => MheCategory::query()->first()->id,
            'supplier_id' => $supplier->id,
            'ref_unit_no' => $unitNo,
            'date_of_incident' => '2026-09-01 08:00:00',
            'hours_down' => $hoursDown,
            'w_spare_unit' => $spare,
            'status' => DowntimeStatus::Posted,
        ]);
    }
}
