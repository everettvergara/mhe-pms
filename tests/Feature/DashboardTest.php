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
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('PMS for the month', false);
        $response->assertSee('0/0', false);
        $response->assertSee('Waiting for my confirmation', false);
        $response->assertSee('Currently down units', false);
        $response->assertSee('Units in my sites', false);
        $response->assertSee('Show all', false);
        $response->assertSee(route('dashboard.pms-schedule'), false);
        $response->assertSee('Quick shortcuts', false);
        $response->assertSee('MHE Downtimes', false);
        $response->assertSee('Preventive Maintenance', false);
        $response->assertSee('PMS Schedule', false);
        $response->assertSee('MHE Downtime Summary', false);
        $response->assertSee('MHE + PMS Site Utilization', false);
        $response->assertSee('MHE Uptime Summary', false);
        $response->assertSee(route('mhes.utilization'), false);
        $response->assertDontSee('Total PMS', false);
        $response->assertDontSee('Recent Activities', false);
    }

    public function test_admin_dashboard_counts_monthly_pms_and_units_still_down(): void
    {
        [$admin, $site] = $this->assignedAdmin();
        $supplier = $this->supplier();
        $type = $this->mheType();
        $category = $this->mheCategory();

        $done = $this->inventory($site, $supplier, $type, 'UNIT-YES');
        $open = $this->inventory($site, $supplier, $type, 'UNIT-NO');
        $lastMonth = $this->inventory($site, $supplier, $type, 'UNIT-LAST');

        $this->pms($site, $supplier, $type, $admin, 'unit-yes', PmsStatus::NoFindings, now());
        $this->pms($site, $supplier, $type, $admin, 'UNIT-NO', PmsStatus::Draft, now());
        $this->pms($site, $supplier, $type, $admin, 'UNIT-LAST', PmsStatus::WithFindings, now()->subMonth());

        $this->downtime($site, $supplier, $type, $category, $admin, $done, 'Still down A');
        $this->downtime($site, $supplier, $type, $category, $admin, $done, 'Still down B');
        $this->downtime($site, $supplier, $type, $category, $admin, $open, 'Already up', now());

        $data = app(DashboardService::class)->forAdmin($admin);

        $this->assertSame(1, $data['pms_month_done']);
        $this->assertSame(3, $data['pms_month_total']);
        $this->assertSame(1, $data['currently_down_units']);
        $this->assertSame(
            ['UNIT-LAST', 'UNIT-NO', 'UNIT-YES'],
            $data['site_units']->pluck('unit_no')->all(),
        );
        $this->assertFalse((bool) $data['site_units'][0]->pms_this_month);
        $this->assertFalse((bool) $data['site_units'][1]->pms_this_month);
        $this->assertTrue((bool) $data['site_units'][2]->pms_this_month);
        $this->assertTrue($data['site_units']->firstWhere('unit_no', 'UNIT-YES')->is($done));
    }

    public function test_admin_dashboard_hides_records_outside_assigned_sites(): void
    {
        [$admin, $site] = $this->assignedAdmin();
        $otherSite = $this->site('SITE-B', 'Other Site');
        $supplier = $this->supplier();
        $type = $this->mheType();
        $category = $this->mheCategory();

        $this->inventory($site, $supplier, $type, 'UNIT-MINE');
        $this->inventory($otherSite, $supplier, $type, 'UNIT-OTHER');

        $minePms = $this->pms($site, $supplier, $type, $admin, 'UNIT-MINE', PmsStatus::NoFindings, now());
        $otherPms = $this->pms($otherSite, $supplier, $type, $admin, 'UNIT-OTHER', PmsStatus::NoFindings, now());
        $this->actionPlan($minePms, $admin, 'Visible brake job');
        $this->actionPlan($otherPms, $admin, 'Hidden brake job');

        $mineDown = $this->downtime($site, $supplier, $type, $category, $admin, null, 'Mine is down', null, 'UNIT-MINE');
        $otherDown = $this->downtime($otherSite, $supplier, $type, $category, $admin, null, 'Theirs is down', null, 'UNIT-OTHER');
        $this->downtimePlan($mineDown, $admin, 'Visible downtime plan');
        $this->downtimePlan($otherDown, $admin, 'Hidden downtime plan');

        $data = app(DashboardService::class)->forAdmin($admin);

        $this->assertSame(1, $data['pms_month_done']);
        $this->assertSame(1, $data['pms_month_total']);
        $this->assertSame(1, $data['currently_down_units']);
        $this->assertSame(1, $data['waiting_confirmation']);
        $this->assertSame(1, $data['downtime_waiting_confirmation']);
        $this->assertCount(1, $data['site_units']);
        $this->assertSame('UNIT-MINE', $data['site_units']->first()->unit_no);
        $this->assertSame('Visible brake job', $data['pending_confirmations']->first()->title);
        $this->assertSame('Visible downtime plan', $data['downtime_pending_confirmations']->first()->title);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('1/1', false);
        $response->assertSee('Assigned Site', false);
        $response->assertSee('UNIT-MINE', false);
        $response->assertSee('Visible brake job', false);
        $response->assertSee('Visible downtime plan', false);
        $response->assertDontSee('Other Site', false);
        $response->assertDontSee('UNIT-OTHER', false);
        $response->assertDontSee('Hidden brake job', false);
        $response->assertDontSee('Hidden downtime plan', false);
    }

    /**
     * @return array{0: User, 1: Site}
     */
    protected function assignedAdmin(): array
    {
        $site = $this->site('SITE-A', 'Assigned Site');
        $admin = User::factory()->create();
        $admin->sites()->attach($site->id);

        return [$admin, $site];
    }

    protected function site(string $code, string $name): Site
    {
        return Site::query()->create([
            'district_id' => District::query()->first()->id,
            'site_code' => $code,
            'site_name' => $name,
            'status' => RecordStatus::Active,
        ]);
    }

    protected function supplier(): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);
    }

    protected function mheType(): MheType
    {
        return MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);
    }

    protected function mheCategory(): MheCategory
    {
        return MheCategory::query()->create([
            'code' => 'Damage',
            'name' => 'Damage',
            'status' => RecordStatus::Active,
        ]);
    }

    protected function inventory(Site $site, Supplier $supplier, MheType $type, string $unitNo): MheInventory
    {
        return MheInventory::query()->create([
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $type->id,
            'unit_no' => $unitNo,
            'equipment_status' => RecordStatus::Active,
        ]);
    }

    protected function pms(
        Site $site,
        Supplier $supplier,
        MheType $type,
        User $user,
        string $unitNumber,
        PmsStatus $status,
        mixed $dateFrom,
    ): PmsHeader {
        $submitted = in_array($status, [PmsStatus::WithFindings, PmsStatus::NoFindings], true);

        return PmsHeader::query()->create([
            'pms_no' => 'PMS-'.fake()->unique()->numerify('####'),
            'supplier_id' => $supplier->id,
            'site_id' => $site->id,
            'technician_name' => 'Tech One',
            'date_from' => $dateFrom,
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $type->id,
            'unit_number' => $unitNumber,
            'status' => $status,
            'submitted_by' => $submitted ? $user->id : null,
            'submitted_at' => $submitted ? now() : null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    protected function actionPlan(PmsHeader $pms, User $user, string $title): ActionPlan
    {
        $group = ChecklistGroup::query()->firstOrCreate(
            ['group_name' => 'General'],
            ['sequence' => 1, 'status' => RecordStatus::Active],
        );

        $item = ChecklistItem::query()->firstOrCreate(
            ['checklist_group_id' => $group->id, 'description' => 'Inspect brakes'],
            ['sequence' => 1, 'status' => RecordStatus::Active],
        );

        $detail = PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => ChecklistAnswer::NoGood,
            'remarks' => 'Needs repair',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return ActionPlan::query()->create([
            'action_plan_no' => 'AP-'.fake()->unique()->numerify('####'),
            'pms_detail_id' => $detail->id,
            'title' => $title,
            'description' => $title,
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => ActionPlanStatus::WaitingForFastConfirmation,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    protected function downtime(
        Site $site,
        Supplier $supplier,
        MheType $type,
        MheCategory $category,
        User $user,
        ?MheInventory $inventory,
        string $title,
        mixed $uptime = null,
        ?string $unitNo = null,
    ): MheDowntime {
        return MheDowntime::query()->create([
            'title' => $title,
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'mhe_inventory_id' => $inventory?->id,
            'supplier_id' => $supplier->id,
            'ref_unit_no' => $unitNo ?? $inventory?->unit_no ?? 'FL-000',
            'date_of_incident' => now(),
            'uptime' => $uptime,
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
            'posted_by' => $user->id,
            'posted_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    protected function downtimePlan(MheDowntime $downtime, User $user, string $title): void
    {
        $downtime->actionPlans()->create([
            'action_plan_no' => 'DT-AP-'.fake()->unique()->numerify('####'),
            'title' => $title,
            'description' => $title,
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
            'status' => DowntimeActionPlanStatus::WaitingForFastConfirmation,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
