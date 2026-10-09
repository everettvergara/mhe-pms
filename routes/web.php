<?php

use App\Http\Controllers\ActionPlanConfirmationController;
use App\Http\Controllers\ActionPlanController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ChecklistGroupController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\MheCategoryController;
use App\Http\Controllers\MheDowntimeActionPlanConfirmationController;
use App\Http\Controllers\MheDowntimeActionPlanController;
use App\Http\Controllers\MheDowntimeAttachmentController;
use App\Http\Controllers\MheDowntimeController;
use App\Http\Controllers\MheDowntimeReportController;
use App\Http\Controllers\MheInventoryController;
use App\Http\Controllers\MheTypeController;
use App\Http\Controllers\MheUptimeDashboardController;
use App\Http\Controllers\PmsAttachmentController;
use App\Http\Controllers\PmsController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserMigrationToolController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/dashboard/pms-schedule', [DashboardController::class, 'pmsSchedule'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard.pms-schedule');

    Route::get('/dashboard/units', [DashboardController::class, 'units'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard.units');

    Route::get('/dashboard/mhe-uptime', [MheUptimeDashboardController::class, 'index'])
        ->middleware('permission:mhe-downtimes.view')
        ->name('dashboard.mhe-uptime');

    Route::middleware('permission:pms.view')->group(function (): void {
        Route::get('pms', [PmsController::class, 'index'])->name('pms.index');
    });

    Route::middleware('permission:pms.manage')->group(function (): void {
        Route::get('pms/search-sites', [PmsController::class, 'searchSites'])->name('pms.search-sites');
        Route::get('pms/lookup-site', [PmsController::class, 'lookupSite'])->name('pms.lookup-site');
        Route::get('pms/search-units', [PmsController::class, 'searchUnits'])->name('pms.search-units');
        Route::get('pms/lookup-unit', [PmsController::class, 'lookupUnit'])->name('pms.lookup-unit');
        Route::get('pms/create', [PmsController::class, 'create'])->name('pms.create');
        Route::post('pms', [PmsController::class, 'store'])->name('pms.store');
        Route::get('pms/{pms}/edit', [PmsController::class, 'edit'])->name('pms.edit');
        Route::put('pms/{pms}', [PmsController::class, 'update'])->name('pms.update');
        Route::post('pms/{pms}/cancel', [PmsController::class, 'cancel'])->name('pms.cancel');
        Route::post('pms/{pms}/revert-to-draft', [PmsController::class, 'revertToDraft'])->name('pms.revert-to-draft');
        Route::post('pms/{pms}/attachments', [PmsAttachmentController::class, 'storeForPms'])->name('pms.attachments.store');
        Route::post('pms-details/{pmsDetail}/attachments', [PmsAttachmentController::class, 'storeForDetail'])->name('pms-details.attachments.store');
        Route::delete('attachments/{attachment}', [PmsAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    Route::middleware('permission:pms.view')->group(function (): void {
        Route::get('pms/{pms}', [PmsController::class, 'show'])->name('pms.show');
    });

    Route::middleware('permission:action-plans.manage')->group(function (): void {
        Route::get('action-plans/create', [ActionPlanController::class, 'create'])->name('action-plans.create');
        Route::post('action-plans', [ActionPlanController::class, 'store'])->name('action-plans.store');
        Route::get('action-plans/{action_plan}/edit', [ActionPlanController::class, 'edit'])->name('action-plans.edit');
        Route::put('action-plans/{action_plan}', [ActionPlanController::class, 'update'])->name('action-plans.update');
        Route::post('action-plans/{action_plan}/comment', [ActionPlanController::class, 'comment'])->name('action-plans.comment');
        Route::post('action-plans/{action_plan}/mark-implemented', [ActionPlanController::class, 'markImplemented'])->name('action-plans.mark-implemented');
        Route::post('action-plans/{action_plan}/cancel', [ActionPlanController::class, 'cancel'])->name('action-plans.cancel');
        Route::delete('action-plans/{action_plan}', [ActionPlanController::class, 'destroy'])->name('action-plans.destroy');
    });

    Route::middleware('permission:action-plans.view')->group(function (): void {
        Route::get('action-plans/{action_plan}', [ActionPlanController::class, 'show'])->name('action-plans.show');
    });

    Route::middleware('permission:action-plans.confirm')->prefix('mhe-downtime-action-plan-confirmations')->name('mhe-downtime-action-plan-confirmations.')->group(function (): void {
        Route::get('/{actionPlan}', [MheDowntimeActionPlanConfirmationController::class, 'show'])->name('show');
        Route::post('/{actionPlan}/confirm', [MheDowntimeActionPlanConfirmationController::class, 'confirm'])->name('confirm');
        Route::post('/{actionPlan}/reject', [MheDowntimeActionPlanConfirmationController::class, 'reject'])->name('reject');
    });

    Route::middleware('permission:action-plans.confirm')->prefix('action-plan-confirmations')->name('action-plan-confirmations.')->group(function (): void {
        Route::get('/{actionPlan}', [ActionPlanConfirmationController::class, 'show'])->name('show');
        Route::post('/{actionPlan}/confirm', [ActionPlanConfirmationController::class, 'confirm'])->name('confirm');
        Route::post('/{actionPlan}/reject', [ActionPlanConfirmationController::class, 'reject'])->name('reject');
    });

    Route::middleware('permission:suppliers.manage')->group(function (): void {
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });
    Route::middleware('permission:suppliers.view')->group(function (): void {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    });

    Route::middleware('permission:districts.manage')->group(function (): void {
        Route::get('districts/create', [DistrictController::class, 'create'])->name('districts.create');
        Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::get('districts/{district}/edit', [DistrictController::class, 'edit'])->name('districts.edit');
        Route::put('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
        Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');
    });
    Route::middleware('permission:districts.view')->group(function (): void {
        Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
        Route::get('districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
    });

    Route::middleware('permission:regions.manage')->group(function (): void {
        Route::get('regions/create', [RegionController::class, 'create'])->name('regions.create');
        Route::post('regions', [RegionController::class, 'store'])->name('regions.store');
        Route::get('regions/{region}/edit', [RegionController::class, 'edit'])->name('regions.edit');
        Route::put('regions/{region}', [RegionController::class, 'update'])->name('regions.update');
        Route::delete('regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');
    });
    Route::middleware('permission:regions.view')->group(function (): void {
        Route::get('regions', [RegionController::class, 'index'])->name('regions.index');
        Route::get('regions/{region}', [RegionController::class, 'show'])->name('regions.show');
    });

    Route::middleware('permission:sites.manage')->group(function (): void {
        Route::get('sites/create', [SiteController::class, 'create'])->name('sites.create');
        Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
        Route::get('sites/{site}/edit', [SiteController::class, 'edit'])->name('sites.edit');
        Route::put('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    });
    Route::middleware('permission:sites.view')->group(function (): void {
        Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
        Route::get('sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    });

    Route::middleware('permission:mhe-types.manage')->group(function (): void {
        Route::get('mhe-types/create', [MheTypeController::class, 'create'])->name('mhe-types.create');
        Route::post('mhe-types', [MheTypeController::class, 'store'])->name('mhe-types.store');
        Route::get('mhe-types/{mhe_type}/edit', [MheTypeController::class, 'edit'])->name('mhe-types.edit');
        Route::put('mhe-types/{mhe_type}', [MheTypeController::class, 'update'])->name('mhe-types.update');
        Route::delete('mhe-types/{mhe_type}', [MheTypeController::class, 'destroy'])->name('mhe-types.destroy');
    });
    Route::middleware('permission:mhe-types.view')->group(function (): void {
        Route::get('mhe-types', [MheTypeController::class, 'index'])->name('mhe-types.index');
        Route::get('mhe-types/{mhe_type}', [MheTypeController::class, 'show'])->name('mhe-types.show');
    });

    Route::middleware('permission:mhe-inventories.manage')->group(function (): void {
        Route::get('mhe-inventories/create', [MheInventoryController::class, 'create'])->name('mhe-inventories.create');
        Route::post('mhe-inventories', [MheInventoryController::class, 'store'])->name('mhe-inventories.store');
        Route::get('mhe-inventories/{mhe_inventory}/edit', [MheInventoryController::class, 'edit'])->name('mhe-inventories.edit');
        Route::put('mhe-inventories/{mhe_inventory}', [MheInventoryController::class, 'update'])->name('mhe-inventories.update');
        Route::delete('mhe-inventories/{mhe_inventory}', [MheInventoryController::class, 'destroy'])->name('mhe-inventories.destroy');
    });
    Route::middleware('permission:mhe-inventories.view')->group(function (): void {
        Route::get('mhe-inventories', [MheInventoryController::class, 'index'])->name('mhe-inventories.index');
        Route::get('mhe-inventories/{mhe_inventory}', [MheInventoryController::class, 'show'])->name('mhe-inventories.show');
    });

    Route::middleware('permission:mhe-categories.manage')->group(function (): void {
        Route::get('mhe-categories/create', [MheCategoryController::class, 'create'])->name('mhe-categories.create');
        Route::post('mhe-categories', [MheCategoryController::class, 'store'])->name('mhe-categories.store');
        Route::get('mhe-categories/{mhe_category}/edit', [MheCategoryController::class, 'edit'])->name('mhe-categories.edit');
        Route::put('mhe-categories/{mhe_category}', [MheCategoryController::class, 'update'])->name('mhe-categories.update');
        Route::delete('mhe-categories/{mhe_category}', [MheCategoryController::class, 'destroy'])->name('mhe-categories.destroy');
    });
    Route::middleware('permission:mhe-categories.view')->group(function (): void {
        Route::get('mhe-categories', [MheCategoryController::class, 'index'])->name('mhe-categories.index');
        Route::get('mhe-categories/{mhe_category}', [MheCategoryController::class, 'show'])->name('mhe-categories.show');
    });

    Route::middleware('permission:mhe-downtimes.view')->group(function (): void {
        Route::get('mhe-downtimes', [MheDowntimeController::class, 'index'])->name('mhe-downtimes.index');
        Route::get('mhe-downtimes/search-sites', [MheDowntimeController::class, 'searchSites'])->name('mhe-downtimes.search-sites');
        Route::get('mhe-downtimes/lookup-site', [MheDowntimeController::class, 'lookupSite'])->name('mhe-downtimes.lookup-site');
        Route::get('mhe-downtimes/search-units', [MheDowntimeController::class, 'searchUnits'])->name('mhe-downtimes.search-units');
        Route::get('mhe-downtimes/lookup-unit', [MheDowntimeController::class, 'lookupUnit'])->name('mhe-downtimes.lookup-unit');
    });

    Route::middleware('permission:mhe-downtimes.view')->prefix('mhes')->name('mhes.')->group(function (): void {
        Route::get('summary', [MheDowntimeReportController::class, 'summary'])->name('summary');
        Route::get('summary/action-plans', [MheDowntimeReportController::class, 'summaryActionPlans'])->name('summary.action-plans');
    });

    Route::middleware('permission:mhe-utilization.view')->prefix('mhes')->name('mhes.')->group(function (): void {
        Route::get('utilization', [MheDowntimeReportController::class, 'utilization'])->name('utilization');
    });

    Route::middleware('permission:mhe-downtimes.manage')->group(function (): void {
        Route::get('mhe-downtimes/create', [MheDowntimeController::class, 'create'])->name('mhe-downtimes.create');
        Route::post('mhe-downtimes', [MheDowntimeController::class, 'store'])->name('mhe-downtimes.store');
        Route::get('mhe-downtimes/{mhe_downtime}/edit', [MheDowntimeController::class, 'edit'])->name('mhe-downtimes.edit');
        Route::put('mhe-downtimes/{mhe_downtime}', [MheDowntimeController::class, 'update'])->name('mhe-downtimes.update');
        Route::delete('mhe-downtimes/{mhe_downtime}', [MheDowntimeController::class, 'destroy'])->name('mhe-downtimes.destroy');
        Route::post('mhe-downtimes/{mhe_downtime}/cancel', [MheDowntimeController::class, 'cancel'])->name('mhe-downtimes.cancel');
        Route::post('mhe-downtimes/{mhe_downtime}/revert-to-draft', [MheDowntimeController::class, 'revertToDraft'])->name('mhe-downtimes.revert-to-draft');
        Route::post('mhe-downtimes/{mhe_downtime}/attachments', [MheDowntimeAttachmentController::class, 'storeForDowntime'])->name('mhe-downtimes.attachments.store');
        Route::post('mhe-downtimes/{mhe_downtime}/action-plans', [MheDowntimeActionPlanController::class, 'store'])->name('mhe-downtimes.action-plans.store');
        Route::put('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}', [MheDowntimeActionPlanController::class, 'update'])->name('mhe-downtimes.action-plans.update');
        Route::post('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}/comment', [MheDowntimeActionPlanController::class, 'comment'])->name('mhe-downtimes.action-plans.comment');
        Route::post('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}/mark-implemented', [MheDowntimeActionPlanController::class, 'markImplemented'])->name('mhe-downtimes.action-plans.mark-implemented');
        Route::post('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}/cancel', [MheDowntimeActionPlanController::class, 'cancel'])->name('mhe-downtimes.action-plans.cancel');
        Route::delete('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}', [MheDowntimeActionPlanController::class, 'destroy'])->name('mhe-downtimes.action-plans.destroy');
        Route::post('mhe-downtimes/{mhe_downtime}/action-plans/{action_plan}/attachments', [MheDowntimeAttachmentController::class, 'storeForActionPlan'])->name('mhe-downtimes.action-plans.attachments.store');
    });

    Route::middleware('permission:mhe-downtimes.view')->group(function (): void {
        Route::get('mhe-downtimes/{mhe_downtime}', [MheDowntimeController::class, 'show'])->name('mhe-downtimes.show');
    });

    Route::middleware('permission:checklist-groups.manage')->group(function (): void {
        Route::get('checklist-groups/create', [ChecklistGroupController::class, 'create'])->name('checklist-groups.create');
        Route::post('checklist-groups', [ChecklistGroupController::class, 'store'])->name('checklist-groups.store');
        Route::get('checklist-groups/{checklist_group}/edit', [ChecklistGroupController::class, 'edit'])->name('checklist-groups.edit');
        Route::put('checklist-groups/{checklist_group}', [ChecklistGroupController::class, 'update'])->name('checklist-groups.update');
        Route::delete('checklist-groups/{checklist_group}', [ChecklistGroupController::class, 'destroy'])->name('checklist-groups.destroy');
    });
    Route::middleware('permission:checklist-groups.view')->group(function (): void {
        Route::get('checklist-groups', [ChecklistGroupController::class, 'index'])->name('checklist-groups.index');
        Route::get('checklist-groups/{checklist_group}', [ChecklistGroupController::class, 'show'])->name('checklist-groups.show');
    });

    Route::middleware('permission:checklist-items.manage')->group(function (): void {
        Route::get('checklist-items/create', [ChecklistItemController::class, 'create'])->name('checklist-items.create');
        Route::post('checklist-items', [ChecklistItemController::class, 'store'])->name('checklist-items.store');
        Route::get('checklist-items/{checklist_item}/edit', [ChecklistItemController::class, 'edit'])->name('checklist-items.edit');
        Route::put('checklist-items/{checklist_item}', [ChecklistItemController::class, 'update'])->name('checklist-items.update');
        Route::delete('checklist-items/{checklist_item}', [ChecklistItemController::class, 'destroy'])->name('checklist-items.destroy');
    });
    Route::middleware('permission:checklist-items.view')->group(function (): void {
        Route::get('checklist-items', [ChecklistItemController::class, 'index'])->name('checklist-items.index');
        Route::get('checklist-items/{checklist_item}', [ChecklistItemController::class, 'show'])->name('checklist-items.show');
    });

    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('permission:users.view')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:roles.manage')->group(function (): void {
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
    Route::middleware('permission:roles.view')->group(function (): void {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });

    Route::middleware('permission:reports.view')->prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{type}', [ReportController::class, 'show'])->name('show');
        Route::get('/{type}/export/{format}', [ReportController::class, 'export'])->name('export');
    });

    Route::middleware('permission:activity-logs.view')->prefix('activity-logs')->name('activity-logs.')->group(function (): void {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activity_log}', [ActivityLogController::class, 'show'])->name('show');
    });

    Route::middleware('fsc-import-operator')->prefix('system/user-migration-tool')->name('system.user-migration-tool.')->group(function (): void {
        Route::get('/', [UserMigrationToolController::class, 'index'])->name('index');
        Route::post('/preview', [UserMigrationToolController::class, 'preview'])->name('preview');
        Route::post('/import', [UserMigrationToolController::class, 'import'])->name('import');
    });

    Route::middleware('permission:profile.manage')->group(function (): void {
        Route::get('/change-password', [PasswordController::class, 'edit'])->name('password.edit');
    });
});

require __DIR__.'/auth.php';
