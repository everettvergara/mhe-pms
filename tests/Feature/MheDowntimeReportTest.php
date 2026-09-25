<?php

namespace Tests\Feature;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheType;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MheDowntimeReportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MheDowntimeReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $district = District::query()->first();
        $site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'RPT1',
            'site_name' => 'Report Site',
            'status' => RecordStatus::Active,
        ]);

        $type = MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);

        $category = MheCategory::query()->create([
            'code' => 'ELEC',
            'name' => 'Electrical',
            'status' => RecordStatus::Active,
        ]);

        MheDowntime::query()->create([
            'title' => 'Incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-1',
            'date_of_incident' => now(),
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
        ]);

        $this->user = User::factory()->create();
    }

    public function test_summary_report_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.summary'))
            ->assertOk()
            ->assertSee('MHE Downtime Summary')
            ->assertSee('id="action-plan-filter"', false)
            ->assertSee('data-controls-site="site_id"', false)
            ->assertSee('data-controls-site="action_plan_site_id"', false)
            ->assertSee('name="ap_date_from"', false)
            ->assertSee('name="ap_date_to"', false)
            ->assertSee('>Retrieve<', false);
    }

    public function test_site_filter_requires_a_matching_district(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $site = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $otherDistrict = District::query()->create([
            'district_code' => 'OTS',
            'district_name' => 'Other Site District',
            'status' => RecordStatus::Active,
        ]);
        $otherSite = Site::query()->create([
            'district_id' => $otherDistrict->id,
            'site_code' => 'RPT2',
            'site_name' => 'Second Report Site',
            'status' => RecordStatus::Active,
        ]);
        $selected = 'value="'.$site->id.'" data-district-id="'.$site->district_id.'" selected';

        $this->actingAs($admin)
            ->get(route('mhes.summary', ['site_id' => $site->id]))
            ->assertOk()
            ->assertSee('data-district-id="'.$site->district_id.'"', false)
            ->assertSee('data-district-id="'.$otherSite->district_id.'"', false)
            ->assertDontSee($selected, false);

        $this->actingAs($admin)
            ->get(route('mhes.summary', [
                'district_id' => $site->district_id,
                'site_id' => $site->id,
            ]))
            ->assertOk()
            ->assertSee($selected, false)
            ->assertSee('data-district-id="'.$otherSite->district_id.'"', false);

        $this->actingAs($admin)
            ->get(route('mhes.summary', [
                'district_id' => $otherDistrict->id,
                'site_id' => $site->id,
            ]))
            ->assertOk()
            ->assertDontSee($selected, false);

        $this->actingAs($admin)
            ->get(route('mhes.utilization', [
                'district_id' => $site->district_id,
                'site_id' => $otherSite->id,
            ]))
            ->assertOk()
            ->assertDontSee('value="'.$otherSite->id.'" data-district-id="'.$otherSite->district_id.'" selected', false);

        $this->actingAs($admin)
            ->get(route('dashboard.mhe-uptime', [
                'district_id' => $site->district_id,
                'site_id' => $site->id,
            ]))
            ->assertOk()
            ->assertSee($selected, false);

        $this->actingAs($admin)
            ->get(route('dashboard.pms-schedule', [
                'district_id' => $site->district_id,
                'site_id' => $site->id,
            ]))
            ->assertOk()
            ->assertSee($selected, false);
    }

    public function test_utilization_report_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.utilization'))
            ->assertOk()
            ->assertSee('MHE + PMS Site Utilization')
            ->assertSee('data-controls-site="utilization-site"', false)
            ->assertSee('Sites using PMS / MHE');
    }

    public function test_utilization_report_is_hidden_from_suppliers(): void
    {
        $supplier = User::factory()->supplier()->create();

        $this->actingAs($supplier)
            ->get(route('mhes.utilization'))
            ->assertForbidden();
    }

    public function test_utilization_splits_sites_by_submitted_pms_and_posted_mhe(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $district = District::query()->first();
        $type = MheType::query()->where('code', 'FL')->firstOrFail();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'UTIL',
            'supplier_name' => 'Utilization Supplier',
            'status' => RecordStatus::Active,
        ]);
        $active = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $idle = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'IDLE',
            'site_name' => 'Idle Site',
            'status' => RecordStatus::Active,
        ]);

        PmsHeader::query()->create([
            'pms_no' => 'PMS-UTIL-1',
            'supplier_id' => $supplier->id,
            'site_id' => $active->id,
            'technician_name' => 'Tech',
            'date_from' => now(),
            'date_to' => now(),
            'mhe_type_id' => $type->id,
            'unit_number' => 'FL-1',
            'status' => PmsStatus::NoFindings,
            'submitted_at' => now(),
        ]);

        PmsHeader::query()->create([
            'pms_no' => 'PMS-UTIL-DRAFT',
            'supplier_id' => $supplier->id,
            'site_id' => $idle->id,
            'technician_name' => 'Tech',
            'date_from' => now(),
            'date_to' => now(),
            'mhe_type_id' => $type->id,
            'unit_number' => 'FL-2',
            'status' => PmsStatus::Draft,
            'submitted_at' => now(),
        ]);

        MheDowntime::query()->create([
            'title' => 'Draft should not count',
            'site_id' => $idle->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => MheCategory::query()->where('code', 'ELEC')->value('id'),
            'ref_unit_no' => 'FL-2',
            'date_of_incident' => now(),
            'hours_down' => 1,
            'status' => DowntimeStatus::Draft,
        ]);

        $light = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'LITE',
            'site_name' => 'Light Use Site',
            'status' => RecordStatus::Active,
        ]);
        MheDowntime::query()->create([
            'title' => 'One posted downtime',
            'site_id' => $light->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => MheCategory::query()->where('code', 'ELEC')->value('id'),
            'ref_unit_no' => 'FL-3',
            'date_of_incident' => now(),
            'hours_down' => 1,
            'status' => DowntimeStatus::Posted,
        ]);

        $bubbles = app(MheDowntimeReportService::class)->utilizationBubbles($admin, [
            'date_from' => now()->startOfWeek()->toDateString(),
            'date_to' => now()->endOfWeek()->toDateString(),
        ]);

        $used = collect($bubbles['used'])->keyBy('site_code');
        $unused = collect($bubbles['unused'])->keyBy('site_code');

        $this->assertSame(['RPT1', 'LITE'], collect($bubbles['used'])->pluck('site_code')->all());
        $this->assertSame(1, $used['RPT1']['pms']);
        $this->assertSame(1, $used['RPT1']['mhe']);
        $this->assertSame(2, $used['RPT1']['total']);
        $this->assertSame(1, $used['LITE']['total']);
        $this->assertArrayHasKey('IDLE', $unused->all());
        $this->assertArrayNotHasKey('IDLE', $used->all());

        $this->actingAs($admin)
            ->get(route('mhes.utilization'))
            ->assertOk()
            ->assertSeeInOrder(['Sites using PMS / MHE', 'RPT1', 'LITE', 'No PMS or MHE in this period', 'IDLE'])
            ->assertDontSee('gstatic.com/charts', false);
    }

    public function test_summary_page_action_plans_use_the_retrieved_date_range(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $site = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $type = MheType::query()->where('code', 'FL')->firstOrFail();
        $category = MheCategory::query()->where('code', 'ELEC')->firstOrFail();

        MheDowntime::query()->create([
            'title' => 'August leftover incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-AUG',
            'date_of_incident' => '2026-08-31 06:00:00',
            'status' => DowntimeStatus::Posted,
        ]);

        $this->actingAs($admin)
            ->get(route('mhes.summary', [
                'ap_date_from' => '2026-09-25',
                'ap_date_to' => '2026-09-25',
            ]))
            ->assertOk()
            ->assertSee('2026-09-25 to 2026-09-25')
            ->assertDontSee('August leftover incident');
    }

    public function test_summary_action_plans_partial_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.summary.action-plans', [
                'date_from' => now()->subYear()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('MHE Action Plans');
    }

    public function test_summary_action_plans_group_by_category_then_status(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $site = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $type = MheType::query()->where('code', 'FL')->firstOrFail();
        $category = MheCategory::query()->where('code', 'ELEC')->firstOrFail();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'ACME',
            'supplier_name' => 'Acme Lifts',
            'status' => RecordStatus::Active,
        ]);

        $mixed = MheDowntime::query()->create([
            'title' => 'Confirmed incident',
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-9',
            'date_of_incident' => '2026-09-20 08:00:00',
            'root_cause' => 'Hydraulic leak',
            'description' => 'Hose burst on the mast',
            'status' => DowntimeStatus::Posted,
        ]);

        $this->createDowntimePlan($mixed, 'AP-MIX-P', DowntimeActionPlanStatus::Pending);
        $this->createDowntimePlan($mixed, 'AP-MIX-C', DowntimeActionPlanStatus::Confirmed);

        $rejected = MheDowntime::query()->create([
            'title' => 'Rejected incident',
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-8',
            'date_of_incident' => '2026-09-21 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);
        $this->createDowntimePlan($rejected, 'AP-REJ', DowntimeActionPlanStatus::Rejected);

        $pendingOnly = MheDowntime::query()->create([
            'title' => 'Pending only incident',
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-7',
            'date_of_incident' => '2026-09-22 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);
        $this->createDowntimePlan($pendingOnly, 'AP-PEND', DowntimeActionPlanStatus::Pending);

        $response = $this->actingAs($admin)->get(route('mhes.summary.action-plans', [
            'date_from' => now()->subYear()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Electrical',
            '4',
            'No Action Plan',
            '1',
            'Pending',
            '2',
            'Confirmed',
            '1',
            'Rejected',
            '1',
        ]);
        $response->assertSeeInOrder([
            'Site',
            'Unit',
            'Supplier',
            'MHE Type',
            'MHE Downtime No',
            'Date',
            'What',
            'Root Cause',
            'Description',
        ]);
        $response->assertSeeInOrder([
            'FL-9',
            'Acme Lifts',
            'Forklift',
            '2026-09-20',
            'Confirmed incident',
            'Hydraulic leak',
            'Hose burst on the mast',
        ]);
        $response->assertSee(route('mhe-downtimes.show', $mixed), false);
        $this->assertSame(2, substr_count($response->getContent(), 'Confirmed incident'));

        $filtered = $this->actingAs($admin)->get(route('mhes.summary.action-plans', [
            'date_from' => now()->subYear()->toDateString(),
            'date_to' => now()->toDateString(),
            'is_pending' => 0,
        ]));

        $filtered->assertOk();
        $filtered->assertDontSee('Pending only incident');
        $filtered->assertDontSee('>Pending<', false);
        $filtered->assertSee('Confirmed incident');
        $filtered->assertSee('No Action Plan');
        $this->assertSame(1, substr_count($filtered->getContent(), 'Confirmed incident'));
    }

    public function test_summary_action_plans_exclude_incidents_outside_the_date_range(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $site = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $type = MheType::query()->where('code', 'FL')->firstOrFail();
        $category = MheCategory::query()->where('code', 'ELEC')->firstOrFail();

        MheDowntime::query()->create([
            'title' => 'Inside range incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-IN',
            'date_of_incident' => '2026-09-10 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);

        MheDowntime::query()->create([
            'title' => 'Outside range incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-OUT',
            'date_of_incident' => '2020-01-15 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);

        $this->actingAs($admin)
            ->get(route('mhes.summary.action-plans', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('Inside range incident')
            ->assertDontSee('Outside range incident');
    }

    public function test_summary_action_plans_filter_by_district_and_site(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $site = Site::query()->where('site_code', 'RPT1')->firstOrFail();
        $type = MheType::query()->where('code', 'FL')->firstOrFail();
        $category = MheCategory::query()->where('code', 'ELEC')->firstOrFail();
        $otherDistrict = District::query()->create([
            'district_code' => 'OTH',
            'district_name' => 'Other District',
            'status' => RecordStatus::Active,
        ]);
        $otherSite = Site::query()->create([
            'district_id' => $otherDistrict->id,
            'site_code' => 'OTH1',
            'site_name' => 'Other Site',
            'status' => RecordStatus::Active,
        ]);

        MheDowntime::query()->create([
            'title' => 'Home district incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-HOME',
            'date_of_incident' => '2026-09-12 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);

        MheDowntime::query()->create([
            'title' => 'Other district incident',
            'site_id' => $otherSite->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-OTH',
            'date_of_incident' => '2026-09-12 08:00:00',
            'status' => DowntimeStatus::Posted,
        ]);

        $this->actingAs($admin)
            ->get(route('mhes.summary.action-plans', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
                'district_id' => $otherDistrict->id,
                'is_pending' => 0,
                'is_implemented' => 0,
                'is_no_action_plan' => 1,
            ]))
            ->assertOk()
            ->assertSee('Other district incident')
            ->assertDontSee('Home district incident');

        $this->actingAs($admin)
            ->get(route('mhes.summary.action-plans', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
                'district_id' => $site->district_id,
                'site_id' => $site->id,
                'is_pending' => 0,
                'is_implemented' => 0,
                'is_no_action_plan' => 1,
            ]))
            ->assertOk()
            ->assertSee('Home district incident')
            ->assertDontSee('Other district incident');

        $this->actingAs($admin)
            ->get(route('mhes.summary.action-plans', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
                'site_id' => $site->id,
                'is_pending' => 0,
                'is_implemented' => 0,
                'is_no_action_plan' => 1,
            ]))
            ->assertOk()
            ->assertSee('Home district incident')
            ->assertSee('Other district incident');
    }

    protected function createDowntimePlan(MheDowntime $downtime, string $number, DowntimeActionPlanStatus $status): void
    {
        MheDowntimeActionPlan::query()->create([
            'action_plan_no' => $number,
            'mhe_downtime_id' => $downtime->id,
            'title' => $number,
            'description' => $number,
            'responsible_person' => 'Tech',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
            'status' => $status,
        ]);
    }
}
