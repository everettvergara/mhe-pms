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
use Tests\Concerns\CreatesMheInventoryForPms;
use Tests\TestCase;

class PmsFinalizeTest extends TestCase
{
    use CreatesMheInventoryForPms;
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected User $supplierUser;

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

        $this->createInventoryForPms($this->site, $this->supplier, $this->mheType);
    }

    public function test_cannot_save_as_final_when_no_good_has_no_remarks(): void
    {
        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();

        $response = $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), $this->finalizePayload($pms, [
            $detail->id => [
                'answer' => ChecklistAnswer::NoGood->value,
                'remarks' => '',
            ],
        ]));

        $response
            ->assertRedirect()
            ->assertSessionHas('error', 'Remarks are required for every No Good checklist answer.');

        $this->assertSame(PmsStatus::Draft, $pms->fresh()->status);
    }

    public function test_cannot_save_as_final_when_no_good_has_remarks_but_no_action_plan(): void
    {
        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();

        $response = $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), $this->finalizePayload($pms, [
            $detail->id => [
                'answer' => ChecklistAnswer::NoGood->value,
                'remarks' => 'Brake pads worn',
            ],
        ]));

        $response
            ->assertRedirect()
            ->assertSessionHas('error', 'At least one action item is required for No Good answer: Inspect brakes.');

        $this->assertSame(PmsStatus::Draft, $pms->fresh()->status);
    }

    public function test_can_save_as_final_when_no_good_has_remarks_and_action_plan(): void
    {
        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();

        ActionPlan::query()->create([
            'action_plan_no' => 'AP-001',
            'pms_detail_id' => $detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => ActionPlanStatus::Pending,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        $response = $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), $this->finalizePayload($pms, [
            $detail->id => [
                'answer' => ChecklistAnswer::NoGood->value,
                'remarks' => 'Brake pads worn',
            ],
        ]));

        $response
            ->assertRedirect(route('pms.show', $pms))
            ->assertSessionHas('success');

        $this->assertSame(PmsStatus::WithFindings, $pms->fresh()->status);
    }

    /**
     * @param  array<int, array{answer: string, remarks?: string}>  $detailOverrides
     * @return array<string, mixed>
     */
    protected function finalizePayload(PmsHeader $pms, array $detailOverrides = []): array
    {
        $details = $pms->pmsDetails->map(function (PmsDetail $detail) use ($detailOverrides) {
            $override = $detailOverrides[$detail->id] ?? [];

            return [
                'id' => $detail->id,
                'answer' => $override['answer'] ?? ChecklistAnswer::Good->value,
                'remarks' => $override['remarks'] ?? null,
            ];
        })->values()->all();

        return [
            'site_id' => $pms->site_id,
            'technician_name' => $pms->technician_name,
            'date_from' => $pms->date_from->format('Y-m-d'),
            'date_to' => $pms->date_to->format('Y-m-d'),
            'next_schedule_date' => $pms->next_schedule_date->format('Y-m-d'),
            'mhe_type_id' => $pms->mhe_type_id,
            'unit_number' => $pms->unit_number,
            'save_as' => 'final',
            'details' => $details,
        ];
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
            'supplier_id' => $this->supplierUser->supplier_id,
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
}
