<?php

namespace App\Providers;

use App\Models\ActionPlan;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\District;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\PmsHeader;
use App\Models\Region;
use App\Models\Role;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\ActionPlanPolicy;
use App\Policies\ActivityLogPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\ChecklistGroupPolicy;
use App\Policies\ChecklistItemPolicy;
use App\Policies\DistrictPolicy;
use App\Policies\MheCategoryPolicy;
use App\Policies\MheDowntimeActionPlanPolicy;
use App\Policies\MheDowntimePolicy;
use App\Policies\MheInventoryPolicy;
use App\Policies\MheTypePolicy;
use App\Policies\PmsPolicy;
use App\Policies\RegionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SitePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Supplier::class => SupplierPolicy::class,
        District::class => DistrictPolicy::class,
        Region::class => RegionPolicy::class,
        Site::class => SitePolicy::class,
        MheType::class => MheTypePolicy::class,
        MheCategory::class => MheCategoryPolicy::class,
        MheInventory::class => MheInventoryPolicy::class,
        MheDowntime::class => MheDowntimePolicy::class,
        MheDowntimeActionPlan::class => MheDowntimeActionPlanPolicy::class,
        ChecklistGroup::class => ChecklistGroupPolicy::class,
        ChecklistItem::class => ChecklistItemPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        PmsHeader::class => PmsPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        ActionPlan::class => ActionPlanPolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.bootstrap-5');

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        View::composer('partials.sidebar', function ($view): void {
            $view->with('reportTypes', config('reports.types', []));
        });
    }
}
