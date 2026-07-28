<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\UpdateChecklistItemRequest;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChecklistItemController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(ChecklistItem::class, 'checklist_item');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'checklist-items', ['sort' => 'sequence', 'direction' => 'asc']);

        $query = ChecklistItem::query()->with('checklistGroup');
        $checklistItems = $this->paginateList($this->applyListQuery($query, $state), $state);
        $checklistGroups = ChecklistGroup::query()->where('status', RecordStatus::Active)->orderBy('sequence')->get();

        return view('checklist-items.index', compact('checklistItems', 'checklistGroups', 'state'));
    }

    public function create(): View
    {
        $checklistGroups = ChecklistGroup::query()->where('status', RecordStatus::Active)->orderBy('sequence')->get();

        return view('checklist-items.form', [
            'checklistItem' => new ChecklistItem(['status' => RecordStatus::Active, 'sequence' => 1]),
            'checklistGroups' => $checklistGroups,
            'isEdit' => false,
        ]);
    }

    public function store(StoreChecklistItemRequest $request): RedirectResponse
    {
        $checklistItem = ChecklistItem::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log($request->user(), 'checklist_items', 'create', $checklistItem->id, 'Created checklist item.');

        return redirect()->route('checklist-items.show', $checklistItem)->with('success', 'Checklist Item created successfully.');
    }

    public function show(ChecklistItem $checklistItem): View
    {
        $checklistItem->load('checklistGroup');

        return view('checklist-items.show', compact('checklistItem'));
    }

    public function edit(ChecklistItem $checklistItem): View
    {
        $checklistGroups = ChecklistGroup::query()->where('status', RecordStatus::Active)->orderBy('sequence')->get();

        return view('checklist-items.form', [
            'checklistItem' => $checklistItem,
            'checklistGroups' => $checklistGroups,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        $this->activityLogService->log($request->user(), 'checklist_items', 'update', $checklistItem->id, 'Updated checklist item.');

        return redirect()->route('checklist-items.show', $checklistItem)->with('success', 'Checklist Item updated successfully.');
    }

    public function destroy(Request $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->update(['updated_by' => $request->user()->id]);
        $checklistItem->delete();
        $this->activityLogService->log($request->user(), 'checklist_items', 'delete', $checklistItem->id, 'Deleted checklist item.');

        return redirect()->route('checklist-items.index')->with('success', 'Checklist Item deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'sequence';
    }

    protected function sortableColumns(): array
    {
        return ['sequence', 'description', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['description'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['checklist_group_id'])) {
            $query->where('checklist_group_id', $filters['checklist_group_id']);
        }

        return $query;
    }
}
