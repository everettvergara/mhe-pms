<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\MheDowntime;
use App\Models\Role;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\UserAssignmentService;
use App\Services\UserDataScopeService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected District $districtA;

    protected District $districtB;

    protected Site $siteA1;

    protected Site $siteA2;

    protected Site $siteB1;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->districtA = District::query()->create([
            'district_code' => 'D-A',
            'district_name' => 'District Alpha',
            'status' => RecordStatus::Active,
        ]);

        $this->districtB = District::query()->create([
            'district_code' => 'D-B',
            'district_name' => 'District Beta',
            'status' => RecordStatus::Active,
        ]);

        $this->siteA1 = Site::query()->create([
            'district_id' => $this->districtA->id,
            'site_code' => 'A1',
            'site_name' => 'Alpha One',
            'status' => RecordStatus::Active,
        ]);

        $this->siteA2 = Site::query()->create([
            'district_id' => $this->districtA->id,
            'site_code' => 'A2',
            'site_name' => 'Alpha Two',
            'status' => RecordStatus::Active,
        ]);

        $this->siteB1 = Site::query()->create([
            'district_id' => $this->districtB->id,
            'site_code' => 'B1',
            'site_name' => 'Beta One',
            'status' => RecordStatus::Active,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id'),
            'is_super_admin' => true,
        ]);
    }

    public function test_fast_admin_update_persists_site_assignments(): void
    {
        $fastAdminRoleId = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id');
        $user = User::factory()->create([
            'role_id' => $fastAdminRoleId,
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $fastAdminRoleId,
            'status' => UserStatus::Active->value,
            'is_super_admin' => false,
            'site_ids' => [$this->siteA1->id, $this->siteB1->id],
        ]);

        $response->assertRedirect(route('users.show', $user));
        $this->assertSame(
            [$this->siteA1->id, $this->siteB1->id],
            $user->fresh()->assignedSiteIds(),
        );
    }

    public function test_user_show_displays_grouped_districts_and_sites(): void
    {
        $fastAdminRoleId = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id');
        $user = User::factory()->create([
            'role_id' => $fastAdminRoleId,
            'is_super_admin' => false,
        ]);
        $user->sites()->sync([$this->siteA1->id, $this->siteA2->id]);

        $response = $this->actingAs($this->admin)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Assigned Districts');
        $response->assertSee('District Alpha');
        $response->assertSee('Alpha One');
        $response->assertSee('Alpha Two');
    }

    public function test_assignment_service_groups_sites_by_district(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id'),
            'is_super_admin' => false,
        ]);
        $user->sites()->sync([$this->siteA1->id, $this->siteB1->id]);

        $service = app(UserAssignmentService::class);

        $this->assertSame(['District Alpha', 'District Beta'], $service->assignedDistrictNames($user));
        $this->assertCount(2, $service->groupSitesByDistrict($user));
    }

    public function test_scoped_user_with_no_sites_cannot_access_downtimes(): void
    {
        $fastAdminRoleId = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id');
        $user = User::factory()->create([
            'role_id' => $fastAdminRoleId,
            'is_super_admin' => false,
        ]);

        $scope = app(UserDataScopeService::class);
        $query = MheDowntime::query();
        $scope->scopeMheDowntime($query, $user);

        $this->assertSame(0, $query->count());
        $this->assertFalse($scope->canAccessMheDowntime($user, $this->siteA1->id));
    }

    public function test_supplier_update_still_persists_supplier_and_site_assignments(): void
    {
        $supplierRoleId = Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->value('id');
        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUPX',
            'supplier_name' => 'Supplier X',
            'status' => RecordStatus::Active,
        ]);

        $user = User::factory()->supplier()->create([
            'role_id' => $supplierRoleId,
            'supplier_id' => $supplier->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $supplierRoleId,
            'status' => UserStatus::Active->value,
            'is_super_admin' => false,
            'supplier_ids' => [$supplier->id],
            'site_ids' => [$this->siteA2->id],
        ]);

        $response->assertRedirect(route('users.show', $user));
        $user->refresh();
        $this->assertSame([$supplier->id], $user->assignedSupplierIds());
        $this->assertSame([$this->siteA2->id], $user->assignedSiteIds());
    }
}
