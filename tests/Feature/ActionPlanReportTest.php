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
use App\Models\MheDowntimeActionPlan;
use App\Models\MheType;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionPlanReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_plan_report_lists_both_types_and_links_to_the_parent(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);

        $site = Site::query()->create([
            'district_id' => District::query()->first()->id,
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
            'code' => 'ELEC',
            'name' => 'Electrical',
            'status' => RecordStatus::Active,
        ]);

        $pms = PmsHeader::query()->create([
            'pms_no' => 'PMS-100',
            'supplier_id' => $supplier->id,
            'site_id' => $site->id,
            'technician_name' => 'Tech',
            'date_from' => now(),
            'date_to' => now(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'U-1',
            'status' => PmsStatus::WithFindings,
            'created_by' => User::factory()->create()->id,
            'updated_by' => User::factory()->create()->id,
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

        $detail = PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => ChecklistAnswer::NoGood,
            'created_by' => $pms->created_by,
            'updated_by' => $pms->updated_by,
        ]);

        $pmsPlan = ActionPlan::query()->create([
            'action_plan_no' => 'AP-PMS-1',
            'pms_detail_id' => $detail->id,
            'title' => 'PMS brake repair',
            'description' => 'Replace pads',
            'responsible_person' => 'Tech',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => ActionPlanStatus::Pending,
            'created_by' => $pms->created_by,
            'updated_by' => $pms->updated_by,
        ]);

        $downtime = MheDowntime::query()->create([
            'title' => 'Hose leak',
            'site_id' => $site->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $mheType->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-1',
            'date_of_incident' => now(),
            'status' => DowntimeStatus::Posted,
            'created_by' => $pms->created_by,
            'updated_by' => $pms->updated_by,
        ]);

        $downtimePlan = MheDowntimeActionPlan::query()->create([
            'action_plan_no' => 'AP-DT-1',
            'mhe_downtime_id' => $downtime->id,
            'title' => 'Replace hose',
            'description' => 'Swap the hose',
            'responsible_person' => 'Tech',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => DowntimeActionPlanStatus::Pending,
            'created_by' => $pms->created_by,
            'updated_by' => $pms->updated_by,
        ]);

        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('reports.show', 'action-plans'));

        $response->assertOk();
        $response->assertSee('PMS');
        $response->assertSee('MHE Downtime');
        $response->assertSee('AP-PMS-1');
        $response->assertSee('AP-DT-1');
        $response->assertSee('PMS-100');
        $response->assertSee('#'.$downtime->id);
        $response->assertSee($pmsPlan->parentShowUrl(), false);
        $response->assertSee($downtimePlan->parentShowUrl(), false);

        $this->actingAs($admin)
            ->get(route('reports.show', ['type' => 'action-plans', 'source_type' => 'pms']))
            ->assertOk()
            ->assertSee('AP-PMS-1')
            ->assertDontSee('AP-DT-1');
    }
}
