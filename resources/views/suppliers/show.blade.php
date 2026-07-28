@extends('layouts.app')
@section('title', $supplier->supplier_name)
@section('content')
<x-page-header :title="$supplier->supplier_name" :breadcrumbs="['Masters' => null, 'Suppliers' => route('suppliers.index'), $supplier->supplier_code => null]">
    <x-slot:actions>
        @can('update', $supplier)<a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">Edit</a>@endcan
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body">
    <div class="d-flex align-items-start gap-3 mb-3">
        @if ($supplier->imageUrl())
            <img src="{{ $supplier->imageUrl() }}" alt="{{ $supplier->supplier_name }}" class="supplier-preview" width="96" height="96">
        @endif
        <div>
            <div class="mb-2"><x-status-badge :status="$supplier->status" /></div>
        </div>
    </div>
    <dl class="row mb-0">
        <dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $supplier->supplier_code }}</dd>
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $supplier->supplier_name }}</dd>
        <dt class="col-sm-3">Contact Person</dt><dd class="col-sm-9">{{ $supplier->contact_person ?? '—' }}</dd>
        <dt class="col-sm-3">Contact Number</dt><dd class="col-sm-9">{{ $supplier->contact_number ?? '—' }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $supplier->email ?? '—' }}</dd>
        <dt class="col-sm-3">Address</dt><dd class="col-sm-9">{{ $supplier->address ?? '—' }}</dd>
    </dl>
</div></div>
<x-audit-info :model="$supplier" />
@can('delete', $supplier)
<form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="mt-3" onsubmit="return confirm('Delete this supplier?')">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
</form>
@endcan
@endsection
