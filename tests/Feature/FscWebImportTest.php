<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\FscWebImportStatus;
use App\Enums\MheDowntimeImportSource;
use App\Enums\RecordStatus;
use App\Jobs\RunFscWebImportJob;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeImportBatch;
use App\Models\MheType;
use App\Models\Site;
use App\Models\User;
use App\Services\FscWebImport\FscWebImportOrchestrator;
use App\Services\FscWebImport\FscWebImportPurgeService;
use Database\Seeders\EagleEyeImportDefaultSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FscWebImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, \Database\Seeders\SupplierSeeder::class]);

        $district = District::query()->first() ?? District::query()->create([
            'district_code' => 'D-IMPORT',
            'district_name' => 'Import Test District',
            'status' => RecordStatus::Active,
        ]);

        foreach (['SDC', 'D&L Pasig', 'DMPICGY', 'Alabang', 'PepSi'] as $siteCode) {
            Site::query()->firstOrCreate(
                ['site_code' => $siteCode],
                [
                    'district_id' => $district->id,
                    'site_name' => $siteCode,
                    'status' => RecordStatus::Active,
                ],
            );
        }

        MheType::query()->create([
            'code' => 'RT',
            'description' => 'Reach Truck',
            'status' => RecordStatus::Active,
        ]);

        MheCategory::query()->create([
            'code' => 'Damage',
            'name' => 'Damage',
            'status' => RecordStatus::Active,
        ]);

        $this->seed(EagleEyeImportDefaultSeeder::class);
    }

    public function test_live_import_requires_confirmation_phrase(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::query()->where('slug', \App\Models\Role::SLUG_FAST_ADMINISTRATOR)->value('id'),
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('mhe-downtimes.import.store'), [
            'host' => '127.0.0.1',
            'database' => 'eagle_eye_test',
            'username' => 'root',
            'import_downtimes' => '1',
        ]);

        $response->assertSessionHasErrors('confirmation');
    }

    public function test_store_dispatches_background_import_job(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::query()->where('slug', \App\Models\Role::SLUG_FAST_ADMINISTRATOR)->value('id'),
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('mhe-downtimes.import.store'), [
            'host' => '127.0.0.1',
            'database' => 'eagle_eye_test',
            'username' => 'root',
            'import_users' => '1',
            'import_downtimes' => '1',
            'dry_run' => '1',
        ]);

        $batch = MheDowntimeImportBatch::query()->first();
        $this->assertNotNull($batch);
        $response->assertRedirect(route('mhe-downtimes.import.progress', $batch->batch_id));
        Queue::assertPushed(RunFscWebImportJob::class);
    }

    public function test_status_endpoint_returns_batch_progress(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::query()->where('slug', \App\Models\Role::SLUG_FAST_ADMINISTRATOR)->value('id'),
            'is_super_admin' => true,
        ]);

        $batch = MheDowntimeImportBatch::query()->create([
            'batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'source' => MheDowntimeImportSource::EagleEyeMysql,
            'source_summary' => 'test',
            'dry_run' => true,
            'status' => FscWebImportStatus::Running,
            'phase' => 'importing_downtimes',
            'progress_percent' => 42,
            'processed_count' => 10,
            'total_count' => 24,
            'status_message' => 'Importing downtimes…',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('mhe-downtimes.import.status', $batch->batch_id))
            ->assertOk()
            ->assertJsonPath('status', 'running')
            ->assertJsonPath('progress_percent', 42)
            ->assertJsonPath('processed_count', 10)
            ->assertJsonPath('total_count', 24);
    }

    public function test_purge_reseeds_default_users(): void
    {
        $extraUser = User::factory()->create(['username' => 'extra.user']);
        $usersBeforePurge = User::query()->count();

        $counts = app(FscWebImportPurgeService::class)->purgeAndReseedDefaults();

        $this->assertSame($usersBeforePurge, $counts['users_purged']);
        $this->assertFalse(User::query()->whereKey($extraUser->id)->exists());
        $this->assertTrue(User::query()->where('username', 'admin')->exists());
        $this->assertTrue(User::query()->where('username', 'toyota')->exists());
    }

    public function test_dry_run_imports_downtimes_without_persisting(): void
    {
        $seedPath = storage_path('framework/testing/fsc_import_seed');
        if (! is_dir($seedPath)) {
            mkdir($seedPath, 0755, true);
        }

        $downtimes = [[
            'legacy_id' => 200,
            'title' => 'Dry run downtime',
            'ee_site_id' => 54,
            'hours_down' => '1.00',
            'ee_mhe_category_id' => 4,
            'date_of_incident' => '2023-10-01 00:00:00',
            'description' => 'Test',
            'ee_created_by_id' => 1,
            'ee_status_id' => 1,
            'created_at' => '2023-10-23 01:02:56',
            'updated_at' => '2023-10-23 01:02:56',
            'ee_mhe_type_id' => 3,
            'uptime' => null,
            'ref_unit_no' => 'UNIT1',
            'w_spare_unit' => false,
            'root_cause' => null,
            'time_from' => null,
            'time_to' => null,
        ]];

        file_put_contents("{$seedPath}/downtimes.json", json_encode($downtimes));
        file_put_contents("{$seedPath}/action_plans.json", json_encode([]));
        file_put_contents("{$seedPath}/downtime_attachments.json", json_encode([]));
        file_put_contents("{$seedPath}/action_plan_attachments.json", json_encode([]));
        file_put_contents("{$seedPath}/ee_sites_by_id.json", json_encode(['54' => 'SDC']));
        file_put_contents("{$seedPath}/ee_mhe_types_by_id.json", json_encode(['3' => 'RT']));
        file_put_contents("{$seedPath}/ee_mhe_categories_by_id.json", json_encode(['4' => 'Damage']));

        $beforeUserCount = User::query()->count();

        $result = app(FscWebImportOrchestrator::class)->run(
            MheDowntimeImportSource::EagleEyeJsonSeed,
            ['seed_path' => $seedPath],
            [
                'dry_run' => true,
                'import_users' => false,
                'import_downtimes' => true,
                'confirmed' => false,
            ],
        );

        $this->assertSame('dry-run', $result['batch_id']);
        $this->assertSame(1, $result['downtimes']);
        $this->assertSame($beforeUserCount, User::query()->count());
        $this->assertNull(MheDowntime::query()->where('legacy_eagle_eye_id', 200)->first());
    }

    public function test_imported_user_password_hash_is_preserved(): void
    {
        $hash = password_hash('SecretPass1!', PASSWORD_BCRYPT, ['cost' => 10]);
        $roleId = \App\Models\Role::query()->where('slug', \App\Models\Role::SLUG_FAST_ADMINISTRATOR)->value('id');

        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'legacy.user',
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => $hash,
            'role_id' => $roleId,
            'status' => \App\Enums\UserStatus::Active->value,
            'is_super_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->where('username', 'legacy.user')->firstOrFail();

        $this->assertSame($hash, $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('SecretPass1!', $user->password));
    }

    public function test_imported_fast_admin_receives_site_assignments_by_site_code(): void
    {
        $district = District::query()->firstOrFail();
        $site = Site::query()->firstOrCreate(
            ['site_code' => 'SDC'],
            [
                'district_id' => $district->id,
                'site_name' => 'SDC',
                'status' => RecordStatus::Active,
            ],
        );

        $roleId = \App\Models\Role::query()->where('slug', \App\Models\Role::SLUG_FAST_ADMINISTRATOR)->value('id');
        $user = User::query()->create([
            'username' => 'mhe.user',
            'name' => 'MHE User',
            'email' => 'mhe.user@example.com',
            'password' => bcrypt('Password1!'),
            'role_id' => $roleId,
            'status' => \App\Enums\UserStatus::Active,
            'is_super_admin' => false,
        ]);

        $service = app(\App\Services\FscWebImport\FscUserImportService::class);
        $method = new \ReflectionMethod($service, 'resolveLocalSiteIds');
        $resolved = $method->invoke($service, [[
            'ee_site_id' => 54,
            'site_code' => 'SDC',
        ]]);

        $user->sites()->sync($resolved['local_site_ids']);

        $this->assertSame([$site->id], $user->fresh()->assignedSiteIds());
        $this->assertSame([], $resolved['unmapped_codes']);
    }
}
