<?php

namespace Tests\Feature;

use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\District;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MheInventoryPmsScheduleService;
use App\Services\PmsScheduleReportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMheInventoryForPms;
use Tests\TestCase;

class MheInventoryPmsScheduleTest extends TestCase
{
    use CreatesMheInventoryForPms;
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected User $supplierUser;

    protected MheInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
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

        $this->supplierUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->supplierUser->sites()->attach($this->site->id);

        $this->inventory = $this->createInventoryForPms($this->site, $this->supplier, $this->mheType);
    }

    public function test_finalize_syncs_next_pms_date_to_inventory(): void
    {
        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();
        $nextDate = now()->addDays(14)->format('Y-m-d');

        $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), [
            'site_id' => $pms->site_id,
            'technician_name' => $pms->technician_name,
            'date_from' => $pms->date_from->format('Y-m-d'),
            'date_to' => $pms->date_to->format('Y-m-d'),
            'next_schedule_date' => $nextDate,
            'mhe_type_id' => $pms->mhe_type_id,
            'unit_number' => $pms->unit_number,
            'save_as' => 'final',
            'details' => [
                [
                    'id' => $detail->id,
                    'answer' => ChecklistAnswer::Good->value,
                ],
            ],
        ])->assertRedirect(route('pms.show', $pms));

        $this->inventory->refresh();

        $this->assertSame($nextDate, $this->inventory->next_pms_date?->format('Y-m-d'));
        $this->assertSame($pms->fresh()->id, $this->inventory->last_pms_header_id);
    }

    public function test_revert_recalculates_inventory_schedule_from_previous_pms(): void
    {
        $olderDate = now()->addDays(10)->format('Y-m-d');
        $newerDate = now()->addDays(30)->format('Y-m-d');

        $olderPms = $this->createSubmittedPms('PMS-OLDER', $olderDate);
        $newerPms = $this->createSubmittedPms('PMS-NEWER', $newerDate);

        $this->inventory->refresh();
        $this->assertSame($newerDate, $this->inventory->next_pms_date?->format('Y-m-d'));
        $this->assertSame($newerPms->id, $this->inventory->last_pms_header_id);

        $this->actingAs($this->supplierUser)
            ->post(route('pms.revert-to-draft', $newerPms))
            ->assertRedirect(route('pms.show', $newerPms));

        $this->inventory->refresh();
        $this->assertSame($olderDate, $this->inventory->next_pms_date?->format('Y-m-d'));
        $this->assertSame($olderPms->id, $this->inventory->last_pms_header_id);
    }

    public function test_schedule_page_retrieves_current_month_without_a_retrieve_action(): void
    {
        $districtName = $this->site->district()->value('district_name');

        $this->actingAs($this->supplierUser)
            ->get(route('dashboard.pms-schedule'))
            ->assertOk()
            ->assertSee('name="district_id"', false)
            ->assertSee('data-controls-site="pms-schedule-site"', false)
            ->assertSee('name="site_id"', false)
            ->assertSee('name="year"', false)
            ->assertSee('name="month"', false)
            ->assertDontSee('>Retrieve<', false)
            ->assertSeeInOrder([
                'District: '.$districtName.' (1)',
                'Site: '.$this->site->site_name.' (1)',
                'Supplier: '.$this->supplier->supplier_name.' (1)',
                'U-001',
            ], false);
    }

    public function test_retrieve_shows_serviced_unit_pms_date(): void
    {
        $earlier = now()->startOfMonth();
        $later = now()->startOfMonth()->addDays(10);
        $this->createSubmittedPms('PMS-EARLY', now()->addMonth()->toDateString(), $earlier);
        $laterPms = $this->createSubmittedPms('PMS-LATER', now()->addMonths(2)->toDateString(), $later);

        $unit = $this->unitRowFor('U-001');

        $this->assertTrue($unit['serviced']);
        $this->assertSame($later->toDateString(), $unit['pms_date']);
        $this->assertSame($laterPms->id, $unit['pms_id']);
        $this->assertSame('PMS-LATER', $unit['pms_no']);
        $this->assertNull($unit['last_serviced']);
        $this->assertNull($unit['next_service']);
    }

    public function test_retrieve_shows_last_and_next_dates_when_not_serviced(): void
    {
        $lastServiced = now()->subMonth()->startOfMonth()->addDays(2);
        $nextService = now()->addMonth()->startOfMonth()->toDateString();
        $this->createSubmittedPms('PMS-LAST', $nextService, $lastServiced);
        $this->createDraftPms();

        $unit = $this->unitRowFor('U-001');

        $this->assertFalse($unit['serviced']);
        $this->assertNull($unit['pms_date']);
        $this->assertSame($lastServiced->toDateString(), $unit['last_serviced']);
        $this->assertSame($nextService, $unit['next_service']);
    }

    public function test_retrieve_excludes_units_outside_district_site_and_supplier(): void
    {
        $otherDistrict = District::query()->create([
            'district_code' => 'D-OTHER',
            'district_name' => 'Other District',
            'status' => RecordStatus::Active,
        ]);
        $otherSite = Site::query()->create([
            'district_id' => $otherDistrict->id,
            'site_code' => 'SITE-OTHER',
            'site_name' => 'Other Site',
            'status' => RecordStatus::Active,
        ]);
        $otherSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP-OTHER',
            'supplier_name' => 'Other Supplier',
            'status' => RecordStatus::Active,
        ]);

        $this->createInventoryForPms($this->site, $otherSupplier, $this->mheType, 'U-OTHER-SUP');
        $this->createInventoryForPms($otherSite, $this->supplier, $this->mheType, 'U-OTHER-SITE');
        $inactive = $this->createInventoryForPms($this->site, $this->supplier, $this->mheType, 'U-INACTIVE');
        $inactive->update(['equipment_status' => RecordStatus::Inactive]);

        $unitNos = collect($this->reportGroups())
            ->flatMap(fn (array $district) => $district['sites'])
            ->flatMap(fn (array $site) => $site['suppliers'])
            ->flatMap(fn (array $supplier) => $supplier['units'])
            ->pluck('unit_no')
            ->all();

        $this->assertSame(['U-001'], $unitNos);
        $this->assertSame([], $this->reportGroups(['site_id' => $otherSite->id]));
        $this->assertSame([], $this->reportGroups(['district_id' => $otherDistrict->id]));
    }

    public function test_retrieve_page_lists_units_grouped_by_district_site_and_supplier(): void
    {
        $districtName = $this->site->district()->value('district_name');

        $this->actingAs($this->supplierUser)
            ->get(route('dashboard.pms-schedule', [
                'year' => now()->year,
                'month' => now()->month,
            ]))
            ->assertOk()
            ->assertSeeInOrder([
                'District: '.$districtName.' (1)',
                'Site: '.$this->site->site_name.' (1)',
                'Supplier: '.$this->supplier->supplier_name.' (1)',
                'U-001',
                'No',
            ], false)
            ->assertSee('class="d-none" data-parent="district-0"', false)
            ->assertSee('aria-expanded="false"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return list<array<string, mixed>>
     */
    protected function reportGroups(array $overrides = []): array
    {
        return app(PmsScheduleReportService::class)->groups($this->supplierUser, array_merge([
            'district_id' => null,
            'site_id' => null,
            'year' => (int) now()->year,
            'month' => (int) now()->month,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    protected function unitRowFor(string $unitNo): array
    {
        foreach ($this->reportGroups() as $district) {
            foreach ($district['sites'] as $site) {
                foreach ($site['suppliers'] as $supplier) {
                    foreach ($supplier['units'] as $unit) {
                        if ($unit['unit_no'] === $unitNo) {
                            return $unit;
                        }
                    }
                }
            }
        }

        $this->fail('Unit '.$unitNo.' was not in the schedule report.');
    }

    protected function createDraftPms(): PmsHeader
    {
        $group = ChecklistGroup::query()->create([
            'group_name' => 'General',
            'sequence' => 1,
            'status' => RecordStatus::Active,
        ]);

        $item = ChecklistItem::query()->create([
            'checklist_group_id' => $group->id,
            'sequence' => 1,
            'description' => 'Inspect brakes',
            'status' => RecordStatus::Active,
        ]);

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

        PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        return $pms->load('pmsDetails');
    }

    protected function createSubmittedPms(string $pmsNo, string $nextScheduleDate, mixed $dateFrom = null): PmsHeader
    {
        $pms = PmsHeader::query()->create([
            'pms_no' => $pmsNo,
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => $dateFrom ?? now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => $nextScheduleDate,
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'status' => PmsStatus::NoFindings,
            'submitted_by' => $this->supplierUser->id,
            'submitted_at' => now(),
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        app(MheInventoryPmsScheduleService::class)->syncFromPmsHeader($pms);

        return $pms;
    }
}
