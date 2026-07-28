<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_admin_can_upload_image_when_creating_supplier(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('logo.jpg');

        $response = $this
            ->actingAs($user)
            ->post(route('suppliers.store'), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'image' => $file,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $supplier = Supplier::query()->where('supplier_code', 'TST')->first();

        $this->assertNotNull($supplier);
        $this->assertNotNull($supplier->image);
        Storage::disk('public')->assertExists($supplier->image);
    }

    public function test_admin_can_upload_image_when_updating_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'TST',
            'supplier_name' => 'Test Supplier',
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $file = UploadedFile::fake()->image('logo.jpg');

        $response = $this
            ->actingAs($user)
            ->put(route('suppliers.update', $supplier), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'image' => $file,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('suppliers.show', $supplier));

        $supplier->refresh();

        $this->assertNotNull($supplier->image);
        Storage::disk('public')->assertExists($supplier->image);
    }

    public function test_invalid_image_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'TST',
            'supplier_name' => 'Test Supplier',
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this
            ->actingAs($user)
            ->from(route('suppliers.edit', $supplier))
            ->put(route('suppliers.update', $supplier), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'image' => $file,
            ]);

        $response
            ->assertSessionHasErrors('image')
            ->assertRedirect(route('suppliers.edit', $supplier));
    }

    public function test_oversized_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'TST',
            'supplier_name' => 'Test Supplier',
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $file = UploadedFile::fake()->image('logo.jpg')->size(3000);

        $response = $this
            ->actingAs($user)
            ->from(route('suppliers.edit', $supplier))
            ->put(route('suppliers.update', $supplier), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'image' => $file,
            ]);

        $response
            ->assertSessionHasErrors('image')
            ->assertRedirect(route('suppliers.edit', $supplier));
    }

    public function test_admin_can_remove_existing_supplier_image(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'TST',
            'supplier_name' => 'Test Supplier',
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $path = UploadedFile::fake()->image('logo.jpg')->store('supplier-images/'.$supplier->id, 'public');
        $supplier->update(['image' => $path]);

        $response = $this
            ->actingAs($user)
            ->put(route('suppliers.update', $supplier), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'remove_image' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('suppliers.show', $supplier));

        $supplier->refresh();

        $this->assertNull($supplier->image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_replacing_supplier_image_deletes_old_file(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'TST',
            'supplier_name' => 'Test Supplier',
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('supplier-images/'.$supplier->id, 'public');
        $supplier->update(['image' => $oldPath]);

        $newFile = UploadedFile::fake()->image('new.jpg');

        $response = $this
            ->actingAs($user)
            ->put(route('suppliers.update', $supplier), [
                'supplier_code' => 'TST',
                'supplier_name' => 'Test Supplier',
                'status' => RecordStatus::Active->value,
                'image' => $newFile,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('suppliers.show', $supplier));

        $supplier->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($supplier->image);
        $this->assertNotSame($oldPath, $supplier->image);
    }
}
