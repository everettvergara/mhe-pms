<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected UserDataScopeService $userDataScopeService,
    ) {
        $this->authorizeResource(Supplier::class, 'supplier');
    }

    public function index(Request $request): View
    {
        $state = $this->resolveListState($request, 'suppliers', ['sort' => 'supplier_code', 'direction' => 'asc']);

        $query = Supplier::query();
        $this->userDataScopeService->scopeSupplier($query, $request->user());

        $suppliers = $this->paginateList(
            $this->applyListQuery($query, $state),
            $state,
        );

        return view('suppliers.index', compact('suppliers', 'state'));
    }

    public function create(): View
    {
        return view('suppliers.form', [
            'supplier' => new Supplier(['status' => RecordStatus::Active]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $validated = collect($request->validated())
            ->except(['image'])
            ->all();

        $supplier = Supplier::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if ($request->hasFile('image')) {
            $supplier->image = $request->file('image')->store(
                'supplier-images/'.$supplier->id,
                'public',
            );
            $supplier->save();
        }

        $this->activityLogService->log($request->user(), 'suppliers', 'create', $supplier->id, "Created supplier {$supplier->supplier_code}.");

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier): View
    {
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', [
            'supplier' => $supplier,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $validated = collect($request->validated())
            ->except(['image', 'remove_image'])
            ->all();

        $supplier->fill([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        if ($request->boolean('remove_image')) {
            $this->deleteSupplierImage($supplier);
            $supplier->image = null;
        } elseif ($request->hasFile('image')) {
            $this->deleteSupplierImage($supplier);
            $supplier->image = $request->file('image')->store(
                'supplier-images/'.$supplier->id,
                'public',
            );
        }

        $supplier->save();

        $this->activityLogService->log($request->user(), 'suppliers', 'update', $supplier->id, "Updated supplier {$supplier->supplier_code}.");

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->deleteSupplierImage($supplier);
        $supplier->update(['updated_by' => $request->user()->id]);
        $supplier->delete();

        $this->activityLogService->log($request->user(), 'suppliers', 'delete', $supplier->id, "Deleted supplier {$supplier->supplier_code}.");

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    protected function defaultSortColumn(): string
    {
        return 'supplier_code';
    }

    protected function sortableColumns(): array
    {
        return ['supplier_code', 'supplier_name', 'status', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['supplier_code', 'supplier_name', 'email', 'contact_person'];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    private function deleteSupplierImage(Supplier $supplier): void
    {
        if ($supplier->image && Storage::disk('public')->exists($supplier->image)) {
            Storage::disk('public')->delete($supplier->image);
        }
    }
}
