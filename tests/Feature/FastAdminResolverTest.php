<?php

namespace Tests\Feature;

use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\Site;
use App\Models\User;
use App\Services\FastAdminResolver;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FastAdminResolverTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected User $fastAdmin;

    protected User $otherSiteAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $district = District::query()->first();

        $this->site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE1',
            'site_name' => 'Site One',
            'status' => RecordStatus::Active,
        ]);

        $otherSite = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'SITE2',
            'site_name' => 'Site Two',
            'status' => RecordStatus::Active,
        ]);

        $this->fastAdmin = User::factory()->create();
        $this->fastAdmin->sites()->attach($this->site->id);

        $this->otherSiteAdmin = User::factory()->create();
        $this->otherSiteAdmin->sites()->attach($otherSite->id);
    }

    public function test_resolver_returns_fast_admin_for_matching_site(): void
    {
        $resolved = app(FastAdminResolver::class)->resolveForSite($this->site->id);

        $this->assertCount(1, $resolved);
        $this->assertTrue($resolved->first()->is($this->fastAdmin));
    }

    public function test_resolver_returns_empty_for_unknown_site(): void
    {
        $resolved = app(FastAdminResolver::class)->resolveForSite(null);

        $this->assertCount(0, $resolved);
    }
}
