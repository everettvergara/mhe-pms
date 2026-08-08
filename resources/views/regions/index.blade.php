@extends('layouts.app')
@section('title', 'Regions')
@section('content')
<x-page-header title="Regions" :breadcrumbs="['Masters' => null, 'Regions' => null]" />
<x-list-toolbar :route="route('regions.index')" :state="$state" :create-route="route('regions.create')" />
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Name</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($regions as $region)
<tr data-href="{{ route('regions.show', $region) }}"><td>{{ $region->region_code }}</td><td>{{ $region->region_name }}</td><td><x-status-badge :status="$region->status" /></td><td class="col-actions"><x-row-actions :model="$region" :show-route="route('regions.show', $region)" :edit-route="route('regions.edit', $region)" :delete-route="route('regions.destroy', $region)" /></td></tr>
@empty<tr><td colspan="4" class="text-center text-muted py-4">No regions found.</td></tr>@endforelse</tbody></table>
</div>@if($regions->hasPages())<div class="card-footer">{{ $regions->links() }}</div>@endif</div>
@endsection
