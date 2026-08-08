<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\DowntimePostedNotification;
use App\Notifications\DowntimeSupplierPostedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DowntimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected User $fastAdmin;

    protected User $supplierIncharge;

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

        $this->supplierIncharge = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
            'email' => 'incharge@example.com',
        ]);
        $this->supplierIncharge->suppliers()->attach($this->supplier->id);
        $this->supplierIncharge->sites()->attach($this->site->id);
    }

    public function test_fast_admin_post_sends_email_to_supplier_incharge(): void
    {
        Notification::fake();

        $downtime = $this->createDraftDowntime($this->fastAdmin);

        $this->actingAs($this->fastAdmin)->put(route('mhe-downtimes.update', $downtime), [
            ...$this->payload(),
            'save_as' => 'post',
        ])->assertRedirect(route('mhe-downtimes.show', $downtime));

        Notification::assertSentTo($this->supplierIncharge, DowntimePostedNotification::class);
    }

    public function test_fast_admin_post_skips_email_when_supplier_incharge_has_no_email(): void
    {
        Notification::fake();

        $this->supplierIncharge->update(['email' => '']);

        $downtime = $this->createDraftDowntime($this->fastAdmin);

        $this->actingAs($this->fastAdmin)->put(route('mhe-downtimes.update', $downtime), [
            ...$this->payload(),
            'save_as' => 'post',
        ])->assertRedirect(route('mhe-downtimes.show', $downtime));

        Notification::assertNothingSent();
    }

    public function test_supplier_post_notifies_fast_admin(): void
    {
        Notification::fake();

        $supplierPoster = User::factory()->supplier()->create([
            'supplier_id' => $this->supplier->id,
            'email' => 'poster@example.com',
        ]);
        $supplierPoster->suppliers()->attach($this->supplier->id);
        $supplierPoster->sites()->attach($this->site->id);

        $downtime = $this->createDraftDowntime($supplierPoster);

        $this->actingAs($supplierPoster)->put(route('mhe-downtimes.update', $downtime), [
            ...$this->payload(),
            'save_as' => 'post',
        ])->assertRedirect(route('mhe-downtimes.show', $downtime));

        Notification::assertSentTo($this->fastAdmin, DowntimeSupplierPostedNotification::class);
        Notification::assertNotSentTo($this->supplierIncharge, DowntimePostedNotification::class);
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
            'supplier_id' => $this->supplier->id,
            'ref_unit_no' => 'FL-001',
            'date_of_incident' => '2026-01-01T08:00',
            'uptime' => '2026-01-01T10:00',
            'root_cause' => 'Hydraulic leak',
            'description' => 'Unit down during shift',
            'w_spare_unit' => false,
        ];
    }

    protected function createDraftDowntime(User $user): MheDowntime
    {
        return MheDowntime::query()->create([
            ...$this->payload(),
            'hours_down' => 2,
            'status' => DowntimeStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
