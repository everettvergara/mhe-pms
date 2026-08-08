<?php

namespace Tests\Feature;

use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\District;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use App\Services\DashboardService;
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

    public function test_dashboard_schedule_uses_inventory_records(): void
    {
        $this->inventory->update([
            'next_pms_date' => now()->addDays(5),
            'last_pms_header_id' => $this->createSubmittedPms('PMS-DASH', now()->addDays(5)->format('Y-m-d'))->id,
        ]);

        $data = app(DashboardService::class)->pmsSchedule($this->supplierUser);

        $this->assertSame(1, $data['kpis']['total_scheduled']);
        $this->assertCount(1, $data['records']);
        $this->assertSame($this->inventory->id, $data['records']->first()->id);
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

    protected function createSubmittedPms(string $pmsNo, string $nextScheduleDate): PmsHeader
    {
        $pms = PmsHeader::query()->create([
            'pms_no' => $pmsNo,
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
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

        app(\App\Services\MheInventoryPmsScheduleService::class)->syncFromPmsHeader($pms);

        return $pms;
    }
}
