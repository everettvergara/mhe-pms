<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreMheInventoryRequest;
use App\Http\Requests\UpdateMheInventoryRequest;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MheInventoryController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {
        $this->authorizeResource(MheInventory::class, 'mhe_inventory');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'mhe-inventories', ['sort' => 'unit_no', 'direction' => 'asc']);

        $query = MheInventory::query()->with(['siteRelation', 'supplier', 'mheType', 'lastPmsHeader']);
        $inventories = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('mhe-inventories.index', [
            'inventories' => $inventories,
            'state' => $state,
            'sites' => Site::query()->orderBy('site_name')->get(['id', 'site_code', 'site_name']),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'mheTypes' => MheType::query()->orderBy('code')->get(['id', 'code', 'description']),
        ]);
    }

    public function create(): View
    {
        return view('mhe-inventories.form', [
            'inventory' => new MheInventory([
                'equipment_status' => RecordStatus::Active,
                'unit_role' => 'Primary',
            ]),
            'isEdit' => false,
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreMheInventoryRequest $request): RedirectResponse
    {
        $inventory = MheInventory::query()->create([
            ...$this->payloadFromValidated($request->validated()),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log(
            $request->user(),
            'mhe_inventories',
            'create',
            $inventory->id,
            "Created MHE inventory {$inventory->unit_no}.",
        );

        return redirect()->route('mhe-inventories.show', $inventory)
            ->with('success', 'MHE Inventory created successfully.');
    }

    public function show(MheInventory $mheInventory): View
    {
        $mheInventory->load(['siteRelation.district', 'supplier', 'mheType', 'lastPmsHeader']);

        return view('mhe-inventories.show', ['inventory' => $mheInventory]);
    }

    public function edit(MheInventory $mheInventory): View
    {
        return view('mhe-inventories.form', [
            'inventory' => $mheInventory,
            'isEdit' => true,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateMheInventoryRequest $request, MheInventory $mheInventory): RedirectResponse
    {
        $mheInventory->update([
            ...$this->payloadFromValidated($request->validated()),
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogService->log(
            $request->user(),
            'mhe_inventories',
            'update',
            $mheInventory->id,
            "Updated MHE inventory {$mheInventory->unit_no}.",
        );

        return redirect()->route('mhe-inventories.show', $mheInventory)
            ->with('success', 'MHE Inventory updated successfully.');
    }

    public function destroy(Request $request, MheInventory $mheInventory): RedirectResponse
    {
        $mheInventory->update(['updated_by' => $request->user()->id]);
        $mheInventory->delete();

        $this->activityLogService->log(
            $request->user(),
            'mhe_inventories',
            'delete',
            $mheInventory->id,
            "Deleted MHE inventory {$mheInventory->unit_no}.",
        );

        return redirect()->route('mhe-inventories.index')
            ->with('success', 'MHE Inventory deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'unit_no';
    }

    protected function sortableColumns(): array
    {
        return ['unit_no', 'brand', 'equipment_status', 'next_pms_date', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['unit_no', 'brand', 'model', 'site', 'provider', 'equipment_type', 'client_fsc'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['equipment_status'])) {
            $query->where('equipment_status', $filters['equipment_status']);
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['mhe_type_id'])) {
            $query->where('mhe_type_id', $filters['mhe_type_id']);
        }

        return $query;
    }

    /**
     * @return array{sites: \Illuminate\Support\Collection, suppliers: \Illuminate\Support\Collection, mheTypes: \Illuminate\Support\Collection}
     */
    private function formOptions(): array
    {
        return [
            'sites' => Site::query()->orderBy('site_name')->get(),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
            'mheTypes' => MheType::query()->orderBy('code')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payloadFromValidated(array $validated): array
    {
        $site = Site::query()->with('district')->find($validated['site_id'] ?? null);
        $supplier = ! empty($validated['supplier_id'])
            ? Supplier::query()->find($validated['supplier_id'])
            : null;
        $mheType = ! empty($validated['mhe_type_id'])
            ? MheType::query()->find($validated['mhe_type_id'])
            : null;

        return [
            ...$validated,
            'unit_role' => $validated['unit_role'] ?: 'Primary',
            'site' => $site?->site_name,
            'district' => $site?->district?->district_name,
            'provider' => $supplier?->supplier_name,
            'equipment_type' => $mheType?->description,
        ];
    }
}
