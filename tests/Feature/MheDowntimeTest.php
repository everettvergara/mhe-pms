<?php

namespace Tests\Feature;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MheDowntimeService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MheDowntimeTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected Site $otherSite;

    protected MheType $mheType;

    protected MheCategory $mheCategory;

    protected MheInventory $inventory;

    protected User $siteUser;

    protected User $otherSiteUser;

    protected User $fastAdmin;

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

        $this->inventory = MheInventory::query()->create([

            'site_id' => $this->site->id,

            'supplier_id' => $this->supplier->id,

            'mhe_type_id' => $this->mheType->id,

            'unit_no' => 'FL-001',

            'equipment_status' => RecordStatus::Active,

        ]);

        $this->siteUser = User::factory()->supplier()->create([

            'supplier_id' => $this->supplier->id,

        ]);

        $this->siteUser->sites()->attach($this->site->id);

        $this->otherSiteUser = User::factory()->supplier()->create([

            'supplier_id' => $this->supplier->id,

        ]);

        $this->otherSiteUser->sites()->attach($this->otherSite->id);

        $this->fastAdmin = User::factory()->create();
        $this->fastAdmin->sites()->attach($this->site->id);

    }

    public function test_user_can_create_draft_downtime(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), $this->payload());

        $downtime = MheDowntime::query()->first();

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertSame(DowntimeStatus::Draft, $downtime->status);

        $this->assertNull($downtime->uptime);

        $this->assertNull($downtime->hours_down);

        $this->assertSame($this->inventory->id, $downtime->mhe_inventory_id);

        $this->assertSame($this->supplier->id, $downtime->supplier_id);

    }

    public function test_create_with_blank_ref_unit_requires_supplier(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'ref_unit_no' => '',

        ]);

        $response->assertSessionHasErrors('supplier_id');

        $this->assertSame(0, MheDowntime::query()->count());

    }

    public function test_create_with_blank_ref_unit_and_supplier_succeeds(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'ref_unit_no' => '',

            'supplier_id' => $this->supplier->id,

        ]);

        $downtime = MheDowntime::query()->first();

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertNull($downtime->mhe_inventory_id);

        $this->assertSame($this->supplier->id, $downtime->supplier_id);

        $this->assertSame('', $downtime->ref_unit_no);

    }

    public function test_create_with_unknown_unit_requires_supplier(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'ref_unit_no' => 'UNKNOWN-99',

        ]);

        $response->assertSessionHasErrors('supplier_id');

        $this->assertSame(0, MheDowntime::query()->count());

    }

    public function test_create_with_unknown_unit_and_supplier_succeeds(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'ref_unit_no' => 'UNKNOWN-99',

            'supplier_id' => $this->supplier->id,

        ]);

        $downtime = MheDowntime::query()->first();

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertNull($downtime->mhe_inventory_id);

        $this->assertSame($this->supplier->id, $downtime->supplier_id);

        $this->assertSame('UNKNOWN-99', $downtime->ref_unit_no);

    }

    public function test_create_with_missing_site_id_fails_validation(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'site_id' => '',

        ]);

        $response->assertSessionHasErrors('site_id');

        $this->assertSame(0, MheDowntime::query()->count());

    }

    public function test_create_with_unassigned_site_id_fails_validation(): void
    {

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'site_id' => $this->otherSite->id,

        ]);

        $response->assertSessionHasErrors('site_id');

        $this->assertSame(0, MheDowntime::query()->count());

    }

    public function test_create_saves_site_id_not_site_name(): void
    {

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), $this->payload());

        $downtime = MheDowntime::query()->first();

        $this->assertSame($this->site->id, $downtime->site_id);

        $this->assertSame('Site One', $downtime->site->site_name);

    }

    public function test_lookup_site_matches_by_site_name(): void
    {
        $response = $this->actingAs($this->siteUser)->getJson(route('mhe-downtimes.lookup-site', [
            'q' => 'Site One',
        ]));

        $response->assertOk();
        $response->assertJson([
            'matched' => true,
            'id' => $this->site->id,
            'label' => 'Site One (SITE1)',
        ]);
    }

    public function test_search_units_is_scoped_to_site(): void
    {

        MheInventory::query()->create([

            'site_id' => $this->otherSite->id,

            'supplier_id' => $this->supplier->id,

            'mhe_type_id' => $this->mheType->id,

            'unit_no' => 'FL-OTHER',

            'equipment_status' => RecordStatus::Active,

        ]);

        $response = $this->actingAs($this->siteUser)->getJson(route('mhe-downtimes.search-units', [

            'site_id' => $this->site->id,

        ]));

        $response->assertOk();

        $unitNumbers = collect($response->json())->pluck('unit_no')->all();

        $this->assertContains('FL-001', $unitNumbers);

        $this->assertNotContains('FL-OTHER', $unitNumbers);

        $response->assertJsonFragment([
            'unit_no' => 'FL-001',
            'mhe_type_id' => $this->mheType->id,
            'supplier_id' => $this->supplier->id,
            'mhe_type_label' => 'FL — Forklift',
            'supplier_name' => 'Supplier One',
            'label' => 'FL-001 (FL — Forklift)',
        ]);

    }

    public function test_site_dropdown_returns_every_assigned_site_for_downtime_and_pms(): void
    {
        $districtId = $this->site->district_id;

        for ($i = 1; $i <= 24; $i++) {
            $site = Site::query()->create([
                'district_id' => $districtId,
                'site_code' => 'S'.$i,
                'site_name' => 'Assigned Site '.$i,
                'status' => RecordStatus::Active,
            ]);
            $this->fastAdmin->sites()->attach($site->id);
            $this->siteUser->sites()->attach($site->id);
        }

        $expected = Site::query()
            ->whereIn('id', $this->siteUser->sites()->pluck('sites.id'))
            ->where('status', RecordStatus::Active)
            ->orderBy('site_name')
            ->pluck('site_name')
            ->all();

        $downtime = $this->actingAs($this->fastAdmin)->getJson(route('mhe-downtimes.search-sites'));
        $pms = $this->actingAs($this->siteUser)->getJson(route('pms.search-sites'));

        $downtime->assertOk();
        $pms->assertOk();
        $this->assertSame($expected, collect($downtime->json())->pluck('site_name')->all());
        $this->assertSame($expected, collect($pms->json())->pluck('site_name')->all());
        $this->assertGreaterThan(20, count($expected));
    }

    public function test_lookup_unit_returns_type_and_supplier(): void
    {
        $response = $this->actingAs($this->siteUser)->getJson(route('mhe-downtimes.lookup-unit', [
            'site_id' => $this->site->id,
            'ref_unit_no' => 'FL-001',
        ]));

        $response->assertOk()->assertJson([
            'matched' => true,
            'unit_no' => 'FL-001',
            'supplier_id' => $this->supplier->id,
            'mhe_type_id' => $this->mheType->id,
        ]);
    }

    public function test_create_form_puts_unit_before_type_and_supplier(): void
    {
        $response = $this->actingAs($this->fastAdmin)->get(route('mhe-downtimes.create'));

        $response->assertOk();
        $html = $response->getContent();
        $unit = strpos($html, '<select name="ref_unit_no" id="ref_unit_no"');
        $type = strpos($html, 'id="mhe_type_id"');
        $supplier = strpos($html, 'id="supplier_id"');

        $this->assertNotFalse($unit);
        $this->assertNotFalse($type);
        $this->assertNotFalse($supplier);
        $this->assertLessThan($type, $unit);
        $this->assertLessThan($supplier, $type);
        $this->assertStringNotContainsString('list="unit-numbers"', $html);
        $response->assertSee('Automatically updated when implemented by supplier', false);
        $response->assertSee('form-text text-danger', false);
        $this->assertSame(2, substr_count($response->getContent(), 'Automatically updated when implemented by supplier'));
    }

    public function test_create_form_defaults_datetime_fields_to_now(): void
    {

        $now = now()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->siteUser)->get(route('mhe-downtimes.create'));

        $response->assertOk();

        $response->assertSee('value="'.$now.'"', false);

        $response->assertSee('value="Site One (SITE1)"', false);

        $response->assertSee('id="site_id" value="'.$this->site->id.'"', false);

        $fastAdminResponse = $this->actingAs($this->fastAdmin)->get(route('mhe-downtimes.create'));

        $fastAdminResponse->assertOk();

        $fastAdminResponse->assertSee('value="Site One (SITE1)"', false);

        $fastAdminResponse->assertSee('id="site_id" value="'.$this->site->id.'"', false);

    }

    public function test_create_form_leaves_site_blank_when_multiple_sites_are_assigned(): void
    {

        $this->fastAdmin->sites()->attach($this->otherSite->id);

        $response = $this->actingAs($this->fastAdmin)->get(route('mhe-downtimes.create'));

        $response->assertOk();

        $response->assertSee('id="site_id" value=""', false);

        $response->assertDontSee('value="Site One (SITE1)"', false);

        $response->assertDontSee('value="Site Two (SITE2)"', false);

    }

    public function test_user_can_post_draft_downtime(): void
    {

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->siteUser)->put(route('mhe-downtimes.update', $downtime), [

            ...$this->payload(),

            'save_as' => 'post',

        ]);

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));
        $response->assertSessionHas('success', 'MHE downtime posted successfully.');

        $downtime->refresh();

        $this->assertSame(DowntimeStatus::Posted, $downtime->status);

        $this->assertNotNull($downtime->posted_at);

    }

    public function test_user_can_post_draft_downtime_with_final_alias(): void
    {

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->siteUser)->put(route('mhe-downtimes.update', $downtime), [

            ...$this->payload(),

            'save_as' => 'final',

        ]);

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));
        $response->assertSessionHas('success', 'MHE downtime posted successfully.');

        $downtime->refresh();

        $this->assertSame(DowntimeStatus::Posted, $downtime->status);

        $this->assertNotNull($downtime->posted_at);

    }

    public function test_posted_downtime_allows_datetime_updates(): void
    {

        $downtime = $this->createPostedDowntime();

        $response = $this->actingAs($this->fastAdmin)->put(route('mhe-downtimes.update', $downtime), [

            'date_of_incident' => '2026-01-01T06:00',

            'uptime' => '2026-02-01T12:00',

        ]);

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $downtime->refresh();

        $this->assertSame('2026-01-01 06:00:00', $downtime->date_of_incident?->format('Y-m-d H:i:s'));

        $this->assertSame('2026-01-01 10:00:00', $downtime->uptime?->format('Y-m-d H:i:s'));

        $this->assertSame(4.0, (float) $downtime->hours_down);

        $this->assertSame('Broken mast', $downtime->title);

    }

    public function test_user_can_cancel_draft_downtime(): void
    {

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.cancel', $downtime));

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertSame(DowntimeStatus::Cancelled, $downtime->fresh()->status);

    }

    public function test_user_can_revert_posted_downtime_to_draft(): void
    {

        $downtime = $this->createPostedDowntime();

        $response = $this->actingAs($this->fastAdmin)->post(route('mhe-downtimes.revert-to-draft', $downtime));

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $downtime->refresh();

        $this->assertSame(DowntimeStatus::Draft, $downtime->status);

        $this->assertNull($downtime->posted_at);

    }

    public function test_user_cannot_access_other_site_downtime(): void
    {

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->otherSiteUser)->get(route('mhe-downtimes.show', $downtime));

        $response->assertForbidden();

    }

    public function test_supplier_cannot_access_other_supplier_downtime_at_same_site(): void
    {

        $otherSupplier = Supplier::query()->create([

            'supplier_code' => 'SUP2',

            'supplier_name' => 'Supplier Two',

            'status' => RecordStatus::Active,

        ]);

        $otherSupplierUser = User::factory()->supplier()->create([

            'supplier_id' => $otherSupplier->id,

        ]);

        $otherSupplierUser->sites()->attach($this->site->id);

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($otherSupplierUser)->get(route('mhe-downtimes.show', $downtime));

        $response->assertForbidden();

    }

    public function test_supplier_index_excludes_other_supplier_downtimes_at_same_site(): void
    {

        $otherSupplier = Supplier::query()->create([

            'supplier_code' => 'SUP2',

            'supplier_name' => 'Supplier Two',

            'status' => RecordStatus::Active,

        ]);

        $ownDowntime = $this->createDraftDowntime();

        $otherDowntime = $this->createDraftDowntime();

        $otherDowntime->update(['supplier_id' => $otherSupplier->id, 'title' => 'Other supplier issue']);

        $response = $this->actingAs($this->siteUser)->get(route('mhe-downtimes.index'));

        $response->assertOk();

        $response->assertSee($ownDowntime->title, false);

        $response->assertDontSee($otherDowntime->title, false);

    }

    public function test_cannot_delete_posted_downtime(): void
    {

        $downtime = $this->createPostedDowntime();

        $response = $this->actingAs($this->siteUser)->delete(route('mhe-downtimes.destroy', $downtime));

        $response->assertForbidden();

        $this->assertNotNull($downtime->fresh());

    }

    public function test_action_plan_mark_implemented_waits_for_fast_confirmation(): void
    {
        $downtime = $this->createPostedDowntime();
        $admin = User::factory()->create();
        $admin->sites()->attach($this->site->id);

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());

        $actionPlan = $downtime->actionPlans()->first();
        $this->assertSame(DowntimeActionPlanStatus::Pending, $actionPlan->status);

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]))
            ->assertRedirect(route('mhe-downtimes.show', $downtime));

        $actionPlan->refresh();
        $this->assertSame(DowntimeActionPlanStatus::WaitingForFastConfirmation, $actionPlan->status);
        $this->assertNotNull($actionPlan->date_implemented);

        $this->actingAs($admin)->post(route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan), [
            'date_implemented' => $actionPlan->date_implemented?->format('Y-m-d\TH:i'),
        ])->assertRedirect($actionPlan->parentShowUrl());

        $actionPlan->refresh();
        $this->assertSame(DowntimeActionPlanStatus::Confirmed, $actionPlan->status);
    }

    public function test_action_plan_can_be_rejected_and_edited(): void
    {
        $downtime = $this->createPostedDowntime();
        $admin = User::factory()->create();
        $admin->sites()->attach($this->site->id);

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());
        $actionPlan = $downtime->actionPlans()->first();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]));

        $this->actingAs($admin)->post(route('mhe-downtime-action-plan-confirmations.reject', $actionPlan), [
            'rejection_remarks' => 'Incomplete work',
        ])->assertRedirect($actionPlan->parentShowUrl());

        $actionPlan->refresh();
        $this->assertSame(DowntimeActionPlanStatus::Rejected, $actionPlan->status);

        $this->actingAs($this->siteUser)->put(route('mhe-downtimes.action-plans.update', [$downtime, $actionPlan]), $this->actionItemPayload([
            'title' => 'Updated hose replacement',
        ]))->assertRedirect(route('mhe-downtimes.show', $downtime));

        $actionPlan->refresh();
        $this->assertSame(DowntimeActionPlanStatus::Pending, $actionPlan->status);
        $this->assertSame('Updated hose replacement', $actionPlan->title);
    }

    public function test_when_to_tracks_the_latest_implemented_action_item(): void
    {
        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());

        $downtime->refresh();
        $this->assertNull($downtime->uptime);
        $this->assertNull($downtime->hours_down);

        $first = $downtime->actionPlans()->first();

        try {
            Carbon::setTestNow('2026-03-01 09:15:00');
            $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $first]));

            $downtime->refresh();
            $first->refresh();
            $this->assertSame('2026-03-01 09:15:00', $first->date_implemented?->format('Y-m-d H:i:s'));
            $this->assertSame('2026-03-01 09:15:00', $downtime->uptime?->format('Y-m-d H:i:s'));
            $this->assertSame(
                MheDowntimeService::computeHoursDown($downtime->date_of_incident, $downtime->uptime),
                (float) $downtime->hours_down,
            );

            Carbon::setTestNow('2026-03-02 11:00:00');
            $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload([
                'title' => 'Second repair',
            ]));

            $downtime->refresh();
            $this->assertNull($downtime->uptime);
            $this->assertNull($downtime->hours_down);

            $second = $downtime->actionPlans()->where('title', 'Second repair')->first();

            Carbon::setTestNow('2026-03-04 16:45:00');
            $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $second]));

            $downtime->refresh();
            $this->assertSame('2026-03-04 16:45:00', $downtime->uptime?->format('Y-m-d H:i:s'));
            $this->assertSame(
                MheDowntimeService::computeHoursDown($downtime->date_of_incident, $downtime->uptime),
                (float) $downtime->hours_down,
            );

            $this->actingAs($this->fastAdmin)->post(route('mhe-downtime-action-plan-confirmations.reject', $second), [
                'rejection_remarks' => 'Needs more work',
            ]);

            $downtime->refresh();
            $this->assertNull($downtime->uptime);
            $this->assertNull($downtime->hours_down);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_fast_admin_can_correct_implementation_datetime_on_confirm(): void
    {
        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());
        $actionPlan = $downtime->actionPlans()->first();

        try {
            Carbon::setTestNow('2026-03-01 09:15:00');
            $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]));
        } finally {
            Carbon::setTestNow();
        }

        $actionPlan->refresh();

        $this->actingAs($this->fastAdmin)
            ->post(route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan))
            ->assertSessionHasErrors('date_implemented');

        $this->assertSame(DowntimeActionPlanStatus::WaitingForFastConfirmation, $actionPlan->fresh()->status);

        $this->actingAs($this->fastAdmin)->post(route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan), [
            'date_implemented' => '2026-03-01T11:45',
        ])->assertRedirect($actionPlan->parentShowUrl());

        $actionPlan->refresh();
        $downtime->refresh();

        $this->assertSame(DowntimeActionPlanStatus::Confirmed, $actionPlan->status);
        $this->assertSame('2026-03-01 11:45:00', $actionPlan->date_implemented?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-01 11:45:00', $downtime->uptime?->format('Y-m-d H:i:s'));
        $this->assertSame(
            MheDowntimeService::computeHoursDown($downtime->date_of_incident, $downtime->uptime),
            (float) $downtime->hours_down,
        );
    }

    public function test_compute_hours_down_matches_service(): void
    {

        $from = Carbon::parse('2026-01-01 08:00:00');

        $to = Carbon::parse('2026-01-01 10:30:00');

        $this->assertSame(2.5, MheDowntimeService::computeHoursDown($from, $to));

    }

    public function test_create_draft_with_attachments_persists_files(): void
    {

        Storage::fake('public');

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'files' => [

                UploadedFile::fake()->image('photo1.jpg'),

                UploadedFile::fake()->image('photo2.png'),

            ],

        ]);

        $downtime = MheDowntime::query()->first();

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertSame(2, $downtime->attachments()->count());

        $downtime->attachments->each(fn ($attachment) => Storage::disk('public')->assertExists($attachment->file_path));

    }

    public function test_create_draft_rejects_non_image_attachments(): void
    {

        Storage::fake('public');

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.store'), [

            ...$this->payload(),

            'files' => [

                UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),

            ],

        ]);

        $response->assertSessionHasErrors('files.0');

        $this->assertSame(0, MheDowntime::query()->count());

    }

    public function test_update_draft_uploads_photos_via_attachment_route(): void
    {

        Storage::fake('public');

        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.attachments.store', $downtime), [

            'files' => [

                UploadedFile::fake()->image('new-photo.jpg'),

            ],

        ]);

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));

        $this->assertSame(1, $downtime->fresh()->attachments()->count());

    }

    public function test_supplier_cannot_save_posted_downtime(): void
    {
        $downtime = $this->createPostedDowntime();

        $response = $this->actingAs($this->siteUser)->put(route('mhe-downtimes.update', $downtime), [
            'date_of_incident' => '2026-02-01T08:00',
            'uptime' => '2026-02-01T12:00',
        ]);

        $response->assertForbidden();
    }

    public function test_supplier_cannot_revert_posted_to_draft(): void
    {
        $downtime = $this->createPostedDowntime();

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.revert-to-draft', $downtime));

        $response->assertForbidden();
    }

    public function test_supplier_can_still_save_draft(): void
    {
        $downtime = $this->createDraftDowntime();

        $response = $this->actingAs($this->siteUser)->put(route('mhe-downtimes.update', $downtime), [
            ...$this->payload(),
            'title' => 'Updated draft title',
            'save_as' => 'draft',
        ]);

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));
        $this->assertSame('Updated draft title', $downtime->fresh()->title);
        $this->assertSame(DowntimeStatus::Draft, $downtime->fresh()->status);
    }

    public function test_supplier_cannot_edit_draft_created_by_fast_admin(): void
    {
        $downtime = $this->createDraftDowntime([
            'created_by' => $this->fastAdmin->id,
            'updated_by' => $this->fastAdmin->id,
        ]);

        $response = $this->actingAs($this->siteUser)->put(route('mhe-downtimes.update', $downtime), [
            ...$this->payload(),
            'title' => 'Supplier edit attempt',
            'save_as' => 'draft',
        ]);

        $response->assertForbidden();
        $this->assertSame('Broken mast', $downtime->fresh()->title);
    }

    public function test_supplier_can_manage_action_items_on_fast_posted_downtime(): void
    {
        $downtime = $this->createPostedDowntime([
            'created_by' => $this->fastAdmin->id,
            'updated_by' => $this->fastAdmin->id,
            'posted_by' => $this->fastAdmin->id,
        ]);

        $response = $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());

        $response->assertRedirect(route('mhe-downtimes.show', $downtime));
        $this->assertSame(1, $downtime->fresh()->actionPlans()->count());
    }

    public function test_show_page_renders_action_items_for_supplier(): void
    {
        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());

        $response = $this->actingAs($this->siteUser)->get(route('mhe-downtimes.show', $downtime));

        $response->assertOk();
        $response->assertSee('Replace hydraulic hose');
        $response->assertSee('Action Items');
        $response->assertSee('Add Action Item');
        $response->assertDontSee('Revert to Draft');
        $response->assertDontSee('form="downtime-form"', false);
        $response->assertSee('Transaction Photos', false);
    }

    public function test_fast_admin_can_open_action_item_from_downtime(): void
    {
        $downtime = $this->createPostedDowntime();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.store', $downtime), $this->actionItemPayload());
        $actionPlan = $downtime->actionPlans()->first();

        $this->actingAs($this->siteUser)->post(route('mhe-downtimes.action-plans.mark-implemented', [$downtime, $actionPlan]));

        $response = $this->actingAs($this->fastAdmin)->get(route('mhe-downtimes.show', [
            'mhe_downtime' => $downtime,
            'action_plan' => $actionPlan->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-ap-action="detail"', false);
        $response->assertSee('id="action-plan-deeplink"', false);
        $response->assertSee('id="dt-ap-detail-'.$actionPlan->id.'"', false);
        $response->assertSee(route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan), false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {

        return [

            'title' => 'Broken mast',

            'site_id' => $this->site->id,

            'mhe_type_id' => $this->mheType->id,

            'mhe_category_id' => $this->mheCategory->id,

            'ref_unit_no' => 'FL-001',

            'date_of_incident' => '2026-01-01T08:00',

            'uptime' => '2026-01-01T10:00',

            'root_cause' => 'Hydraulic leak',

            'description' => 'Unit down during shift',

            'w_spare_unit' => false,

        ];

    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function actionItemPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Replace hydraulic hose',
            'description' => 'Replace hydraulic hose on unit',
            'responsible_person' => 'John Tech',
            'timeline_from' => '2026-01-01',
            'timeline_to' => '2026-01-07',
        ], $overrides);
    }

    protected function createDraftDowntime(array $overrides = []): MheDowntime
    {

        return MheDowntime::query()->create(array_merge([

            ...$this->payload(),

            'mhe_inventory_id' => $this->inventory->id,

            'supplier_id' => $this->supplier->id,

            'hours_down' => 2,

            'status' => DowntimeStatus::Draft,

            'created_by' => $this->siteUser->id,

            'updated_by' => $this->siteUser->id,

        ], $overrides));

    }

    protected function createPostedDowntime(array $overrides = []): MheDowntime
    {

        return MheDowntime::query()->create(array_merge([

            ...$this->payload(),

            'mhe_inventory_id' => $this->inventory->id,

            'supplier_id' => $this->supplier->id,

            'hours_down' => 2,

            'status' => DowntimeStatus::Posted,

            'posted_by' => $this->siteUser->id,

            'posted_at' => now(),

            'created_by' => $this->siteUser->id,

            'updated_by' => $this->siteUser->id,

        ], $overrides));

    }
}
