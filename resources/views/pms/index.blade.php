@extends('layouts.app')
@section('title', 'Preventive Maintenance')
@section('content')
<x-page-header title="Preventive Maintenance" :breadcrumbs="['Transactions'=>null,'PMS'=>null]" />
@php $filters = $state['filters'] ?? []; @endphp
<x-list-toolbar :route="route('pms.index')" :state="$state" :create-route="auth()->user()->can('create', \App\Models\PmsHeader::class) ? route('pms.create') : null" :show-create="auth()->user()->can('create', \App\Models\PmsHeader::class)">
    <x-slot:filters>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Supplier</label>
                <select name="filters[supplier_id]" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Site</label>
                <select name="filters[site_id]" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot:filters>
</x-list-toolbar>
<div class="card"><div class="table-responsive"><table class="table table-hover table-striped mb-0 pms-index-table"><thead><tr><th>PMS No.</th><th>Site</th><th>Supplier</th><th>Technician</th><th>Status</th><th>Action Items</th><th>Date From</th><th>Date To</th><th>Created By</th><th>Created At</th><th>Finalized</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($records as $pms)<tr data-href="{{ route('pms.show',$pms) }}"><td>{{ $pms->pms_no }}</td><td>{{ $pms->site?->site_name }}</td><td>{{ $pms->supplier?->supplier_name }}</td><td>{{ $pms->technician_name }}</td><td><x-status-badge :status="$pms->status"/></td><td><x-pms-action-plan-counts :pms="$pms" /></td><td>{{ $pms->date_from?->format('Y-m-d') ?? '—' }}</td><td>{{ $pms->date_to?->format('Y-m-d') ?? '—' }}</td><td>{{ $pms->creator?->name ?? '—' }}</td><td>{{ $pms->created_at?->format('Y-m-d H:i') ?? '—' }}</td><td>{{ $pms->submitted_at?->format('Y-m-d') ?? '—' }}</td><td class="col-actions"><x-row-actions :model="$pms" :show-route="route('pms.show', $pms)" /></td></tr>@empty<tr><td colspan="12" class="text-center text-muted py-4">No PMS records.</td></tr>@endforelse</tbody></table></div>@if($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif</div>
@endsection
