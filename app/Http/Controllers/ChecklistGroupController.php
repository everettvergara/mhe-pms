<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreChecklistGroupRequest;
use App\Http\Requests\UpdateChecklistGroupRequest;
use App\Models\ChecklistGroup;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChecklistGroupController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(ChecklistGroup::class, 'checklist_group');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'checklist-groups', ['sort' => 'sequence', 'direction' => 'asc']);
        $checklistGroups = $this->paginateList($this->applyListQuery(ChecklistGroup::query(), $state), $state);

        return view('checklist-groups.index', compact('checklistGroups', 'state'));
    }

    public function create(): View
    {
        return view('checklist-groups.form', [
            'checklistGroup' => new ChecklistGroup(['status' => RecordStatus::Active, 'sequence' => 1]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreChecklistGroupRequest $request): RedirectResponse
    {
        $checklistGroup = ChecklistGroup::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'checklist_groups', 'create', $checklistGroup->id, "Created checklist group {$checklistGroup->group_name}.");

        return redirect()->route('checklist-groups.show', $checklistGroup)->with('success', 'Checklist Group created successfully.');
    }

    public function show(ChecklistGroup $checklistGroup): View
    {
        $checklistGroup->load('checklistItems');

        return view('checklist-groups.show', compact('checklistGroup'));
    }

    public function edit(ChecklistGroup $checklistGroup): View
    {
        return view('checklist-groups.form', ['checklistGroup' => $checklistGroup, 'isEdit' => true]);
    }

    public function update(UpdateChecklistGroupRequest $request, ChecklistGroup $checklistGroup): RedirectResponse
    {
        $checklistGroup->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'checklist_groups', 'update', $checklistGroup->id, "Updated checklist group {$checklistGroup->group_name}.");

        return redirect()->route('checklist-groups.show', $checklistGroup)->with('success', 'Checklist Group updated successfully.');
    }

    public function destroy(Request $request, ChecklistGroup $checklistGroup): RedirectResponse
    {
        $checklistGroup->update(['updated_by' => $request->user()->id]);
        $checklistGroup->delete();
        $this->activityLogService->log($request->user(), 'checklist_groups', 'delete', $checklistGroup->id, "Deleted checklist group {$checklistGroup->group_name}.");

        return redirect()->route('checklist-groups.index')->with('success', 'Checklist Group deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'sequence';
    }

    protected function sortableColumns(): array
    {
        return ['group_name', 'sequence', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['group_name'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
