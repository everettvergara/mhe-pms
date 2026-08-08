@extends('layouts.app')
@section('title', 'MHE Inventories')
@section('content')
@php $filters = $state['filters'] ?? []; @endphp
<x-page-header title="MHE Inventories" :breadcrumbs="['Masters' => null, 'MHE Inventories' => null]" />
<x-list-toolbar :route="route('mhe-inventories.index')" :state="$state" :create-route="route('mhe-inventories.create')">
    <x-slot:filters>
        <div class="row g-2">
            <div class="col-md-3">
                <select name="filters[site_id]" class="form-select form-select-sm">
                    <option value="">All sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_code }} — {{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="filters[mhe_type_id]" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach($mheTypes as $type)
                        <option value="{{ $type->id }}" @selected(($filters['mhe_type_id'] ?? '') == $type->id)>{{ $type->code }} — {{ $type->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="filters[supplier_id]" class="form-select form-select-sm">
                    <option value="">All suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="filters[equipment_status]" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(\App\Enums\RecordStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['equipment_status'] ?? '') === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot:filters>
</x-list-toolbar>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead>
        <tr>
            <th>Unit No</th>
            <th>Site</th>
            <th>Type</th>
            <th>Brand</th>
            <th>Supplier</th>
            <th>Next PMS</th>
            <th>Status</th>
            <th class="col-actions">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inventories as $item)
            @php
                $daysUntil = $item->next_pms_date
                    ? now()->startOfDay()->diffInDays($item->next_pms_date, false)
                    : null;
                $urgencyClass = $daysUntil === null ? '' : ($daysUntil < 0 ? 'text-danger' : ($daysUntil <= 7 ? 'text-warning' : ''));
            @endphp
            <tr data-href="{{ route('mhe-inventories.show', $item) }}">
                <td>{{ $item->unit_no ?? '—' }}</td>
                <td>{{ $item->siteRelation?->site_name ?? $item->site ?? '—' }}</td>
                <td>{{ $item->mheType?->code ?? $item->equipment_type ?? '—' }}</td>
                <td>{{ $item->brand ?? '—' }}</td>
                <td>{{ $item->supplier?->supplier_name ?? $item->provider ?? '—' }}</td>
                <td class="{{ $urgencyClass }}">{{ $item->next_pms_date?->format('Y-m-d') ?? '—' }}</td>
                <td><x-status-badge :status="$item->equipment_status" /></td>
                <td class="col-actions">
                    <x-row-actions
                        :model="$item"
                        :show-route="route('mhe-inventories.show', $item)"
                        :edit-route="route('mhe-inventories.edit', $item)"
                        :delete-route="route('mhe-inventories.destroy', $item)"
                    />
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No records.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($inventories->hasPages())
    <div class="card-footer">{{ $inventories->links() }}</div>
@endif
</div>
@endsection
