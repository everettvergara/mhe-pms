<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Services\FscWebImport\FscDistrictUserImportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PDO;
use Tests\TestCase;

class UserMigrationToolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        config(['fsc_web_import.allow_source_deactivate' => true]);
    }

    public function test_non_admin_cannot_open_the_user_migration_tool(): void
    {
        $user = User::factory()->create(['username' => 'not-admin']);

        $this->actingAs($user)
            ->get(route('system.user-migration-tool.index'))
            ->assertForbidden();
    }

    public function test_import_requires_a_preview_token(): void
    {
        $admin = User::factory()->create(['username' => 'admin']);

        $this->actingAs($admin)
            ->from(route('system.user-migration-tool.index'))
            ->post(route('system.user-migration-tool.import'), [
                'confirmation' => config('fsc_web_import.confirmation_phrase'),
            ])
            ->assertRedirect(route('system.user-migration-tool.index'))
            ->assertSessionHasErrors('preview_token');
    }

    public function test_preview_excludes_existing_users_and_import_copies_only_their_district_sites(): void
    {
        $district = District::query()->firstOrFail();

        $cabuyao = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'CABUYAO',
            'site_name' => 'Cabuyao',
            'status' => RecordStatus::Active,
        ]);

        Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'CEBU',
            'site_name' => 'Cebu',
            'status' => RecordStatus::Active,
        ]);

        User::factory()->create([
            'username' => 'keepme',
            'name' => 'Already Here',
            'email' => 'keepme@example.com',
        ]);

        $pdo = $this->eagleEye();
        $service = app(FscDistrictUserImportService::class);
        $preview = $service->preview($pdo, ['District 5'], ['host' => 'sqlite', 'database' => 'memory', 'username' => 'test']);

        $byCode = collect($preview['users'])->keyBy('code');

        $this->assertSame(FscDistrictUserImportService::STATUS_ALREADY_EXISTS, $byCode['keepme']['status']);
        $this->assertSame(FscDistrictUserImportService::STATUS_WILL_IMPORT, $byCode['newone']['status']);
        $this->assertSame(['CABUYAO'], $byCode['newone']['site_codes']);
        $this->assertSame(['DAVAO'], $byCode['newone']['outside_site_codes']);
        $this->assertArrayNotHasKey('safetyonly', $byCode);

        $result = $service->import($pdo, $preview['preview_token'], true, User::query()->where('username', 'keepme')->value('id'));

        $this->assertSame(1, $result['users_created']);
        $this->assertSame(1, $result['deactivated']);

        $imported = User::query()->where('username', 'newone')->firstOrFail();
        $this->assertSame('New One', $imported->name);
        $this->assertSame(UserStatus::Active, $imported->status);
        $this->assertFalse($imported->is_super_admin);
        $this->assertSame(Role::SLUG_FAST_ADMINISTRATOR, $imported->role->slug);
        $this->assertTrue(Hash::check('newone', $imported->password));
        $this->assertEquals([$cabuyao->id], $imported->sites()->pluck('sites.id')->all());

        $this->assertSame('Already Here', User::query()->where('username', 'keepme')->value('name'));
        $this->assertSame(1, (int) $pdo->query("SELECT is_active FROM tb_sys_mf_user WHERE code = 'keepme'")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT is_active FROM tb_sys_mf_user WHERE code = 'newone'")->fetchColumn());
    }

    protected function eagleEye(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<'SQL'
            CREATE TABLE tb_fin_mf_district (id INTEGER PRIMARY KEY, code TEXT, name TEXT, is_active INTEGER);
            CREATE TABLE tb_fin_mf_site (id INTEGER PRIMARY KEY, code TEXT, name TEXT, district_id INTEGER);
            CREATE TABLE tb_sys_mf_user (id INTEGER PRIMARY KEY, code TEXT, name TEXT, is_active INTEGER);
            CREATE TABLE tb_sys_mf_user_site (id INTEGER PRIMARY KEY, user_id INTEGER, site_id INTEGER);
            CREATE TABLE tb_sys_mf_access_type (id INTEGER PRIMARY KEY, code TEXT);
            CREATE TABLE tb_sys_mf_user_access_type (id INTEGER PRIMARY KEY, user_id INTEGER, access_type_id INTEGER);
        SQL);

        $pdo->exec("INSERT INTO tb_fin_mf_district (id, code, name, is_active) VALUES (5, 'District 5', 'District 5', 1), (1, 'District 1', 'District 1', 1)");
        $pdo->exec("INSERT INTO tb_fin_mf_site (id, code, name, district_id) VALUES (10, 'CABUYAO', 'Cabuyao', 5), (11, 'DAVAO', 'Davao', 1)");
        $pdo->exec("INSERT INTO tb_sys_mf_access_type (id, code) VALUES (10, 'MHE Transaction'), (3, 'Safety Transaction')");
        $pdo->exec("INSERT INTO tb_sys_mf_user (id, code, name, is_active) VALUES (1, 'newone', 'New One', 1), (2, 'keepme', 'Keep Me', 1), (3, 'safetyonly', 'Safety Only', 1)");
        $pdo->exec('INSERT INTO tb_sys_mf_user_access_type (id, user_id, access_type_id) VALUES (1, 1, 10), (2, 2, 10), (3, 3, 3)');
        $pdo->exec('INSERT INTO tb_sys_mf_user_site (id, user_id, site_id) VALUES (1, 1, 10), (2, 1, 11), (3, 2, 10), (4, 3, 10)');

        return $pdo;
    }
}
