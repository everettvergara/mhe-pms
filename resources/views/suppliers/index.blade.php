@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<x-page-header title="Suppliers" :breadcrumbs="['Masters' => null, 'Suppliers' => null]" />
<x-list-toolbar :route="route('suppliers.index')" :state="$state" :create-route="route('suppliers.create')" />
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr data-href="{{ route('suppliers.show', $supplier) }}">
                        <td>{{ $supplier->supplier_code }}</td>
                        <td>{{ $supplier->supplier_name }}</td>
                        <td>{{ $supplier->email ?? '—' }}</td>
                        <td><x-status-badge :status="$supplier->status" /></td>
                        <td class="col-actions"><x-row-actions :model="$supplier" :show-route="route('suppliers.show', $supplier)" :edit-route="route('suppliers.edit', $supplier)" :delete-route="route('suppliers.destroy', $supplier)" delete-confirm="Delete this supplier?" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No suppliers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())<div class="card-footer">{{ $suppliers->links() }}</div>@endif
</div>
@endsection
