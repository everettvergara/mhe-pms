<?php

namespace Tests\Feature;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\EagleEyeImportMap;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheDowntimeImportBatch;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FscWebImport\FscDowntimeMasterMap;
use App\Services\FscWebImport\FscDowntimeMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

class DowntimeMigrationToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_open_the_downtime_migration_tool(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('system.downtime-migration-tool.index'))->assertForbidden();
        $this->actingAs($user)->post(route('system.downtime-migration-tool.test-connection'))->assertForbidden();
        $this->actingAs($user)->get(route('system.downtime-migration-tool.sites', ['district' => 'District 1']))->assertForbidden();
        $this->actingAs($user)->post(route('system.downtime-migration-tool.count'), ['district' => 'District 1'])->assertForbidden();
        $this->actingAs($user)->post(route('system.downtime-migration-tool.preview'), ['district' => 'District 1'])->assertForbidden();
        $this->actingAs($user)->post(route('system.downtime-migration-tool.migrate'), [])->assertForbidden();
    }

    public function test_super_admin_page_shows_the_tool_and_a_connection_error(): void
    {
        config([
            'fsc_web_import.mysql.host' => '127.0.0.1',
            'fsc_web_import.mysql.port' => 1,
            'fsc_web_import.mysql.database' => 'missing_fsc',
            'fsc_web_import.mysql.username' => 'fsc_user',
            'fsc_web_import.mysql.password' => 'x',
        ]);

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('system.downtime-migration-tool.index'))
            ->assertOk()
            ->assertSee('Migrate MHE Downtime Data')
            ->assertSee('missing_fsc')
            ->assertSee('Could not load districts from fsc_web');
    }

    public function test_migrate_requires_a_preview_token_and_the_confirmation_phrase(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->from(route('system.downtime-migration-tool.index'))
            ->post(route('system.downtime-migration-tool.migrate'), [
                'confirmation' => config('fsc_web_import.confirmation_phrase'),
                'migrate_all' => 1,
            ])
            ->assertRedirect(route('system.downtime-migration-tool.index'))
            ->assertSessionHasErrors('preview_token');
    }

    public function test_preview_classifies_rows_and_migrate_is_idempotent(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $district = District::query()->where('district_code', 'District 1')->firstOrFail();
        $otherDistrict = District::query()->where('district_code', 'District 2A')->firstOrFail();
        $type = MheType::query()->create(['code' => 'RT', 'description' => 'Reach Truck', 'status' => RecordStatus::Active]);
        $category = MheCategory::query()->create(['code' => 'Flat Tire', 'name' => 'Flat Tire', 'status' => RecordStatus::Active]);
        $supplier = Supplier::query()->create(['supplier_code' => 'OWNED', 'supplier_name' => 'Owned', 'status' => RecordStatus::Active]);

        $cabuyao = $this->site($district, 'CABUYAO', 'Cabuyao');
        $plain = $this->site($district, 'CDI La Union', 'CDI La Union');
        $collision = $this->site($district, 'CDI La Union-ee149', 'CDI La Union ee149');
        $jtiPlain = $this->site($district, 'JTI WH1', 'JTI WH1');
        $jtiCollision = $this->site($district, 'JTI WH1-ee233', 'JTI WH1 ee233');
        $trimmed = $this->site($district, 'KC Cebu ', 'KC Cebu');
        $this->site($otherDistrict, 'WRONG-DIST', 'Wrong district');
        $gone = $this->site($district, 'GONE', 'Gone');
        $gone->delete();

        $tech = User::factory()->create(['username' => 'tech1', 'name' => 'Tech One']);
        $mapped = User::factory()->create(['username' => 'mapped-name', 'name' => 'Mapped Name']);
        $fallback = User::factory()->create([
            'username' => 'importer',
            'email' => config('eagle_eye.defaults.import_user_email'),
        ]);

        EagleEyeImportMap::query()->create(['entity_type' => 'user', 'legacy_id' => 9, 'local_id' => $mapped->id, 'legacy_code' => 'oldcode']);
        EagleEyeImportMap::query()->create(['entity_type' => 'user', 'legacy_id' => 8, 'local_id' => $fallback->id, 'legacy_code' => 'not-a-user']);

        MheInventory::query()->create([
            'site_id' => $cabuyao->id,
            'supplier_id' => $supplier->id,
            'mhe_type_id' => $type->id,
            'unit_no' => 'RM0014',
            'equipment_status' => 'Active',
        ]);

        $already = MheDowntime::query()->create([
            'legacy_eagle_eye_id' => 50,
            'title' => 'Already here',
            'site_id' => $cabuyao->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'RM0014',
            'date_of_incident' => '2024-01-01 00:00:00',
            'status' => DowntimeStatus::Draft,
            'created_by' => $tech->id,
        ]);
        $already->delete();

        $pdo = $this->eagleEye($district->district_code, $otherDistrict->district_code);
        $service = app(FscDowntimeMigrationService::class);

        $sites = $service->fetchSites($pdo, $district->district_code);
        $cabuyaoSource = collect($sites)->firstWhere('code', 'CABUYAO');
        $this->assertTrue($cabuyaoSource['mapped']);
        $this->assertSame(10, $cabuyaoSource['downtime_count']);
        $this->assertFalse(collect($sites)->firstWhere('code', 'MISSING')['mapped']);

        $filter = ['district' => $district->district_code];
        $ready = $service->preview($pdo, $filter);
        $blocked = $service->preview($pdo, $filter, FscDowntimeMigrationService::STATUS_BLOCKED);
        $alreadyPreview = $service->preview($pdo, $filter, FscDowntimeMigrationService::STATUS_ALREADY);
        $byId = collect([...$ready['rows'], ...$blocked['rows'], ...$alreadyPreview['rows']])->keyBy('legacy_id');

        $this->assertSame(FscDowntimeMigrationService::STATUS_READY, $byId[1]['status']);
        $this->assertSame($cabuyao->id, $byId[1]['local_site_id']);
        $this->assertTrue($byId[1]['inventory_matched']);
        $this->assertSame(DowntimeStatus::Posted->value, $byId[1]['new_status']);
        $this->assertSame($collision->id, $byId[2]['local_site_id']);
        $this->assertSame($plain->id, $byId[3]['local_site_id']);
        $this->assertSame($jtiCollision->id, $byId[4]['local_site_id']);
        $this->assertSame($jtiPlain->id, $byId[5]['local_site_id']);
        $this->assertSame($trimmed->id, $byId[6]['local_site_id']);
        $this->assertSame(FscDowntimeMasterMap::REASON_UNMAPPED_SITE, $byId[7]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_DISTRICT_MISMATCH, $byId[8]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_UNMAPPED_TYPE, $byId[9]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_UNMAPPED_CATEGORY, $byId[10]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_CREATOR, $byId[11]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_CREATOR, $byId[12]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_INVALID_DATE, $byId[13]['block_reason']);
        $this->assertSame(FscDowntimeMasterMap::REASON_DELETED_MASTER, $byId[14]['block_reason']);
        $this->assertSame(FscDowntimeMigrationService::STATUS_ALREADY, $byId[50]['status']);
        $this->assertSame($already->id, $byId[50]['local_downtime_id']);
        $this->assertSame($mapped->id, $byId[15]['creator_id']);
        $this->assertSame('UNKNOWN', $byId[16]['ref_unit_no']);
        $this->assertContains('ghost', $ready['summary']['unmapped_creators']);
        $this->assertContains('not-a-user', $ready['summary']['unmapped_creators']);

        $readyIds = collect($ready['rows'])->pluck('legacy_id')->sort()->values()->all();
        $this->assertSame([1, 2, 3, 4, 5, 6, 15, 16, 17], $readyIds);

        $january = $service->preview($pdo, [
            'district' => $district->district_code,
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ]);
        $this->assertSame(8, $january['summary']['ready']);
        $this->assertNotContains(15, collect($january['rows'])->pluck('legacy_id')->all());

        $siteFiltered = $service->preview($pdo, [
            'district' => $district->district_code,
            'sites' => [1],
        ]);
        $this->assertEqualsCanonicalizing([1, 15, 16, 17], collect($siteFiltered['rows'])->pluck('legacy_id')->all());

        $pdo->exec("INSERT INTO tb_sf_tr_mhe (id, title, site_id, hours_down, mhe_category_id, date_of_incident, created_by_id, status_id, created_at, updated_at, mhe_type_id, ref_unit_no) VALUES (99, 'Extra', 1, '1', 2, '2024-03-01 00:00:00', 1, 2, '2024-03-01 00:00:00', '2024-03-01 00:00:00', 3, 'RM9')");

        try {
            $service->migrate($pdo, $ready['preview_token'], null, $admin->id);
            $this->fail('A stale preview was accepted.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('changed since preview', $exception->getMessage());
        }

        $this->assertSame(0, MheDowntime::withTrashed()->where('legacy_eagle_eye_id', 1)->count());
        $pdo->exec('DELETE FROM tb_sf_tr_mhe WHERE id = 99');

        $fresh = $service->preview($pdo, ['district' => $district->district_code]);
        $result = $service->migrate($pdo, $fresh['preview_token'], null, $admin->id);

        $this->assertSame(9, $result['downtimes_created']);
        $this->assertSame(4, $result['action_plans_created']);
        $this->assertSame(2, $result['downtime_attachments_created']);
        $this->assertSame(1, $result['action_plan_attachments_created']);
        $this->assertSame(0, $result['skipped']);

        $imported = MheDowntime::query()->where('legacy_eagle_eye_id', 1)->firstOrFail();
        $this->assertSame(DowntimeStatus::Posted, $imported->status);
        $this->assertSame($tech->id, $imported->posted_by);
        $this->assertSame($tech->id, $imported->created_by);
        $this->assertSame('Approver note', $imported->approver_remarks);
        $this->assertSame('9.00', $imported->hours_down);
        $this->assertSame('2024-01-10 12:00:00', $imported->uptime?->format('Y-m-d H:i:s'));
        $this->assertSame('2023-12-01 00:00:00', $imported->created_at?->format('Y-m-d H:i:s'));
        $this->assertTrue($imported->w_spare_unit);
        $this->assertSame($supplier->id, $imported->supplier_id);
        $this->assertSame('08:30:00', $imported->time_from);
        $this->assertCount(2, $imported->attachments);
        $this->assertTrue($imported->attachments->every(fn ($attachment) => $attachment->isLegacyPlaceholder()));

        $plans = MheDowntimeActionPlan::query()->where('mhe_downtime_id', $imported->id)->orderBy('legacy_eagle_eye_id')->get();
        $this->assertSame('2024-01-10 11:00:00', $plans[0]->date_implemented?->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-10 12:00:00', $plans[1]->date_implemented?->format('Y-m-d H:i:s'));
        $this->assertTrue($plans->every(fn (MheDowntimeActionPlan $plan) => $plan->status === DowntimeActionPlanStatus::Confirmed));
        $this->assertSame($admin->id, $plans[0]->confirmed_by);
        $this->assertFalse($plans[0]->unit_safe_guaranteed);
        $this->assertSame('EE-DT-AP-101', $plans[0]->action_plan_no);
        $this->assertCount(1, $plans[0]->attachments);

        $fallbackDowntime = MheDowntime::query()->where('legacy_eagle_eye_id', 15)->firstOrFail();
        $fallbackPlans = $fallbackDowntime->actionPlans()->orderBy('legacy_eagle_eye_id')->get();
        $this->assertSame('2024-02-02 00:00:00', $fallbackPlans[0]->date_implemented?->format('Y-m-d H:i:s'));
        $this->assertSame('2024-02-01 09:00:00', $fallbackPlans[1]->date_implemented?->format('Y-m-d H:i:s'));
        $this->assertSame($mapped->id, $fallbackDowntime->created_by);

        $cancelled = MheDowntime::query()->where('legacy_eagle_eye_id', 17)->firstOrFail();
        $this->assertSame(DowntimeStatus::Cancelled, $cancelled->status);
        $this->assertSame($tech->id, $cancelled->cancelled_by);

        $this->assertNotNull(MheDowntimeImportBatch::query()->where('batch_id', $result['batch_id'])->first());
        $this->assertTrue(EagleEyeImportMap::query()->where('entity_type', 'downtime')->where('legacy_id', 1)->exists());

        $again = $service->preview($pdo, ['district' => $district->district_code]);
        $this->assertSame(0, $again['summary']['ready']);
        $this->assertSame(10, $again['summary']['already_migrated']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No downtimes to migrate.');
        $service->migrate($pdo, $again['preview_token'], null, $admin->id);
    }

    public function test_selection_can_migrate_one_row_and_over_limit_selections_are_refused(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $district = District::query()->where('district_code', 'District 1')->firstOrFail();
        $type = MheType::query()->create(['code' => 'RT', 'description' => 'Reach Truck', 'status' => RecordStatus::Active]);
        $category = MheCategory::query()->create(['code' => 'Flat Tire', 'name' => 'Flat Tire', 'status' => RecordStatus::Active]);
        $this->site($district, 'CABUYAO', 'Cabuyao');
        User::factory()->create(['username' => 'tech1']);

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSourceTables($pdo);
        $pdo->exec("INSERT INTO tb_fin_mf_district (id, code, name, is_active) VALUES (1, 'District 1', 'District 1', 1)");
        $pdo->exec("INSERT INTO tb_fin_mf_site (id, code, name, district_id) VALUES (1, 'CABUYAO', 'Cabuyao', 1)");
        $pdo->exec("INSERT INTO tb_sf_mf_mhe_type (id, code, name) VALUES (3, 'RT', 'Reach Truck')");
        $pdo->exec("INSERT INTO tb_sf_mf_mhe_category (id, code, name) VALUES (2, 'Flat Tire', 'Flat Tire')");
        $pdo->exec("INSERT INTO tb_sys_mf_user (id, code, name) VALUES (1, 'tech1', 'Tech One')");
        $pdo->exec("INSERT INTO tb_sys_mf_status (id, code, name) VALUES (2, 'posted', 'Posted')");
        $pdo->exec("INSERT INTO tb_sf_tr_mhe (id, title, site_id, hours_down, mhe_category_id, date_of_incident, created_by_id, status_id, created_at, updated_at, mhe_type_id, ref_unit_no) VALUES
            (1, 'One', 1, '1', 2, '2024-01-10 08:00:00', 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, 'U1'),
            (2, 'Two', 1, '1', 2, '2024-01-11 08:00:00', 1, 2, '2024-01-11 08:00:00', '2024-01-11 08:00:00', 3, 'U2')");

        $service = app(FscDowntimeMigrationService::class);
        $preview = $service->preview($pdo, ['district' => 'District 1']);

        config(['fsc_web_import.downtime_max_rows_per_run' => 1]);

        try {
            $service->migrate($pdo, $preview['preview_token'], null, $admin->id);
            $this->fail('The row limit was ignored.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('limit per run is 1', $exception->getMessage());
        }

        $this->assertSame(0, MheDowntime::query()->count());

        config(['fsc_web_import.downtime_max_rows_per_run' => 2000]);
        $result = $service->migrate($pdo, $preview['preview_token'], [2], $admin->id);

        $this->assertSame(1, $result['downtimes_created']);
        $this->assertNull(MheDowntime::query()->where('legacy_eagle_eye_id', 1)->first());
        $this->assertNotNull(MheDowntime::query()->where('legacy_eagle_eye_id', 2)->first());
        $this->assertSame($type->id, MheDowntime::query()->where('legacy_eagle_eye_id', 2)->value('mhe_type_id'));
        $this->assertSame($category->id, MheDowntime::query()->where('legacy_eagle_eye_id', 2)->value('mhe_category_id'));
    }

    protected function site(District $district, string $code, string $name): Site
    {
        return Site::query()->create([
            'district_id' => $district->id,
            'site_code' => $code,
            'site_name' => $name,
            'status' => RecordStatus::Active,
        ]);
    }

    protected function eagleEye(string $districtCode, string $otherDistrictCode): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSourceTables($pdo);

        $pdo->exec("INSERT INTO tb_fin_mf_district (id, code, name, is_active) VALUES (1, '{$districtCode}', 'One', 1), (2, '{$otherDistrictCode}', 'Two', 1)");
        $pdo->exec("INSERT INTO tb_fin_mf_site (id, code, name, district_id) VALUES
            (1, 'CABUYAO', 'Cabuyao', 1),
            (22, 'CDI La Union', 'CDI La Union', 1),
            (149, 'CDI La Union', 'CDI La Union duplicate', 1),
            (232, 'JTI WH1', 'JTI WH1', 1),
            (233, 'JTI WH1', 'JTI WH1 duplicate', 1),
            (46, 'KC Cebu', 'KC Cebu', 1),
            (70, 'MISSING', 'Missing', 1),
            (71, 'WRONG-DIST', 'Wrong', 1),
            (72, 'GONE', 'Gone', 1)");
        $pdo->exec("INSERT INTO tb_sf_mf_mhe_type (id, code, name) VALUES (3, 'RT', 'Reach Truck'), (9, 'ZZ', 'Unknown type')");
        $pdo->exec("INSERT INTO tb_sf_mf_mhe_category (id, code, name) VALUES (2, 'Flat Tire', 'Flat Tire'), (9, 'Nope', 'Nope')");
        $pdo->exec("INSERT INTO tb_sys_mf_user (id, code, name) VALUES (1, 'tech1', 'Tech One'), (8, 'not-a-user', 'Fallback'), (9, 'oldcode', 'Old Code'), (7, 'ghost', 'Ghost')");
        $pdo->exec("INSERT INTO tb_sys_mf_status (id, code, name) VALUES (1, 'posting', 'New'), (2, 'posted', 'Posted'), (3, 'cancel', 'Cancelled'), (4, 'for approval', 'For Approval')");

        $rows = [
            [1, 'Flat tire', 1, '9.00', 2, '2024-01-10 08:00:00', 'notes.pdf', 1, 4, '2023-12-01 00:00:00', '2024-01-10 12:30:00', 3, 'Approver note', '2024-01-10 12:00:00', 'RM0014', 1, 'worn', '2024-01-11 08:30:00', '2024-01-10 12:00:00'],
            [2, 'Collision new', 149, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U2', 0, null, null, null],
            [3, 'Collision old', 22, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U3', 0, null, null, null],
            [4, 'JTI new', 233, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U4', 0, null, null, null],
            [5, 'JTI old', 232, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U5', 0, null, null, null],
            [6, 'Trimmed site', 46, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U6', 0, null, null, null],
            [7, 'Missing site', 70, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U7', 0, null, null, null],
            [8, 'Wrong district', 71, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U8', 0, null, null, null],
            [9, 'Bad type', 1, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 9, null, null, 'U9', 0, null, null, null],
            [10, 'Bad category', 1, '1', 9, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U10', 0, null, null, null],
            [11, 'Ghost user', 1, '1', 2, '2024-01-10 08:00:00', null, 7, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U11', 0, null, null, null],
            [12, 'Fallback user', 1, '1', 2, '2024-01-10 08:00:00', null, 8, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U12', 0, null, null, null],
            [13, 'Bad date', 1, '1', 2, '0000-00-00 00:00:00', null, 1, 2, null, null, 3, null, null, 'U13', 0, null, null, null],
            [14, 'Deleted site', 72, '1', 2, '2024-01-10 08:00:00', null, 1, 2, '2024-01-10 08:00:00', '2024-01-10 08:00:00', 3, null, null, 'U14', 0, null, null, null],
            [15, 'Mapped user', 1, '1', 2, '2024-02-01 09:00:00', null, 9, 2, '2024-02-01 09:00:00', '2024-02-01 09:00:00', 3, null, null, 'U15', 0, null, null, null],
            [16, 'Blank unit', 1, '1', 2, '2024-01-12 08:00:00', null, 1, null, '2024-01-12 08:00:00', '2024-01-12 08:00:00', 3, null, null, null, null, null, null, null],
            [17, 'Cancelled', 1, '1', 2, '2024-01-13 08:00:00', null, 1, 3, '2024-01-13 08:00:00', '2024-01-13 10:00:00', 3, null, null, 'U17', 0, null, null, null],
            [50, 'Already', 1, '1', 2, '2024-01-01 00:00:00', null, 1, 1, '2024-01-01 00:00:00', '2024-01-01 00:00:00', 3, null, null, 'RM0014', 0, null, null, null],
        ];

        $insert = $pdo->prepare('INSERT INTO tb_sf_tr_mhe (id, title, site_id, hours_down, mhe_category_id, date_of_incident, attachment, created_by_id, status_id, created_at, updated_at, mhe_type_id, approver_remarks, uptime, ref_unit_no, w_spare_unit, root_cause, time_from, time_to) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

        foreach ($rows as $row) {
            $insert->execute($row);
        }

        $pdo->exec("INSERT INTO tb_sf_tr_mhe_action_plan (id, mhe_id, action_plan, action_plan_date, responsible, action_plan_id, date_implemented, created_at, updated_at) VALUES
            (101, 1, 'Replace the tire', '2024-01-10', 'Juan', 2, '2024-01-10 11:00:00', '2024-01-10 09:00:00', '2024-01-10 11:00:00'),
            (102, 1, 'Torque the nuts', NULL, NULL, 1, NULL, '2024-01-10 09:30:00', '2024-01-10 09:30:00'),
            (103, 15, 'Order the part', '2024-02-02', 'Ana', 1, NULL, '2024-02-01 09:00:00', '2024-02-01 09:00:00'),
            (104, 15, 'Fit the part', NULL, 'Ana', 1, NULL, '2024-02-01 09:10:00', '2024-02-01 09:10:00')");
        $pdo->exec("INSERT INTO tb_sf_tr_mhe_attachment (id, mhe_id, attachment, created_at, updated_at) VALUES (201, 1, '100-photo.jpg', '2024-01-10 08:05:00', '2024-01-10 08:05:00')");
        $pdo->exec("INSERT INTO tb_sf_tr_mhe_action_plan_attachment (id, mhe_action_plan_id, attachment, created_at, updated_at) VALUES (301, 101, '201-fix.pdf', '2024-01-10 11:05:00', '2024-01-10 11:05:00')");

        return $pdo;
    }

    protected function createSourceTables(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE tb_fin_mf_district (id INTEGER PRIMARY KEY, code TEXT, name TEXT, is_active INTEGER);
            CREATE TABLE tb_fin_mf_site (id INTEGER PRIMARY KEY, code TEXT, name TEXT, district_id INTEGER);
            CREATE TABLE tb_sf_mf_mhe_type (id INTEGER PRIMARY KEY, code TEXT, name TEXT);
            CREATE TABLE tb_sf_mf_mhe_category (id INTEGER PRIMARY KEY, code TEXT, name TEXT);
            CREATE TABLE tb_sys_mf_user (id INTEGER PRIMARY KEY, code TEXT, name TEXT);
            CREATE TABLE tb_sys_mf_status (id INTEGER PRIMARY KEY, code TEXT, name TEXT);
            CREATE TABLE tb_sf_tr_mhe (
                id INTEGER PRIMARY KEY,
                title TEXT,
                site_id INTEGER,
                hours_down TEXT,
                mhe_category_id INTEGER,
                date_of_incident TEXT,
                description TEXT,
                attachment TEXT,
                created_by_id INTEGER,
                status_id INTEGER,
                created_at TEXT,
                updated_at TEXT,
                mhe_type_id INTEGER,
                approver_remarks TEXT,
                uptime TEXT,
                ref_unit_no TEXT,
                w_spare_unit INTEGER,
                root_cause TEXT,
                time_from TEXT,
                time_to TEXT
            );
            CREATE TABLE tb_sf_tr_mhe_action_plan (
                id INTEGER PRIMARY KEY,
                mhe_id INTEGER,
                action_plan TEXT,
                action_plan_date TEXT,
                responsible TEXT,
                action_plan_id INTEGER,
                date_implemented TEXT,
                created_at TEXT,
                updated_at TEXT
            );
            CREATE TABLE tb_sf_tr_mhe_attachment (
                id INTEGER PRIMARY KEY,
                mhe_id INTEGER,
                attachment TEXT,
                created_at TEXT,
                updated_at TEXT
            );
            CREATE TABLE tb_sf_tr_mhe_action_plan_attachment (
                id INTEGER PRIMARY KEY,
                mhe_action_plan_id INTEGER,
                attachment TEXT,
                created_at TEXT,
                updated_at TEXT
            );
        SQL);
    }
}
