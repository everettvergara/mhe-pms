<?php

namespace Tests\Feature;

use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
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

class PmsActionPlanModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $supplierUser;

    protected PmsHeader $pms;

    protected PmsDetail $detail;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->supplierUser = User::factory()->supplier()->create([
            'supplier_id' => $supplier->id,
        ]);
        $this->supplierUser->sites()->attach($site->id);

        $this->pms = PmsHeader::query()->create([
            'pms_no' => 'PMS-TEST-001',
            'supplier_id' => $supplier->id,
            'site_id' => $site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'U-001',
            'serial_number' => 'S-001',
            'status' => PmsStatus::Draft,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        $this->detail = PmsDetail::query()->create([
            'pms_header_id' => $this->pms->id,
            'checklist_item_id' => $item->id,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);
    }

    public function test_pms_draft_update_returns_json_for_ajax_requests(): void
    {
        $response = $this->actingAs($this->supplierUser)->putJson(route('pms.update', $this->pms), [
            'site_id' => $this->pms->site_id,
            'technician_name' => $this->pms->technician_name,
            'date_from' => $this->pms->date_from->format('Y-m-d'),
            'date_to' => $this->pms->date_to->format('Y-m-d'),
            'next_schedule_date' => $this->pms->next_schedule_date->format('Y-m-d'),
            'mhe_type_id' => $this->pms->mhe_type_id,
            'unit_number' => $this->pms->unit_number,
            'serial_number' => $this->pms->serial_number,
            'save_as' => 'draft',
            'details' => [
                [
                    'id' => $this->detail->id,
                    'answer' => ChecklistAnswer::NoGood->value,
                    'remarks' => 'Worn pads',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'PMS draft saved successfully.');

        $this->assertSame(ChecklistAnswer::NoGood, $this->detail->fresh()->answer);
        $this->assertSame('Worn pads', $this->detail->fresh()->remarks);
    }

    public function test_pms_draft_save_allows_no_good_without_remarks(): void
    {
        $response = $this->actingAs($this->supplierUser)->putJson(route('pms.update', $this->pms), [
            'site_id' => $this->pms->site_id,
            'technician_name' => $this->pms->technician_name,
            'date_from' => $this->pms->date_from->format('Y-m-d'),
            'date_to' => $this->pms->date_to->format('Y-m-d'),
            'next_schedule_date' => $this->pms->next_schedule_date->format('Y-m-d'),
            'mhe_type_id' => $this->pms->mhe_type_id,
            'unit_number' => $this->pms->unit_number,
            'serial_number' => $this->pms->serial_number,
            'save_as' => 'draft',
            'details' => [
                [
                    'id' => $this->detail->id,
                    'answer' => ChecklistAnswer::NoGood->value,
                    'remarks' => '',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'PMS draft saved successfully.');

        $this->assertSame(ChecklistAnswer::NoGood, $this->detail->fresh()->answer);
        $this->assertNull($this->detail->fresh()->remarks);
    }

    public function test_action_plan_store_works_after_draft_save_with_no_good_and_no_remarks(): void
    {
        $this->actingAs($this->supplierUser)->putJson(route('pms.update', $this->pms), [
            'site_id' => $this->pms->site_id,
            'technician_name' => $this->pms->technician_name,
            'date_from' => $this->pms->date_from->format('Y-m-d'),
            'date_to' => $this->pms->date_to->format('Y-m-d'),
            'next_schedule_date' => $this->pms->next_schedule_date->format('Y-m-d'),
            'mhe_type_id' => $this->pms->mhe_type_id,
            'unit_number' => $this->pms->unit_number,
            'serial_number' => $this->pms->serial_number,
            'save_as' => 'draft',
            'details' => [
                [
                    'id' => $this->detail->id,
                    'answer' => ChecklistAnswer::NoGood->value,
                    'remarks' => '',
                ],
            ],
        ])->assertOk();

        $response = $this->actingAs($this->supplierUser)->postJson(route('action-plans.store'), [
            'pms_detail_id' => $this->detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->format('Y-m-d'),
            'timeline_to' => now()->addWeek()->format('Y-m-d'),
            'return_to' => 'pms',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Action Plan created successfully.');

        $this->assertSame(1, $this->detail->fresh()->actionPlans()->count());
    }
}
