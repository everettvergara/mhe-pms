<?php

namespace Tests\Feature;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Permission;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDowntimeQueueTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected Site $otherSite;

    protected User $siteUser;

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

        $district = District::query()->first();

        $this->site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE1',
            'site_name' => 'Site One',
            'status' => RecordStatus::Active,
        ]);

        $this->otherSite = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE2',
            'site_name' => 'Site Two',
            'status' => RecordStatus::Active,
        ]);

        $this->siteUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->siteUser->sites()->attach($this->site->id);

        $this->otherSiteUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->otherSiteUser->sites()->attach($this->otherSite->id);

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

    public function test_supplier_dashboard_counts_posted_downtime_still_down(): void
    {
        $this->createPostedDowntime($this->site);
        $backUp = $this->createPostedDowntime($this->site, 'Back up', 'FL-002');
        $backUp->update(['uptime' => now()]);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(1, $data['kpis']['currently_down_units']);
    }

    public function test_supplier_dashboard_counts_distinct_down_units(): void
    {
        $this->createPostedDowntime($this->site, 'Broken mast');
        $this->createPostedDowntime($this->site, 'Still broken');
        $this->createPostedDowntime($this->site, 'Other unit', 'FL-002');

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(2, $data['kpis']['currently_down_units']);
    }

    public function test_supplier_dashboard_excludes_other_site_downtimes(): void
    {
        $this->createPostedDowntime($this->site);

        $data = app(DashboardService::class)->forSupplier($this->otherSiteUser);

        $this->assertSame(0, $data['kpis']['currently_down_units']);
    }

    public function test_supplier_dashboard_excludes_other_supplier_downtimes_at_same_site(): void
    {
        $otherSupplier = $this->createOtherSupplier();

        $this->createPostedDowntime($this->site);
        $otherDowntime = $this->createPostedDowntime($this->site, 'Other supplier downtime');
        $otherDowntime->update(['supplier_id' => $otherSupplier->id]);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(1, $data['kpis']['currently_down_units']);
    }

    public function test_supplier_dashboard_lists_pending_and_rejected_downtime_action_plans_in_scope(): void
    {
        $pending = $this->createPostedDowntime($this->site, 'Pending job', 'FL-001');
        $pending->actionPlans()->create($this->downtimePlan('DT-PENDING', 'Pending fix', DowntimeActionPlanStatus::Pending));

        $rejected = $this->createPostedDowntime($this->site, 'Rejected job', 'FL-002');
        $rejected->actionPlans()->create($this->downtimePlan('DT-REJECTED', 'Rejected fix', DowntimeActionPlanStatus::Rejected));

        $confirmed = $this->createPostedDowntime($this->site, 'Confirmed job', 'FL-003');
        $confirmed->actionPlans()->create($this->downtimePlan('DT-CONFIRMED', 'Confirmed fix', DowntimeActionPlanStatus::Confirmed));

        $otherSite = $this->createPostedDowntime($this->otherSite, 'Other site job', 'FL-009');
        $otherSite->actionPlans()->create($this->downtimePlan('DT-OTHER-SITE', 'Other site fix', DowntimeActionPlanStatus::Pending));

        $otherSupplierDowntime = $this->createPostedDowntime($this->site, 'Other supplier job', 'FL-008');
        $otherSupplierDowntime->update(['supplier_id' => $this->createOtherSupplier()->id]);
        $otherSupplierDowntime->actionPlans()->create($this->downtimePlan('DT-OTHER-SUP', 'Other supplier fix', DowntimeActionPlanStatus::Pending));

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(2, $data['kpis']['downtime_waiting_implementation']);
        $this->assertEqualsCanonicalizing(
            ['DT-PENDING', 'DT-REJECTED'],
            $data['downtime_waiting_implementation']->pluck('action_plan_no')->all(),
        );
        $this->assertSame(3, $data['kpis']['currently_down_units']);
    }

    public function test_supplier_dashboard_page_shows_simple_layout(): void
    {
        $this->createPostedDowntime($this->site);

        $response = $this->actingAs($this->siteUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('PMS for the month', false);
        $response->assertSee('Currently down units', false);
        $response->assertSee('Waiting for my implementation', false);
        $response->assertSee('Quick shortcuts', false);
        $response->assertSee('MHE Downtimes', false);
        $response->assertSee('Preventive Maintenance', false);
        $response->assertSee('PMS Schedule', false);
        $response->assertSee('MHE Downtime Summary', false);
        $response->assertSee('MHE Uptime Summary', false);
        $response->assertDontSee('MHE + PMS Site Utilization', false);
        $response->assertDontSee(route('mhes.utilization'), false);
        $response->assertDontSee('Downtimes Needing Action Plan', false);
    }

    public function test_supplier_dashboard_units_stay_on_my_site_and_supplier(): void
    {
        $this->createInventory($this->site, $this->supplier, 'U-MINE');
        $this->createInventory($this->otherSite, $this->supplier, 'U-OTHER-SITE');
        $this->createInventory($this->site, $this->createOtherSupplier(), 'U-OTHER-SUP');
        $inactive = $this->createInventory($this->site, $this->supplier, 'U-INACTIVE');
        $inactive->update(['equipment_status' => RecordStatus::Inactive]);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(['U-MINE'], $data['units']->pluck('unit_no')->all());
        $this->assertSame(0, $data['kpis']['pms_month_done']);
        $this->assertSame(1, $data['kpis']['pms_month_total']);

        $response = $this->actingAs($this->siteUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('U-MINE', false);
        $response->assertDontSee('U-OTHER-SITE', false);
        $response->assertDontSee('U-OTHER-SUP', false);
        $response->assertDontSee('U-INACTIVE', false);

        $all = $this->actingAs($this->siteUser)->get(route('dashboard.units'));
        $all->assertOk();
        $all->assertSee('U-MINE', false);
        $all->assertDontSee('U-OTHER-SITE', false);
        $all->assertDontSee('U-OTHER-SUP', false);
    }

    public function test_supplier_dashboard_pms_month_counts_submitted_pms_only(): void
    {
        $this->createInventory($this->site, $this->supplier, 'U-YES');
        $this->createInventory($this->site, $this->supplier, 'U-DRAFT');

        $this->createPms('PMS-YES', 'U-yes', PmsStatus::NoFindings);
        $this->createPms('PMS-DRAFT', 'U-DRAFT', PmsStatus::Draft);
        $this->createPms('PMS-OLD', 'U-DRAFT', PmsStatus::WithFindings, now()->subMonth());
        $this->createPms('PMS-CANCELLED', 'U-DRAFT', PmsStatus::Cancelled);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(1, $data['kpis']['pms_month_done']);
        $this->assertSame(2, $data['kpis']['pms_month_total']);
        $this->assertSame('U-DRAFT', $data['units']->first()->unit_no);
        $this->assertFalse($data['units']->firstWhere('unit_no', 'U-DRAFT')->pms_this_month);
        $this->assertTrue($data['units']->firstWhere('unit_no', 'U-YES')->pms_this_month);

        $response = $this->actingAs($this->siteUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('1/2', false);
        $response->assertSee('Yes', false);
        $response->assertSee('No', false);
    }

    public function test_supplier_dashboard_pms_waiting_list_is_pending_and_rejected_in_scope(): void
    {
        $mine = $this->createPms('PMS-MINE', 'U-MINE', PmsStatus::WithFindings);
        $this->createActionPlan($this->createDetail($mine, 'Leak'), ActionPlanStatus::Pending, 'AP-PENDING');
        $this->createActionPlan($this->createDetail($mine, 'Wear'), ActionPlanStatus::Rejected, 'AP-REJECTED');
        $this->createActionPlan($this->createDetail($mine, 'Done'), ActionPlanStatus::Confirmed, 'AP-CONFIRMED');

        $other = $this->createPms('PMS-OTHER', 'U-OTHER', PmsStatus::WithFindings, now(), $this->otherSite->id);
        $this->createActionPlan($this->createDetail($other, 'Other site'), ActionPlanStatus::Pending, 'AP-OTHER-SITE');

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(2, $data['kpis']['pms_waiting_implementation']);
        $this->assertEqualsCanonicalizing(
            ['AP-PENDING', 'AP-REJECTED'],
            $data['pms_waiting_implementation']->pluck('action_plan_no')->all(),
        );

        $response = $this->actingAs($this->siteUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('AP-PENDING', false);
        $response->assertSee('AP-REJECTED', false);
        $response->assertDontSee('AP-CONFIRMED', false);
        $response->assertDontSee('AP-OTHER-SITE', false);
    }

    public function test_supplier_inventory_index_and_show_stay_in_scope(): void
    {
        $permission = Permission::query()->where('slug', 'mhe-inventories.view')->firstOrFail();
        $this->siteUser->role->permissions()->syncWithoutDetaching([$permission->id]);

        $mine = $this->createInventory($this->site, $this->supplier, 'U-MINE');
        $otherSite = $this->createInventory($this->otherSite, $this->supplier, 'U-OTHER-SITE');
        $otherSupplier = $this->createInventory($this->site, $this->createOtherSupplier(), 'U-OTHER-SUP');

        $this->actingAs($this->siteUser)
            ->get(route('mhe-inventories.index'))
            ->assertOk()
            ->assertSee('U-MINE', false)
            ->assertDontSee('U-OTHER-SITE', false)
            ->assertDontSee('U-OTHER-SUP', false);

        $this->actingAs($this->siteUser)
            ->get(route('mhe-inventories.show', $mine))
            ->assertOk();

        $this->actingAs($this->siteUser)
            ->get(route('mhe-inventories.show', $otherSite))
            ->assertForbidden();

        $this->actingAs($this->siteUser)
            ->get(route('mhe-inventories.show', $otherSupplier))
            ->assertForbidden();
    }

    public function test_downtime_index_filters_currently_down(): void
    {
        $down = $this->createPostedDowntime($this->site, 'Still down', 'FL-001');
        $up = $this->createPostedDowntime($this->site, 'Already up', 'FL-002');
        $up->update(['uptime' => now()]);

        $response = $this->actingAs($this->siteUser)->get(route('mhe-downtimes.index', [
            'filters' => ['currently_down' => 1],
        ]));

        $response->assertOk();
        $response->assertSee($down->title, false);
        $response->assertDontSee($up->title, false);
    }

    public function test_downtime_index_filters_needs_action_plan(): void
    {
        $needsPlan = $this->createPostedDowntime($this->site);
        $withPlan = $this->createPostedDowntime($this->site, 'Other issue');
        $withPlan->actionPlans()->create([
            'action_plan_no' => 'DT-AP-TEST-2',
            'title' => 'Fix unit',
            'description' => 'Fix unit',
            'responsible_person' => 'Tech',
            'timeline_from' => '2026-01-01',
            'timeline_to' => '2026-01-07',
            'status' => DowntimeActionPlanStatus::Pending,
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);

        $response = $this->actingAs($this->siteUser)->get(route('mhe-downtimes.index', [
            'filters' => ['needs_action_plan' => 1],
        ]));

        $response->assertOk();
        $response->assertSee($needsPlan->title, false);
        $response->assertDontSee($withPlan->title, false);
    }

    protected function createPostedDowntime(Site $site, string $title = 'Broken mast', string $unitNo = 'FL-001'): MheDowntime
    {
        return MheDowntime::query()->create([
            'title' => $title,
            'site_id' => $site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => $this->mheCategory->id,
            'supplier_id' => $this->supplier->id,
            'ref_unit_no' => $unitNo,
            'date_of_incident' => now(),
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
            'posted_by' => $this->siteUser->id,
            'posted_at' => now(),
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);
    }

    protected function createOtherSupplier(): Supplier
    {
        return Supplier::query()->firstOrCreate(
            ['supplier_code' => 'SUP2'],
            [
                'supplier_name' => 'Supplier Two',
                'status' => RecordStatus::Active,
            ],
        );
    }

    protected function createInventory(Site $site, Supplier $supplier, string $unitNo): MheInventory
    {
        return MheInventory::query()->create([
            'site_id' => $site->id,
            'site' => $site->site_name,
            'supplier_id' => $supplier->id,
            'provider' => $supplier->supplier_name,
            'mhe_type_id' => $this->mheType->id,
            'equipment_type' => $this->mheType->description,
            'unit_no' => $unitNo,
            'equipment_status' => RecordStatus::Active,
        ]);
    }

    protected function createPms(
        string $pmsNo,
        string $unitNumber,
        PmsStatus $status,
        mixed $dateFrom = null,
        ?int $siteId = null,
    ): PmsHeader {
        $dateFrom ??= now();

        return PmsHeader::query()->create([
            'pms_no' => $pmsNo,
            'supplier_id' => $this->supplier->id,
            'site_id' => $siteId ?? $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => $dateFrom,
            'date_to' => now(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => $unitNumber,
            'status' => $status,
            'submitted_by' => $this->siteUser->id,
            'submitted_at' => now(),
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);
    }

    protected function createDetail(PmsHeader $pms, string $description): PmsDetail
    {
        $group = ChecklistGroup::query()->firstOrCreate(
            ['group_name' => 'General'],
            ['sequence' => 1, 'status' => RecordStatus::Active],
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
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);
    }

    protected function createActionPlan(PmsDetail $detail, ActionPlanStatus $status, string $actionPlanNo): ActionPlan
    {
        return ActionPlan::query()->create([
            'action_plan_no' => $actionPlanNo,
            'pms_detail_id' => $detail->id,
            'title' => 'Fix '.$actionPlanNo,
            'description' => 'Repair required',
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => $status,
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function downtimePlan(string $number, string $title, DowntimeActionPlanStatus $status): array
    {
        return [
            'action_plan_no' => $number,
            'title' => $title,
            'description' => $title,
            'responsible_person' => 'Tech',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
            'status' => $status,
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ];
    }
}
