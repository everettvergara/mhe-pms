<?php

use App\Http\Controllers\ActionPlanConfirmationController;
use App\Http\Controllers\ActionPlanController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ChecklistGroupController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MheTypeController;
use App\Http\Controllers\PmsAttachmentController;
use App\Http\Controllers\PmsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/dashboard/pms-schedule', [DashboardController::class, 'pmsSchedule'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard.pms-schedule');

    Route::middleware('permission:pms.view')->group(function (): void {
        Route::get('pms', [PmsController::class, 'index'])->name('pms.index');
    });

    Route::middleware('permission:pms.manage')->group(function (): void {
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
        Route::get('action-plans', [ActionPlanController::class, 'index'])->name('action-plans.index');
        Route::get('action-plans/{action_plan}', [ActionPlanController::class, 'show'])->name('action-plans.show');
    });

    Route::middleware('permission:action-plans.confirm')->prefix('action-plan-confirmations')->name('action-plan-confirmations.')->group(function (): void {
        Route::get('/', [ActionPlanConfirmationController::class, 'index'])->name('index');
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

    Route::middleware('permission:profile.manage')->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/change-password', [PasswordController::class, 'edit'])->name('password.edit');
    });
});

require __DIR__.'/auth.php';
