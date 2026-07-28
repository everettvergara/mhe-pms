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

class PmsRevertToDraftTest extends TestCase
{
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
    }

    public function test_supplier_can_revert_no_findings_pms_to_draft(): void
    {
        $pms = $this->createSubmittedPms(PmsStatus::NoFindings);

        $response = $this->actingAs($this->supplierUser)->post(route('pms.revert-to-draft', $pms));

        $response
            ->assertRedirect(route('pms.show', $pms))
            ->assertSessionHas('success');

        $pms->refresh();
        $this->assertSame(PmsStatus::Draft, $pms->status);
        $this->assertNull($pms->submitted_by);
        $this->assertNull($pms->submitted_at);
    }

    public function test_supplier_can_revert_with_findings_pms_even_when_action_plan_is_confirmed(): void
    {
        $pms = $this->createSubmittedPms(PmsStatus::WithFindings);
        $detail = $pms->pmsDetails->first();

        ActionPlan::query()->create([
            'action_plan_no' => 'AP-001',
            'pms_detail_id' => $detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => ActionPlanStatus::Confirmed,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        $response = $this->actingAs($this->supplierUser)->post(route('pms.revert-to-draft', $pms));

        $response
            ->assertRedirect(route('pms.show', $pms))
            ->assertSessionHas('success');

        $this->assertSame(PmsStatus::Draft, $pms->fresh()->status);
        $this->assertSame(1, ActionPlan::query()->where('pms_detail_id', $detail->id)->count());
    }

    public function test_cannot_revert_draft_pms(): void
    {
        $pms = $this->createSubmittedPms(PmsStatus::Draft);

        $response = $this->actingAs($this->supplierUser)->post(route('pms.revert-to-draft', $pms));

        $response->assertForbidden();
        $this->assertSame(PmsStatus::Draft, $pms->fresh()->status);
    }

    public function test_cannot_revert_cancelled_pms(): void
    {
        $pms = $this->createSubmittedPms(PmsStatus::Cancelled);

        $response = $this->actingAs($this->supplierUser)->post(route('pms.revert-to-draft', $pms));

        $response->assertForbidden();
        $this->assertSame(PmsStatus::Cancelled, $pms->fresh()->status);
    }

    public function test_supplier_cannot_revert_another_suppliers_pms(): void
    {
        $otherSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP2',
            'supplier_name' => 'Supplier Two',
            'status' => RecordStatus::Active,
        ]);

        $otherUser = User::factory()->supplier()->create([
            'supplier_id' => $otherSupplier->id,
        ]);
        $otherUser->sites()->attach($this->site->id);

        $pms = $this->createSubmittedPms(PmsStatus::NoFindings, $otherSupplier->id, $otherUser->id);

        $response = $this->actingAs($this->supplierUser)->post(route('pms.revert-to-draft', $pms));

        $response->assertForbidden();
        $this->assertSame(PmsStatus::NoFindings, $pms->fresh()->status);
    }

    protected function createSubmittedPms(
        PmsStatus $status,
        ?int $supplierId = null,
        ?int $userId = null,
    ): PmsHeader {
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
            'pms_no' => 'PMS-TEST-'.fake()->unique()->numerify('###'),
            'supplier_id' => $supplierId ?? $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'serial_number' => 'S-001',
            'status' => $status,
            'submitted_by' => in_array($status, [PmsStatus::WithFindings, PmsStatus::NoFindings], true)
                ? ($userId ?? $this->supplierUser->id)
                : null,
            'submitted_at' => in_array($status, [PmsStatus::WithFindings, PmsStatus::NoFindings], true) ? now() : null,
            'created_by' => $userId ?? $this->supplierUser->id,
            'updated_by' => $userId ?? $this->supplierUser->id,
        ]);

        PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => $status === PmsStatus::WithFindings ? ChecklistAnswer::NoGood : ChecklistAnswer::Good,
            'remarks' => $status === PmsStatus::WithFindings ? 'Needs repair' : null,
            'created_by' => $userId ?? $this->supplierUser->id,
            'updated_by' => $userId ?? $this->supplierUser->id,
        ]);

        return $pms->load('pmsDetails');
    }
}
