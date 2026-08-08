@extends('layouts.app')
@section('title', 'Sites')
@section('content')
<x-page-header title="Sites" :breadcrumbs="['Masters' => null, 'Sites' => null]" />
<x-list-toolbar :route="route('sites.index')" :state="$state" :create-route="route('sites.create')" />
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Name</th><th>District</th><th>Region</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($sites as $site)
<tr data-href="{{ route('sites.show', $site) }}"><td>{{ $site->site_code }}</td><td>{{ $site->site_name }}</td><td>{{ $site->district?->district_name ?? '—' }}</td><td>{{ $site->region?->region_name ?? '—' }}</td><td><x-status-badge :status="$site->status" /></td><td class="col-actions"><x-row-actions :model="$site" :show-route="route('sites.show', $site)" :edit-route="route('sites.edit', $site)" :delete-route="route('sites.destroy', $site)" /></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">No sites found.</td></tr>@endforelse</tbody></table>
</div>@if($sites->hasPages())<div class="card-footer">{{ $sites->links() }}</div>@endif</div>
@endsection
