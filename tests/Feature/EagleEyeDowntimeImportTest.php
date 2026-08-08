<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheType;
use App\Models\Site;
use App\Services\EagleEye\EagleEyeDowntimeImportService;
use Database\Seeders\EagleEyeImportDefaultSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EagleEyeDowntimeImportTest extends TestCase
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

    public function test_imports_downtime_from_payload_with_legacy_maps(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 6,
                'title' => 'MHE DOWNTIME',
                'ee_site_id' => 54,
                'hours_down' => '528.00',
                'ee_mhe_category_id' => 4,
                'date_of_incident' => '2023-10-01 00:00:00',
                'description' => 'TR5 DEFECTIVE CONTROLLER',
                'ee_created_by_id' => 384,
                'ee_status_id' => 2,
                'created_at' => '2023-10-23 01:02:56',
                'updated_at' => '2023-10-26 07:39:15',
                'ee_mhe_type_id' => 3,
                'uptime' => '2023-10-26 15:38:00',
                'ref_unit_no' => 'TR5',
                'w_spare_unit' => false,
                'root_cause' => null,
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [[
                'legacy_id' => 4,
                'mhe_id' => 6,
                'action_plan' => 'Sample Action Plan 2',
                'action_plan_date' => '2023-09-28',
                'responsible_person' => 'Safety Officer',
                'ee_action_plan_status_id' => 2,
                'date_implemented' => '2023-09-28',
                'created_at' => '2023-09-28 07:03:53',
                'updated_at' => '2023-09-28 07:04:01',
            ]],
            'downtime_attachments' => [],
            'action_plan_attachments' => [],
        ];

        $result = app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, [
            'legacy_maps' => [
                'site' => ['54' => 'SDC'],
                'mhe_type' => ['3' => 'RT'],
                'mhe_category' => ['4' => 'Damage'],
            ],
        ]);

        $this->assertSame(1, $result['downtimes']);
        $this->assertSame(1, $result['action_plans']);

        $downtime = MheDowntime::query()->where('legacy_eagle_eye_id', 6)->first();
        $this->assertNotNull($downtime);
        $this->assertSame(DowntimeStatus::Posted, $downtime->status);
        $this->assertSame('TR5', $downtime->ref_unit_no);
        $this->assertCount(1, $downtime->actionPlans);
        $this->assertSame('Safety Officer', $downtime->actionPlans->first()->responsible_person);
    }

    public function test_imports_legacy_attachment_metadata_without_file(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 6,
                'title' => 'MHE DOWNTIME',
                'ee_site_id' => 54,
                'hours_down' => '528.00',
                'ee_mhe_category_id' => 4,
                'date_of_incident' => '2023-10-01 00:00:00',
                'description' => 'Test',
                'ee_created_by_id' => 384,
                'ee_status_id' => 2,
                'created_at' => '2023-10-23 01:02:56',
                'updated_at' => '2023-10-26 07:39:15',
                'ee_mhe_type_id' => 3,
                'uptime' => '2023-10-26 15:38:00',
                'ref_unit_no' => 'TR5',
                'w_spare_unit' => false,
                'root_cause' => null,
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [],
            'downtime_attachments' => [[
                'legacy_id' => 16,
                'mhe_id' => 6,
                'attachment' => '1698734459-Service Report Monitoring.xlsx',
                'created_at' => '2023-09-18 08:13:30',
                'updated_at' => '2023-09-18 08:13:30',
            ]],
            'action_plan_attachments' => [],
        ];

        app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, [
            'legacy_maps' => [
                'site' => ['54' => 'SDC'],
                'mhe_type' => ['3' => 'RT'],
                'mhe_category' => ['4' => 'Damage'],
            ],
        ]);

        $attachment = \App\Models\Attachment::query()->first();
        $this->assertNotNull($attachment);
        $this->assertNull($attachment->mime_type);
        $this->assertNull($attachment->file_size);
        $this->assertFalse($attachment->isAvailable());
        $this->assertTrue($attachment->isLegacyPlaceholder());
        $this->assertNull($attachment->url());
    }

    public function test_dry_run_does_not_persist_records(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 99,
                'title' => 'Dry Run',
                'ee_site_id' => 999,
                'hours_down' => '1.00',
                'ee_mhe_category_id' => 999,
                'date_of_incident' => '2023-10-01 00:00:00',
                'description' => 'Test',
                'ee_created_by_id' => 1,
                'ee_status_id' => 1,
                'created_at' => '2023-10-23 01:02:56',
                'updated_at' => '2023-10-23 01:02:56',
                'ee_mhe_type_id' => 999,
                'uptime' => null,
                'ref_unit_no' => 'UNIT1',
                'w_spare_unit' => false,
                'root_cause' => null,
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [],
            'downtime_attachments' => [],
            'action_plan_attachments' => [],
        ];

        app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, ['dry_run' => true]);

        $this->assertSame(0, MheDowntime::query()->count());
    }

    public function test_zero_date_of_incident_falls_back_to_created_at(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 2263,
                'title' => 'MPC 24 DOWN SINCE AUGUST 2024',
                'ee_site_id' => 54,
                'hours_down' => '0.00',
                'ee_mhe_category_id' => 4,
                'date_of_incident' => '0000-00-00 00:00:00',
                'description' => 'CANNOT BE REPAIR',
                'ee_created_by_id' => 384,
                'ee_status_id' => 1,
                'created_at' => '2025-03-15 11:10:18',
                'updated_at' => '2025-03-15 11:10:18',
                'ee_mhe_type_id' => 3,
                'uptime' => '2025-03-14 00:00:00',
                'ref_unit_no' => 'MPC 24',
                'w_spare_unit' => true,
                'root_cause' => 'MULTIPLE DEFECT',
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [],
            'downtime_attachments' => [],
            'action_plan_attachments' => [],
        ];

        $result = app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, [
            'legacy_maps' => [
                'site' => ['54' => 'SDC'],
                'mhe_type' => ['3' => 'RT'],
                'mhe_category' => ['4' => 'Damage'],
            ],
        ]);

        $this->assertSame(1, $result['downtimes']);
        $this->assertSame(0, $result['errors']);

        $downtime = MheDowntime::query()->where('legacy_eagle_eye_id', 2263)->first();
        $this->assertNotNull($downtime);
        $this->assertSame('2025-03-15 11:10:18', $downtime->date_of_incident->format('Y-m-d H:i:s'));
        $this->assertSame('2025-03-14 00:00:00', $downtime->uptime->format('Y-m-d H:i:s'));
    }

    public function test_zero_date_of_incident_and_created_at_stores_null_incident_date(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 9999,
                'title' => 'Both zero dates',
                'ee_site_id' => 54,
                'hours_down' => '1.00',
                'ee_mhe_category_id' => 4,
                'date_of_incident' => '0000-00-00 00:00:00',
                'description' => 'Test',
                'ee_created_by_id' => 384,
                'ee_status_id' => 1,
                'created_at' => '0000-00-00 00:00:00',
                'updated_at' => '0000-00-00 00:00:00',
                'ee_mhe_type_id' => 3,
                'uptime' => '0000-00-00 00:00:00',
                'ref_unit_no' => 'UNIT-ZERO',
                'w_spare_unit' => false,
                'root_cause' => null,
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [],
            'downtime_attachments' => [],
            'action_plan_attachments' => [],
        ];

        $result = app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, [
            'legacy_maps' => [
                'site' => ['54' => 'SDC'],
                'mhe_type' => ['3' => 'RT'],
                'mhe_category' => ['4' => 'Damage'],
            ],
        ]);

        $this->assertSame(0, $result['downtimes']);
        $this->assertSame(1, $result['errors']);
        $this->assertNull(MheDowntime::query()->where('legacy_eagle_eye_id', 9999)->first());
    }

    public function test_zero_date_downtime_6612_imports_action_plan_1411(): void
    {
        $payload = [
            'downtimes' => [[
                'legacy_id' => 6612,
                'title' => 'No power on display',
                'ee_site_id' => 183,
                'hours_down' => '192.00',
                'ee_mhe_category_id' => 11,
                'date_of_incident' => '0000-00-00 00:00:00',
                'description' => 'No power on display',
                'ee_created_by_id' => 1065,
                'ee_status_id' => 1,
                'created_at' => '2026-02-19 14:16:15',
                'updated_at' => '2026-02-19 14:16:15',
                'ee_mhe_type_id' => 6,
                'uptime' => '0000-00-00 00:00:00',
                'ref_unit_no' => 'Electric Jacklift',
                'w_spare_unit' => true,
                'root_cause' => null,
                'time_from' => null,
                'time_to' => null,
            ]],
            'action_plans' => [[
                'legacy_id' => 1411,
                'mhe_id' => 6612,
                'action_plan' => 'Escalate to admin for repair of Jacklift',
                'action_plan_date' => '2026-02-12',
                'responsible_person' => 'Maria Jonalyn Alvarez',
                'ee_action_plan_status_id' => 1,
                'date_implemented' => null,
                'created_at' => '2026-02-19 14:19:40',
                'updated_at' => '2026-02-19 14:19:40',
            ]],
            'downtime_attachments' => [],
            'action_plan_attachments' => [],
        ];

        $result = app(EagleEyeDowntimeImportService::class)->importFromPayload($payload, [
            'legacy_maps' => [
                'site' => ['183' => 'SDC'],
                'mhe_type' => ['6' => 'RT'],
                'mhe_category' => ['11' => 'Damage'],
            ],
        ]);

        $this->assertSame(1, $result['downtimes']);
        $this->assertSame(1, $result['action_plans']);
        $this->assertSame(0, $result['errors']);

        $downtime = MheDowntime::query()->where('legacy_eagle_eye_id', 6612)->first();
        $this->assertNotNull($downtime);
        $this->assertSame('2026-02-19 14:16:15', $downtime->date_of_incident->format('Y-m-d H:i:s'));
        $this->assertNull($downtime->uptime);

        $actionPlan = MheDowntimeActionPlan::query()->where('legacy_eagle_eye_id', 1411)->first();
        $this->assertNotNull($actionPlan);
        $this->assertSame($downtime->id, $actionPlan->mhe_downtime_id);
        $this->assertSame('Maria Jonalyn Alvarez', $actionPlan->responsible_person);
    }

    public function test_export_command_writes_seed_files_from_sql_dump(): void
    {
        $sqlPath = dirname(base_path()).'/eagleeyefastlogi_fsc_dashboard.sql';

        if (! File::exists($sqlPath)) {
            $this->markTestSkipped('Eagle Eye SQL dump not available.');
        }

        $output = storage_path('framework/testing/eagle_eye_seed');

        $this->artisan('mhe:export-eagle-eye-seed', [
            '--sql-file' => $sqlPath,
            '--output' => $output,
        ])->assertSuccessful();

        $this->assertFileExists("{$output}/downtimes.json");
        $this->assertFileExists("{$output}/action_plans.json");
        $this->assertGreaterThan(0, count(json_decode(File::get("{$output}/downtimes.json"), true)));
    }
}
