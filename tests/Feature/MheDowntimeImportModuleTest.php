<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\MheDowntimeImportSource;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeImportBatch;
use App\Models\MheType;
use App\Models\Site;
use App\Models\User;
use App\Services\MheDowntimeImport\MheDowntimeImportOrchestrator;
use Database\Seeders\EagleEyeImportDefaultSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MheDowntimeImportModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $district = District::query()->first() ?? District::query()->create([
            'district_code' => 'D-IMPORT',
            'district_name' => 'Import Test District',
            'status' => RecordStatus::Active,
        ]);

        Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SDC',
            'site_name' => 'SDC',
            'status' => RecordStatus::Active,
        ]);

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

    public function test_orchestrator_records_import_batch_for_json_seed_source(): void
    {
        $seedPath = storage_path('framework/testing/mhe_import_seed');
        if (! is_dir($seedPath)) {
            mkdir($seedPath, 0755, true);
        }

        $downtimes = [[
            'legacy_id' => 100,
            'title' => 'Batch test',
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

        $result = app(MheDowntimeImportOrchestrator::class)->run(
            MheDowntimeImportSource::EagleEyeJsonSeed,
            ['seed_path' => $seedPath],
        );

        $this->assertSame(1, $result['downtimes']);
        $this->assertDatabaseHas('mhe_downtime_import_batches', [
            'batch_id' => $result['batch_id'],
            'source' => MheDowntimeImportSource::EagleEyeJsonSeed->value,
            'downtimes' => 1,
        ]);

        $downtime = MheDowntime::query()->where('legacy_eagle_eye_id', 100)->first();
        $this->assertNotNull($downtime);
        $this->assertSame(DowntimeStatus::Draft, $downtime->status);
    }

    public function test_import_index_route_is_disabled(): void
    {
        $user = User::factory()->supplier()->create();
        $this->actingAs($user)->get('/mhe-downtimes/import')->assertNotFound();
    }

    public function test_import_create_route_is_disabled(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/mhe-downtimes/import/run')->assertNotFound();
    }
}
