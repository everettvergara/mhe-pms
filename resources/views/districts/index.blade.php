@extends('layouts.app')
@section('title', 'Districts')
@section('content')
<x-page-header title="Districts" :breadcrumbs="['Masters' => null, 'Districts' => null]" />
<x-list-toolbar :route="route('districts.index')" :state="$state" :create-route="route('districts.create')" />
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Name</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($districts as $district)
<tr data-href="{{ route('districts.show', $district) }}"><td>{{ $district->district_code }}</td><td>{{ $district->district_name }}</td><td><x-status-badge :status="$district->status" /></td><td class="col-actions"><x-row-actions :model="$district" :show-route="route('districts.show', $district)" :edit-route="route('districts.edit', $district)" :delete-route="route('districts.destroy', $district)" /></td></tr>
@empty<tr><td colspan="4" class="text-center text-muted py-4">No districts found.</td></tr>@endforelse</tbody></table>
</div>@if($districts->hasPages())<div class="card-footer">{{ $districts->links() }}</div>@endif</div>
@endsection
