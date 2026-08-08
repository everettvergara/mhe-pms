<?php

namespace Tests\Feature;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheType;
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

    public function test_supplier_dashboard_includes_posted_downtime_without_action_plan(): void
    {
        $downtime = $this->createPostedDowntime($this->site);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(1, $data['kpis']['downtimes_needing_action_plan']);
        $this->assertCount(1, $data['downtimes_needing_action_plan']);
        $this->assertTrue($data['downtimes_needing_action_plan']->first()->is($downtime));
    }

    public function test_supplier_dashboard_excludes_other_site_downtimes(): void
    {
        $this->createPostedDowntime($this->site);

        $data = app(DashboardService::class)->forSupplier($this->otherSiteUser);

        $this->assertSame(0, $data['kpis']['downtimes_needing_action_plan']);
        $this->assertCount(0, $data['downtimes_needing_action_plan']);
    }

    public function test_supplier_dashboard_excludes_other_supplier_downtimes_at_same_site(): void
    {
        $otherSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP2',
            'supplier_name' => 'Supplier Two',
            'status' => RecordStatus::Active,
        ]);

        $this->createPostedDowntime($this->site);
        $otherDowntime = $this->createPostedDowntime($this->site, 'Other supplier downtime');
        $otherDowntime->update(['supplier_id' => $otherSupplier->id]);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(1, $data['kpis']['downtimes_needing_action_plan']);
        $this->assertCount(1, $data['downtimes_needing_action_plan']);
        $this->assertNotSame($otherDowntime->id, $data['downtimes_needing_action_plan']->first()->id);
    }

    public function test_supplier_dashboard_excludes_downtime_with_action_plan(): void
    {
        $downtime = $this->createPostedDowntime($this->site);
        $downtime->actionPlans()->create([
            'action_plan_no' => 'DT-AP-TEST-1',
            'title' => 'Replace hose',
            'description' => 'Replace hose',
            'responsible_person' => 'Tech',
            'timeline_from' => '2026-01-01',
            'timeline_to' => '2026-01-07',
            'status' => DowntimeActionPlanStatus::Pending,
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);

        $data = app(DashboardService::class)->forSupplier($this->siteUser);

        $this->assertSame(0, $data['kpis']['downtimes_needing_action_plan']);
    }

    public function test_supplier_dashboard_page_shows_downtime_alert(): void
    {
        $this->createPostedDowntime($this->site);

        $response = $this->actingAs($this->siteUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('need an action plan', false);
        $response->assertSee('Downtimes Needing Action Plan', false);
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

    protected function createPostedDowntime(Site $site, string $title = 'Broken mast'): MheDowntime
    {
        return MheDowntime::query()->create([
            'title' => $title,
            'site_id' => $site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => $this->mheCategory->id,
            'supplier_id' => $this->supplier->id,
            'ref_unit_no' => 'FL-001',
            'date_of_incident' => now(),
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
            'posted_by' => $this->siteUser->id,
            'posted_at' => now(),
            'created_by' => $this->siteUser->id,
            'updated_by' => $this->siteUser->id,
        ]);
    }
}
