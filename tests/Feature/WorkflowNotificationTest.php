<?php

namespace Tests\Feature;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
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
use App\Notifications\DowntimeActionPlanWorkflowNotification;
use App\Notifications\PmsActionPlanWorkflowNotification;
use App\Notifications\PmsSubmittedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesMheInventoryForPms;
use Tests\TestCase;

class WorkflowNotificationTest extends TestCase
{
    use CreatesMheInventoryForPms;
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected MheCategory $mheCategory;

    protected User $fastAdmin;

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

        $this->mheCategory = MheCategory::query()->create([
            'code' => 'Damage',
            'name' => 'Damage',
            'status' => RecordStatus::Active,
        ]);

        $this->fastAdmin = User::factory()->create([
            'email' => 'fastadmin@example.com',
        ]);
        $this->fastAdmin->sites()->attach($this->site->id);

        $this->supplierUser = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
            'email' => 'supplier@example.com',
        ]);
        $this->supplierUser->suppliers()->attach($this->supplier->id);
        $this->supplierUser->sites()->attach($this->site->id);

        $this->createInventoryForPms($this->site, $this->supplier, $this->mheType);
    }

    public function test_pms_finalize_notifies_fast_admin(): void
    {
        Notification::fake();

        $pms = $this->createDraftPmsWithActionPlan();

        $this->actingAs($this->supplierUser)->put(route('pms.update', $pms), $this->finalizePayload($pms))
            ->assertRedirect(route('pms.show', $pms));

        Notification::assertSentTo($this->fastAdmin, PmsSubmittedNotification::class);
    }

    public function test_pms_action_plan_create_notifies_fast_admin(): void
    {
        Notification::fake();

        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();

        $this->actingAs($this->supplierUser)->postJson(route('action-plans.store'), [
            'pms_detail_id' => $detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
        ])->assertOk();

        Notification::assertSentTo($this->fastAdmin, PmsActionPlanWorkflowNotification::class);
    }

    public function test_pms_action_plan_implemented_notifies_fast_admin(): void
    {
        Notification::fake();

        $actionPlan = $this->createSubmittedPmsActionPlan(ActionPlanStatus::Pending);

        $this->actingAs($this->supplierUser)
            ->post(route('action-plans.mark-implemented', $actionPlan), [
                'unit_safe_guaranteed' => '1',
            ])
            ->assertRedirect();

        $actionPlan->refresh();
        $this->assertTrue($actionPlan->unit_safe_guaranteed);
        $this->assertSame($this->supplierUser->id, $actionPlan->unit_safe_guaranteed_by);

        Notification::assertSentTo($this->fastAdmin, PmsActionPlanWorkflowNotification::class);
    }

    public function test_pms_action_plan_confirmed_notifies_supplier(): void
    {
        Notification::fake();

        $actionPlan = $this->createSubmittedPmsActionPlan(ActionPlanStatus::WaitingForFastConfirmation);

        $this->actingAs($this->fastAdmin)
            ->post(route('action-plan-confirmations.confirm', $actionPlan))
            ->assertRedirect($actionPlan->parentShowUrl());

        Notification::assertSentTo($this->supplierUser, PmsActionPlanWorkflowNotification::class);
    }

    public function test_pms_action_plan_rejected_notifies_supplier(): void
    {
        Notification::fake();

        $actionPlan = $this->createSubmittedPmsActionPlan(ActionPlanStatus::WaitingForFastConfirmation);

        $this->actingAs($this->fastAdmin)
            ->post(route('action-plan-confirmations.reject', $actionPlan), [
                'rejection_remarks' => 'Incomplete work',
            ])
            ->assertRedirect($actionPlan->parentShowUrl());

        Notification::assertSentTo($this->supplierUser, PmsActionPlanWorkflowNotification::class);
    }

    public function test_downtime_action_plan_create_notifies_fast_admin(): void
    {
        Notification::fake();

        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->supplierUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload())
            ->assertRedirect(route('mhe-downtimes.show', $downtime));

        Notification::assertSentTo($this->fastAdmin, DowntimeActionPlanWorkflowNotification::class);
    }

    public function test_downtime_action_plan_implemented_notifies_fast_admin(): void
    {
        Notification::fake();

        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->supplierUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());
        $actionPlan = $downtime->actionPlans()->first();

        $this->actingAs($this->supplierUser)
            ->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]), [
                'unit_safe_guaranteed' => '1',
            ])
            ->assertRedirect(route('mhe-downtimes.show', $downtime));

        $actionPlan->refresh();
        $this->assertTrue($actionPlan->unit_safe_guaranteed);
        $this->assertSame($this->supplierUser->id, $actionPlan->unit_safe_guaranteed_by);

        Notification::assertSentTo($this->fastAdmin, DowntimeActionPlanWorkflowNotification::class);
    }

    public function test_downtime_action_plan_confirmed_notifies_supplier(): void
    {
        Notification::fake();

        $downtime = $this->createPostedDowntime();
        $actionPlan = $this->createWaitingDowntimeActionPlan($downtime);

        $this->actingAs($this->fastAdmin)
            ->post(route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan), [
                'date_implemented' => $actionPlan->date_implemented?->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect($actionPlan->parentShowUrl());

        Notification::assertSentTo($this->supplierUser, DowntimeActionPlanWorkflowNotification::class);
    }

    public function test_downtime_action_plan_rejected_notifies_supplier(): void
    {
        Notification::fake();

        $downtime = $this->createPostedDowntime();
        $actionPlan = $this->createWaitingDowntimeActionPlan($downtime);

        $this->actingAs($this->fastAdmin)
            ->post(route('mhe-downtime-action-plan-confirmations.reject', $actionPlan), [
                'rejection_remarks' => 'Incomplete work',
            ])
            ->assertRedirect($actionPlan->parentShowUrl());

        Notification::assertSentTo($this->supplierUser, DowntimeActionPlanWorkflowNotification::class);
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
            'supplier_id' => $this->supplier->id,
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

    protected function createDraftPmsWithActionPlan(): PmsHeader
    {
        $pms = $this->createDraftPms();
        $detail = $pms->pmsDetails->first();

        $this->actingAs($this->supplierUser)->postJson(route('action-plans.store'), [
            'pms_detail_id' => $detail->id,
            'title' => 'Replace brake pads',
            'description' => 'Order and install new pads',
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
        ])->assertOk();

        return $pms->fresh()->load('pmsDetails');
    }

    /**
     * @return array<string, mixed>
     */
    protected function finalizePayload(PmsHeader $pms): array
    {
        $details = $pms->pmsDetails->map(fn (PmsDetail $detail) => [
            'id' => $detail->id,
            'answer' => ChecklistAnswer::NoGood->value,
            'remarks' => 'Brake pads worn',
        ])->values()->all();

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

    protected function createSubmittedPmsActionPlan(ActionPlanStatus $status): ActionPlan
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
            'pms_no' => 'PMS-TEST-'.fake()->unique()->numerify('###'),
            'supplier_id' => $this->supplier->id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'status' => PmsStatus::WithFindings,
            'submitted_by' => $this->supplierUser->id,
            'submitted_at' => now(),
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
            'status' => $status,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);
    }

    protected function createPostedDowntime(): MheDowntime
    {
        return MheDowntime::query()->create([
            'title' => 'Broken mast',
            'site_id' => $this->site->id,
            'mhe_type_id' => $this->mheType->id,
            'mhe_category_id' => $this->mheCategory->id,
            'supplier_id' => $this->supplier->id,
            'ref_unit_no' => 'FL-001',
            'date_of_incident' => '2026-01-01 08:00:00',
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
            'created_by' => $this->supplierUser->id,
            'updated_by' => $this->supplierUser->id,
        ]);
    }

    protected function createWaitingDowntimeActionPlan(MheDowntime $downtime): MheDowntimeActionPlan
    {
        $this->actingAs($this->supplierUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());
        $actionPlan = $downtime->actionPlans()->first();

        $this->actingAs($this->supplierUser)
            ->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]), [
                'unit_safe_guaranteed' => '1',
            ]);

        return $actionPlan->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function actionItemPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Replace hydraulic hose',
            'description' => 'Order and install replacement hose',
            'responsible_person' => 'Tech One',
            'timeline_from' => now()->toDateString(),
            'timeline_to' => now()->addWeek()->toDateString(),
        ], $overrides);
    }
}
