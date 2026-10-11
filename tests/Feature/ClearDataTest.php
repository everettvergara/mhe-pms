<?php

namespace Tests\Feature;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\MheDowntimeImportSource;
use App\Enums\PmsStatus;
use App\Enums\ProgressStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\ActionPlanComment;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\District;
use App\Models\EagleEyeImportLog;
use App\Models\EagleEyeImportMap;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheDowntimeActionPlanComment;
use App\Models\MheDowntimeImportBatch;
use App\Models\MheInventory;
use App\Models\MheMonthlyCapacity;
use App\Models\MheType;
use App\Models\NumberSequence;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClearDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_non_super_admin_cannot_open_or_clear_data(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('system.clear-data.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('system.clear-data.clear'), [
                'confirmation' => config('data_clear.confirmation_phrase'),
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertDontSee('Clear Data');
    }

    public function test_super_admin_can_open_the_clear_data_page(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('system.clear-data.index'))
            ->assertOk()
            ->assertSee('Clear Data')
            ->assertSee('Will be deleted')
            ->assertSee('Will be kept')
            ->assertSee('PMS headers')
            ->assertSee('MHE downtimes')
            ->assertSee(config('data_clear.confirmation_phrase'))
            ->assertSee('Equipment inventory units');
    }

    public function test_wrong_or_missing_phrase_leaves_records_in_place(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $records = $this->seedRecords($admin);

        $this->actingAs($admin)
            ->from(route('system.clear-data.index'))
            ->post(route('system.clear-data.clear'), [
                'confirmation' => 'clear it',
            ])
            ->assertRedirect(route('system.clear-data.index'))
            ->assertSessionHasErrors('confirmation');

        $this->actingAs($admin)
            ->from(route('system.clear-data.index'))
            ->post(route('system.clear-data.clear'), [])
            ->assertRedirect(route('system.clear-data.index'))
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(1, PmsHeader::query()->count());
        $this->assertSame(1, MheDowntime::withTrashed()->count());
        $this->assertNotNull($records['inventory']->fresh()->next_pms_date);
        $this->assertSame(1, ActivityLog::query()->where('module', 'Users')->count());
    }

    public function test_correct_phrase_clears_pms_and_mhe_records_and_keeps_masters(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $records = $this->seedRecords($admin);

        $this->actingAs($admin)
            ->post(route('system.clear-data.clear'), [
                'confirmation' => config('data_clear.confirmation_phrase'),
            ])
            ->assertRedirect(route('system.clear-data.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, PmsHeader::query()->count());
        $this->assertSame(0, PmsDetail::query()->count());
        $this->assertSame(0, ActionPlan::query()->count());
        $this->assertSame(0, ActionPlanComment::query()->count());
        $this->assertSame(0, MheDowntime::withTrashed()->count());
        $this->assertSame(0, MheDowntimeActionPlan::withTrashed()->count());
        $this->assertSame(0, MheDowntimeActionPlanComment::query()->count());
        $this->assertSame(0, MheMonthlyCapacity::query()->count());
        $this->assertSame(0, Attachment::query()->count());
        $this->assertSame(0, EagleEyeImportLog::query()->count());
        $this->assertSame(0, EagleEyeImportMap::query()->count());
        $this->assertSame(0, MheDowntimeImportBatch::query()->count());
        $this->assertSame(0, NumberSequence::query()->whereIn('type', ['pms', 'action_plan', 'downtime_action_plan'])->count());
        $this->assertSame(0, ActivityLog::query()->whereIn('module', ['pms', 'action_plan', 'mhe_downtimes', 'mhe_downtime_action_plans'])->count());

        $inventory = $records['inventory']->fresh();
        $this->assertNotNull($inventory);
        $this->assertNull($inventory->next_pms_date);
        $this->assertNull($inventory->last_pms_header_id);
        $this->assertSame('FL-001', $inventory->unit_no);

        $this->assertTrue(User::query()->whereKey($admin->id)->exists());
        $this->assertTrue(Supplier::query()->whereKey($records['supplier']->id)->exists());
        $this->assertTrue(Site::query()->whereKey($records['site']->id)->exists());
        $this->assertTrue(ChecklistItem::query()->whereKey($records['checklistItem']->id)->exists());
        $this->assertSame(1, NumberSequence::query()->where('type', 'other')->count());
        $this->assertSame(1, ActivityLog::query()->where('module', 'Users')->count());

        $clearLog = ActivityLog::query()->where('module', 'system')->where('action', 'clear_data')->first();
        $this->assertNotNull($clearLog);
        $this->assertSame($admin->id, $clearLog->user_id);

        Storage::disk('public')->assertMissing($records['pmsFile']);
        Storage::disk('public')->assertMissing($records['downtimeFile']);
    }

    /**
     * @return array{
     *     supplier: Supplier,
     *     site: Site,
     *     inventory: MheInventory,
     *     checklistItem: ChecklistItem,
     *     pmsFile: string,
     *     downtimeFile: string
     * }
     */
    private function seedRecords(User $admin): array
    {
        Storage::fake('public');

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);

        $site = Site::query()->create([
            'district_id' => District::query()->firstOrFail()->id,
            'site_code' => 'SITE1',
            'site_name' => 'Site One',
            'status' => RecordStatus::Active,
        ]);

        $mheType = MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);

        $category = MheCategory::query()->create([
            'code' => 'Damage',
            'name' => 'Damage',
            'status' => RecordStatus::Active,
        ]);

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
            'supplier_id' => $supplier->id,
            'site_id' => $site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'FL-001',
            'status' => PmsStatus::WithFindings,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $detail = PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => ChecklistAnswer::NoGood,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $actionPlan = ActionPlan::query()->create([
            'action_plan_no' => 'AP-001',
            'pms_detail_id' => $detail->id,
            'title' => 'Replace hose',
            'description' => 'Replace the hose',
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
            'status' => ActionPlanStatus::Pending,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ActionPlanComment::query()->create([
            'action_plan_id' => $actionPlan->id,
            'comment' => 'Started',
            'progress_status' => ProgressStatus::Pending,
            'created_by' => $admin->id,
        ]);

        $inventory = MheInventory::query()->create([
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $mheType->id,
            'unit_no' => 'FL-001',
            'equipment_status' => RecordStatus::Active,
            'next_pms_date' => now()->addMonth()->toDateString(),
            'last_pms_header_id' => $pms->id,
        ]);

        $downtime = MheDowntime::query()->create([
            'title' => 'Hydraulic leak',
            'site_id' => $site->id,
            'mhe_type_id' => $mheType->id,
            'mhe_category_id' => $category->id,
            'mhe_inventory_id' => $inventory->id,
            'supplier_id' => $supplier->id,
            'ref_unit_no' => 'FL-001',
            'date_of_incident' => now(),
            'status' => DowntimeStatus::Posted,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $downtimePlan = MheDowntimeActionPlan::query()->create([
            'action_plan_no' => 'DT-AP-001',
            'mhe_downtime_id' => $downtime->id,
            'title' => 'Replace hose',
            'description' => 'Swap the hose',
            'responsible_person' => 'Tech',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
            'status' => DowntimeActionPlanStatus::Pending,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        MheDowntimeActionPlanComment::query()->create([
            'mhe_downtime_action_plan_id' => $downtimePlan->id,
            'comment' => 'Waiting for parts',
            'progress_status' => ProgressStatus::Pending,
            'created_by' => $admin->id,
            'created_at' => now(),
        ]);

        $downtime->delete();
        $downtimePlan->delete();

        MheMonthlyCapacity::query()->create([
            'yyyymm' => 202610,
            'mhe_inventory_id' => $inventory->id,
            'site_id' => $site->id,
            'mhe_type_id' => $mheType->id,
            'supplier_id' => $supplier->id,
            'unit_no' => 'FL-001',
            'available_hours' => 200,
            'generated_at' => now(),
        ]);

        $pmsFile = 'pms-attachments/'.$pms->id.'/photo.txt';
        $downtimeFile = 'mhe-downtime-attachments/'.$downtime->id.'/photo.txt';
        Storage::disk('public')->put($pmsFile, 'pms');
        Storage::disk('public')->put($downtimeFile, 'downtime');

        Attachment::query()->create([
            'attachable_type' => $pms->getMorphClass(),
            'attachable_id' => $pms->id,
            'file_path' => $pmsFile,
            'original_filename' => 'photo.txt',
            'mime_type' => 'text/plain',
            'file_size' => 3,
            'created_by' => $admin->id,
        ]);

        Attachment::query()->create([
            'attachable_type' => $downtime->getMorphClass(),
            'attachable_id' => $downtime->id,
            'file_path' => $downtimeFile,
            'original_filename' => 'photo.txt',
            'mime_type' => 'text/plain',
            'file_size' => 8,
            'created_by' => $admin->id,
        ]);

        EagleEyeImportLog::query()->create([
            'batch_id' => (string) Str::uuid(),
            'level' => 'info',
            'message' => 'Imported',
        ]);

        EagleEyeImportMap::query()->create([
            'entity_type' => 'downtime',
            'legacy_id' => 10,
            'local_id' => $downtime->id,
        ]);

        MheDowntimeImportBatch::query()->create([
            'batch_id' => (string) Str::uuid(),
            'source' => MheDowntimeImportSource::EagleEyeMysql,
            'created_by' => $admin->id,
        ]);

        NumberSequence::query()->create(['type' => 'pms', 'year' => 2026, 'last_number' => 4]);
        NumberSequence::query()->create(['type' => 'action_plan', 'year' => 2026, 'last_number' => 2]);
        NumberSequence::query()->create(['type' => 'downtime_action_plan', 'year' => 2026, 'last_number' => 1]);
        NumberSequence::query()->create(['type' => 'other', 'year' => 2026, 'last_number' => 9]);

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'module' => 'pms',
            'action' => 'create',
            'record_id' => $pms->id,
            'description' => 'Created PMS',
        ]);

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'module' => 'mhe_downtimes',
            'action' => 'create',
            'record_id' => $downtime->id,
            'description' => 'Created downtime',
        ]);

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'module' => 'Users',
            'action' => 'import',
            'record_id' => $admin->id,
            'description' => 'Imported user',
        ]);

        return [
            'supplier' => $supplier,
            'site' => $site,
            'inventory' => $inventory,
            'checklistItem' => $item,
            'pmsFile' => $pmsFile,
            'downtimeFile' => $downtimeFile,
        ];
    }
}
