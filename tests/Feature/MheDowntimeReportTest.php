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
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MheDowntimeReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $district = District::query()->first();
        $site = Site::query()->create([
            'district_id' => $district->id,
            'site_code' => 'RPT1',
            'site_name' => 'Report Site',
            'status' => RecordStatus::Active,
        ]);

        $type = MheType::query()->create([
            'code' => 'FL',
            'description' => 'Forklift',
            'status' => RecordStatus::Active,
        ]);

        $category = MheCategory::query()->create([
            'code' => 'ELEC',
            'name' => 'Electrical',
            'status' => RecordStatus::Active,
        ]);

        MheDowntime::query()->create([
            'title' => 'Incident',
            'site_id' => $site->id,
            'mhe_type_id' => $type->id,
            'mhe_category_id' => $category->id,
            'ref_unit_no' => 'FL-1',
            'date_of_incident' => now(),
            'hours_down' => 2,
            'status' => DowntimeStatus::Posted,
        ]);

        $this->user = User::factory()->create();
    }

    public function test_summary_report_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.summary'))
            ->assertOk()
            ->assertSee('MHE Summary');
    }

    public function test_utilization_report_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.utilization'))
            ->assertOk()
            ->assertSee('MHE System Utilization');
    }

    public function test_summary_action_plans_partial_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('mhes.summary.action-plans', ['as_of_date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('MHE Action Plans');
    }
}
