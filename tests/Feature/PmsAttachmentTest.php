<?php

namespace Tests\Feature;

use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\Attachment;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmsAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected Site $site;

    protected MheType $mheType;

    protected User $supplierUser;

    protected User $otherSupplierUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->supplier = Supplier::query()->create([
            'supplier_code' => 'SUP1',
            'supplier_name' => 'Supplier One',
            'status' => RecordStatus::Active,
        ]);

        $otherSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP2',
            'supplier_name' => 'Supplier Two',
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

        $this->otherSupplierUser = User::factory()->supplier()->create([
            'supplier_id' => $otherSupplier->id,
        ]);
        $this->otherSupplierUser->sites()->attach($this->site->id);
    }

    public function test_supplier_can_upload_multiple_images_to_draft_pms_header(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);
        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ];

        $response = $this
            ->actingAs($this->supplierUser)
            ->post(route('pms.attachments.store', $pms), [
                'files' => $files,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(2, $pms->attachments()->count());
        $pms->attachments->each(fn (Attachment $attachment) => Storage::disk('public')->assertExists($attachment->file_path));
    }

    public function test_supplier_can_upload_multiple_images_to_checklist_detail(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);
        $detail = $pms->pmsDetails()->firstOrFail();
        $files = [
            UploadedFile::fake()->image('finding1.jpg'),
            UploadedFile::fake()->image('finding2.jpg'),
        ];

        $response = $this
            ->actingAs($this->supplierUser)
            ->post(route('pms-details.attachments.store', $detail), [
                'files' => $files,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(2, $detail->attachments()->count());
    }

    public function test_upload_rejected_when_pms_is_submitted(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);
        $pms->update(['status' => PmsStatus::NoFindings]);

        $response = $this
            ->actingAs($this->supplierUser)
            ->from(route('pms.show', $pms))
            ->post(route('pms.attachments.store', $pms), [
                'files' => [UploadedFile::fake()->image('photo.jpg')],
            ]);

        $response
            ->assertRedirect(route('pms.show', $pms))
            ->assertSessionHasErrors('files');

        $this->assertSame(0, $pms->attachments()->count());
    }

    public function test_invalid_image_type_is_rejected(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);

        $response = $this
            ->actingAs($this->supplierUser)
            ->from(route('pms.show', $pms))
            ->post(route('pms.attachments.store', $pms), [
                'files' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
            ]);

        $response
            ->assertSessionHasErrors('files.0')
            ->assertRedirect(route('pms.show', $pms));
    }

    public function test_oversized_image_is_rejected(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);

        $response = $this
            ->actingAs($this->supplierUser)
            ->from(route('pms.show', $pms))
            ->post(route('pms.attachments.store', $pms), [
                'files' => [UploadedFile::fake()->image('large.jpg')->size(26000)],
            ]);

        $response
            ->assertSessionHasErrors('files.0')
            ->assertRedirect(route('pms.show', $pms));
    }

    public function test_delete_removes_db_row_and_storage_file(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);
        $path = UploadedFile::fake()->image('photo.jpg')->store('pms-attachments/'.$pms->id, 'public');
        $attachment = $pms->attachments()->create([
            'file_path' => $path,
            'original_filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'created_by' => $this->supplierUser->id,
        ]);

        $response = $this
            ->actingAs($this->supplierUser)
            ->delete(route('attachments.destroy', $attachment));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_user_without_access_cannot_upload_to_another_suppliers_pms(): void
    {
        $pms = $this->createDraftPms($this->supplierUser);

        $response = $this
            ->actingAs($this->otherSupplierUser)
            ->post(route('pms.attachments.store', $pms), [
                'files' => [UploadedFile::fake()->image('photo.jpg')],
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $pms->attachments()->count());
    }

    public function test_fast_admin_can_view_submitted_pms_with_attachments(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $pms = $this->createDraftPms($this->supplierUser);
        $path = UploadedFile::fake()->image('photo.jpg')->store('pms-attachments/'.$pms->id, 'public');
        $pms->attachments()->create([
            'file_path' => $path,
            'original_filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'created_by' => $this->supplierUser->id,
        ]);
        $pms->update(['status' => PmsStatus::NoFindings]);

        $response = $this->actingAs($admin)->get(route('pms.show', $pms));

        $response
            ->assertOk()
            ->assertSee('Transaction Photos', false);
    }

    protected function createDraftPms(User $user): PmsHeader
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
            'supplier_id' => $user->supplier_id,
            'site_id' => $this->site->id,
            'technician_name' => 'Tech One',
            'date_from' => now(),
            'date_to' => now()->addDay(),
            'next_schedule_date' => now()->addMonth(),
            'mhe_type_id' => $this->mheType->id,
            'unit_number' => 'U-001',
            'status' => PmsStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        PmsDetail::query()->create([
            'pms_header_id' => $pms->id,
            'checklist_item_id' => $item->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return $pms->load('pmsDetails');
    }
}
