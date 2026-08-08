<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected UserAssignmentService $userAssignmentService,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'users', ['sort' => 'username', 'direction' => 'asc']);

        $query = User::query()->with(['role', 'suppliers']);
        $users = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('users.index', compact('users', 'state'));
    }

    public function create(): View
    {
        return view('users.form', $this->formViewData(
            new User(['status' => UserStatus::Active, 'is_super_admin' => false]),
            false,
        ));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $this->prepareUserData($request->validated());
        $supplierIds = $data['supplier_ids'] ?? [];
        $siteIds = $data['site_ids'] ?? [];
        unset($data['supplier_ids'], $data['site_ids']);

        $user = User::query()->create([
            ...$data,
            'password' => Hash::make($data['password']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->syncAssignments($user, $supplierIds, $siteIds);

        $this->activityLogService->log($request->user(), 'users', 'create', $user->id, "Created user {$user->username}.");

        return redirect()->route('users.show', $user)->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user->load(['role', 'suppliers', 'sites.district']);

        $sitesByDistrict = $this->userAssignmentService->groupSitesByDistrict($user);
        $assignedDistrictNames = $this->userAssignmentService->assignedDistrictNames($user);

        return view('users.show', compact('user', 'sitesByDistrict', 'assignedDistrictNames'));
    }

    public function edit(User $user): View
    {
        $user->load(['suppliers', 'sites']);

        return view('users.form', $this->formViewData($user, true));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $this->prepareUserData($request->validated());
        $supplierIds = $data['supplier_ids'] ?? [];
        $siteIds = $data['site_ids'] ?? [];
        unset($data['supplier_ids'], $data['site_ids']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        $this->syncAssignments($user->fresh(), $supplierIds, $siteIds);

        $this->activityLogService->log($request->user(), 'users', 'update', $user->id, "Updated user {$user->username}.");

        return redirect()->route('users.show', $user)->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $user->update(['updated_by' => $request->user()->id]);
        $user->delete();

        $this->activityLogService->log($request->user(), 'users', 'delete', $user->id, "Deleted user {$user->username}.");

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formViewData(User $user, bool $isEdit): array
    {
        $supplierRoleId = Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->value('id');

        return [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
            'districtsWithSites' => $this->userAssignmentService->districtsWithActiveSites(),
            'assignedSupplierIds' => $isEdit ? $user->assignedSupplierIds() : [],
            'assignedSiteIds' => $isEdit ? $user->assignedSiteIds() : [],
            'supplierRoleId' => $supplierRoleId,
            'isEdit' => $isEdit,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareUserData(array $data): array
    {
        unset($data['password_confirmation']);

        if (! empty($data['is_super_admin'])) {
            $data['supplier_id'] = null;
            $data['supplier_ids'] = [];
            $data['site_ids'] = [];
        }

        return $data;
    }

    /**
     * @param  array<int, int>  $supplierIds
     * @param  array<int, int>  $siteIds
     */
    protected function syncAssignments(User $user, array $supplierIds, array $siteIds): void
    {
        if ($user->isSuperAdmin()) {
            $user->update(['supplier_id' => null]);
            $user->suppliers()->sync([]);
            $user->sites()->sync([]);

            return;
        }

        if ($user->isSupplier()) {
            $user->suppliers()->sync($supplierIds);
            $user->update(['supplier_id' => $supplierIds[0] ?? null]);
            $user->sites()->sync($siteIds);

            return;
        }

        $user->update(['supplier_id' => null]);
        $user->suppliers()->sync([]);
        $user->sites()->sync($siteIds);
    }

    protected function defaultSortColumn(): string
    {
        return 'username';
    }

    protected function sortableColumns(): array
    {
        return ['username', 'name', 'email', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['username', 'name', 'email'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->whereHas('suppliers', fn (Builder $q) => $q->where('suppliers.id', $filters['supplier_id']));
        }

        return $query;
    }
}
