<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(Role::class, 'role');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'roles', ['sort' => 'name', 'direction' => 'asc']);

        $query = Role::query()->withCount('permissions');
        $roles = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('roles.index', compact('roles', 'state'));
    }

    public function create(): View
    {
        return view('roles.form', [
            'role' => new Role,
            'permissions' => Permission::query()->orderBy('module')->orderBy('action')->get(),
            'assignedPermissionIds' => [],
            'isEdit' => false,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);

        $role = Role::query()->create([
            ...$data,
            'is_system' => false,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $role->permissions()->sync($permissionIds);

        $this->activityLogService->log($request->user(), 'roles', 'create', $role->id, "Created role {$role->name}.");

        return redirect()->route('roles.show', $role)->with('success', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('roles.form', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('module')->orderBy('action')->get(),
            'assignedPermissionIds' => $role->permissions->pluck('id')->all(),
            'isEdit' => true,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);

        $role->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        $role->permissions()->sync($permissionIds);

        $this->activityLogService->log($request->user(), 'roles', 'update', $role->id, "Updated role {$role->name}.");

        return redirect()->route('roles.show', $role)->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $role->update(['updated_by' => $request->user()->id]);
        $role->delete();

        $this->activityLogService->log($request->user(), 'roles', 'delete', $role->id, "Deleted role {$role->name}.");

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'name';
    }

    protected function sortableColumns(): array
    {
        return ['name', 'slug', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['is_system']) && $filters['is_system'] !== '') {
            $query->where('is_system', (bool) $filters['is_system']);
        }

        return $query;
    }
}
