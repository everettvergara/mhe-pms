<?php

namespace Tests\Feature;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\MheType;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\District;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmsIndexActionPlanCountsTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected User $adminUser;

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

        $this->adminUser = User::factory()->create();
    }

    public function test_pms_index_shows_date_range_and_created_metadata(): void
    {
        $pms = $this->createPms('PMS-META-001');

        $response = $this->actingAs($this->adminUser)
            ->get(route('pms.index'));

        $response
            ->assertOk()
            ->assertSee('Date From')
            ->assertSee('Date To')
            ->assertSee('Created By')
            ->assertSee('Created At')
            ->assertSee($pms->date_from?->format('Y-m-d'))
            ->assertSee($pms->date_to?->format('Y-m-d'))
            ->assertSee($this->adminUser->name)
            ->assertSee($pms->created_at?->format('Y-m-d H:i'));
    }

    public function test_pms_index_shows_action_item_total_and_status_counts(): void
    {
        $pmsWithPlans = $this->createPms('PMS-COUNTS-001');
        $detailOne = $this->createDetail($pmsWithPlans, 'Inspect brakes');
        $detailTwo = $this->createDetail($pmsWithPlans, 'Check hydraulics');

        $this->createActionPlan($detailOne, ActionPlanStatus::Pending, 'AP-PENDING-1');
        $this->createActionPlan($detailOne, ActionPlanStatus::Pending, 'AP-PENDING-2');
        $this->createActionPlan($detailTwo, ActionPlanStatus::Confirmed, 'AP-CONFIRMED-1');
        $this->createActionPlan($detailTwo, ActionPlanStatus::Rejected, 'AP-REJECTED-1');

        $pmsWithoutPlans = $this->createPms('PMS-COUNTS-002');

        $response = $this->actingAs($this->adminUser)
            ->get(route('pms.index'));

        $response
            ->assertOk()
            ->assertSee('Action Items')
            ->assertSee('PMS-COUNTS-001')
            ->assertSee('PMS-COUNTS-002')
            ->assertSee('>4<', false)
            ->assertSee('Pending 2')
            ->assertSee('Confirmed 1')
            ->assertSee('Rejected 1')
            ->assertDontSee('Cancelled 1');
    }

    public function test_pms_index_shows_dash_when_no_action_items(): void
    {
        $this->createPms('PMS-NO-PLANS');

        $response = $this->actingAs($this->adminUser)
            ->get(route('pms.index'));

        $response
            ->assertOk()
            ->assertSee('PMS-NO-PLANS')
            ->assertSee('>—<', false);
    }

    protected function createPms(string $pmsNo): PmsHeader
    {
        return PmsHeader::query()->create([
            'pms_no' => $pmsNo,
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'serial_number' => 'S-001',
            'status' => PmsStatus::WithFindings,
            'submitted_by' => $this->adminUser->id,
            'submitted_at' => now(),
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
    }

    protected function createDetail(PmsHeader $pms, string $description): PmsDetail
    {
        $group = ChecklistGroup::query()->firstOrCreate(
            ['group_name' => 'General'],
            ['sequence' => 1, 'status' => RecordStatus::Active]
        );

        $item = ChecklistItem::query()->create([
            'checklist_group_id' => $group->id,
            'sequence' => ChecklistItem::query()->where('checklist_group_id', $group->id)->count() + 1,
            'description' => $description,
            'status' => RecordStatus::Active,
        ]);

        return PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => ChecklistAnswer::NoGood,
            'remarks' => 'Needs repair',
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
    }

    protected function createActionPlan(
        PmsDetail $detail,
        ActionPlanStatus $status,
        string $actionPlanNo,
    ): ActionPlan {
        return ActionPlan::query()->create([
            'action_plan_no' => $actionPlanNo,
            'pms_detail_id' => $detail->id,
            'title' => 'Fix '.$detail->checklistItem?->description,
            'description' => 'Repair required',
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => $status,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
    }
}
