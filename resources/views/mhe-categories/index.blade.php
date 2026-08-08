@extends('layouts.app')
@section('title', 'MHE Categories')
@section('content')
<x-page-header title="MHE Categories" :breadcrumbs="['Masters'=>null,'MHE Categories'=>null]" />
<x-list-toolbar :route="route('mhe-categories.index')" :state="$state" :create-route="route('mhe-categories.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Name</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($mheCategories as $item)<tr data-href="{{ route('mhe-categories.show',$item) }}"><td>{{ $item->code }}</td><td>{{ $item->name }}</td><td><x-status-badge :status="$item->status"/></td><td class="col-actions"><x-row-actions :model="$item" :show-route="route('mhe-categories.show', $item)" :edit-route="route('mhe-categories.edit', $item)" :delete-route="route('mhe-categories.destroy', $item)" /></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No records.</td></tr>@endforelse</tbody></table></div>@if($mheCategories->hasPages())<div class="card-footer">{{ $mheCategories->links() }}</div>@endif</div>
@endsection
