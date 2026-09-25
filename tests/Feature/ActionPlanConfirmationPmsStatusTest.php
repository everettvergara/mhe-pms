<?php

namespace Tests\Feature;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\District;
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

class ActionPlanConfirmationPmsStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected User $supplierUser;

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

        $this->supplierUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        $this->supplierUser->sites()->attach($this->site->id);

        $this->adminUser = User::factory()->superAdmin()->create();
    }

    public function test_waiting_confirmation_on_draft_pms_is_not_listed(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::Draft);

        $this->actingAs($this->adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($actionPlan->action_plan_no);
    }

    public function test_waiting_confirmation_appears_after_pms_is_finalized(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::Draft);
        $pms = $actionPlan->pmsDetail->pmsHeader;

        $pms->update([
            'status' => PmsStatus::WithFindings,
            'submitted_by' => $this->supplierUser->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($actionPlan->action_plan_no);
    }

    public function test_waiting_confirmation_disappears_after_pms_is_reverted_to_draft(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::WithFindings);
        $pms = $actionPlan->pmsDetail->pmsHeader;

        $this->actingAs($this->supplierUser)
            ->post(route('pms.revert-to-draft', $pms))
            ->assertRedirect(route('pms.show', $pms));

        $this->actingAs($this->adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($actionPlan->action_plan_no);
    }

    public function test_admin_cannot_confirm_action_plan_on_draft_pms(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::Draft);

        $this->actingAs($this->adminUser)
            ->post(route('action-plan-confirmations.confirm', $actionPlan))
            ->assertForbidden();

        $this->assertSame(ActionPlanStatus::WaitingForFastConfirmation, $actionPlan->fresh()->status);
    }

    public function test_admin_cannot_reject_action_plan_on_draft_pms(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::Draft);

        $this->actingAs($this->adminUser)
            ->post(route('action-plan-confirmations.reject', $actionPlan), [
                'rejection_remarks' => 'Incomplete work',
            ])
            ->assertForbidden();

        $this->assertSame(ActionPlanStatus::WaitingForFastConfirmation, $actionPlan->fresh()->status);
    }

    public function test_supplier_cannot_mark_implemented_when_pms_is_draft(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::Draft, ActionPlanStatus::Pending);

        $this->actingAs($this->supplierUser)
            ->post(route('action-plans.mark-implemented', $actionPlan), [
                'unit_safe_guaranteed' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(ActionPlanStatus::Pending, $actionPlan->fresh()->status);
    }

    public function test_mark_implemented_requires_unit_safety_guarantee(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::WithFindings, ActionPlanStatus::Pending);

        $this->actingAs($this->supplierUser)
            ->post(route('action-plans.mark-implemented', $actionPlan))
            ->assertRedirect()
            ->assertSessionHas('error');

        $fresh = $actionPlan->fresh();
        $this->assertSame(ActionPlanStatus::Pending, $fresh->status);
        $this->assertFalse($fresh->unit_safe_guaranteed);
    }

    public function test_implemented_comment_records_unit_safety_guarantee_and_listings_show_it(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::WithFindings, ActionPlanStatus::Pending);

        $this->actingAs($this->supplierUser)
            ->post(route('action-plans.comment', $actionPlan), [
                'comment' => 'Still working',
                'progress_status' => 'Pending',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ActionPlanStatus::Pending, $actionPlan->fresh()->status);

        $this->actingAs($this->supplierUser)
            ->from(route('action-plans.show', $actionPlan))
            ->post(route('action-plans.comment', $actionPlan), [
                'comment' => 'Work finished',
                'progress_status' => 'Implemented',
            ])
            ->assertRedirect(route('action-plans.show', $actionPlan))
            ->assertSessionHasErrors('unit_safe_guaranteed');

        $this->assertSame(ActionPlanStatus::Pending, $actionPlan->fresh()->status);
        $this->assertSame(1, $actionPlan->comments()->count());

        $this->actingAs($this->supplierUser)
            ->post(route('action-plans.comment', $actionPlan), [
                'comment' => 'Work finished',
                'progress_status' => 'Implemented',
                'unit_safe_guaranteed' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $actionPlan->refresh();
        $this->assertSame(ActionPlanStatus::WaitingForFastConfirmation, $actionPlan->status);
        $this->assertTrue($actionPlan->unit_safe_guaranteed);
        $this->assertSame($this->supplierUser->id, $actionPlan->unit_safe_guaranteed_by);
        $this->assertNotNull($actionPlan->unit_safe_guaranteed_at);

        $pms = $actionPlan->pmsDetail->pmsHeader;

        $supplierList = $this->actingAs($this->supplierUser)
            ->get(route('pms.show', $pms));
        $supplierList->assertOk()->assertSee('Unit safe')->assertSee($actionPlan->action_plan_no);
        $this->assertUnitSafeCheckboxChecked($supplierList->getContent());

        $adminList = $this->actingAs($this->adminUser)
            ->get(route('pms.show', ['pms' => $pms, 'action_plan' => $actionPlan->id]));
        $adminList->assertOk()->assertSee('Unit safe')->assertSee($actionPlan->action_plan_no);
        $adminList->assertSee('data-ap-action="detail"', false);
        $adminList->assertSee('id="action-plan-deeplink"', false);
        $adminList->assertSee(route('action-plan-confirmations.confirm', $actionPlan), false);
        $this->assertUnitSafeCheckboxChecked($adminList->getContent());

        $this->actingAs($this->adminUser)
            ->get(route('action-plan-confirmations.show', $actionPlan))
            ->assertRedirect($actionPlan->parentShowUrl());
    }

    public function test_rejecting_action_plan_clears_unit_safety_guarantee(): void
    {
        $actionPlan = $this->createActionPlanOnPms(PmsStatus::WithFindings, ActionPlanStatus::WaitingForFastConfirmation);
        $actionPlan->update([
            'unit_safe_guaranteed' => true,
            'unit_safe_guaranteed_by' => $this->supplierUser->id,
            'unit_safe_guaranteed_at' => now(),
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('action-plan-confirmations.reject', $actionPlan), [
                'rejection_remarks' => 'Not safe yet',
            ])
            ->assertRedirect();

        $actionPlan->refresh();
        $this->assertSame(ActionPlanStatus::Rejected, $actionPlan->status);
        $this->assertFalse($actionPlan->unit_safe_guaranteed);
        $this->assertNull($actionPlan->unit_safe_guaranteed_by);
        $this->assertNull($actionPlan->unit_safe_guaranteed_at);
    }

    protected function assertUnitSafeCheckboxChecked(string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/checked(?:\s[^>]*)?\saria-label="I guarantee that the unit is safe to use"/',
            $html,
        );
    }

    protected function createActionPlanOnPms(
        PmsStatus $pmsStatus,
        ActionPlanStatus $actionPlanStatus = ActionPlanStatus::WaitingForFastConfirmation,
    ): ActionPlan {
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
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'status' => $pmsStatus,
            'submitted_by' => in_array($pmsStatus, [PmsStatus::WithFindings, PmsStatus::NoFindings], true)
                ? $this->supplierUser->id
                : null,
            'submitted_at' => in_array($pmsStatus, [PmsStatus::WithFindings, PmsStatus::NoFindings], true)
                ? now()
                : null,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        $detail = PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'answer' => ChecklistAnswer::NoGood,
            'remarks' => 'Needs repair',
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);

        return ActionPlan::query()->create([
            'action_plan_no' => 'AP-TEST-'.fake()->unique()->numerify('###'),
            'pms_detail_id' => $detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now(),
            'timeline_to' => now()->addWeek(),
            'status' => $actionPlanStatus,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);
    }
}
