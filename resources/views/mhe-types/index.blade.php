@extends('layouts.app')
@section('title', 'MHE Types')
@section('content')
<x-page-header title="MHE Types" :breadcrumbs="['Masters'=>null,'MHE Types'=>null]" />
<x-list-toolbar :route="route('mhe-types.index')" :state="$state" :create-route="route('mhe-types.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Description</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($mheTypes as $item)<tr data-href="{{ route('mhe-types.show',$item) }}"><td>{{ $item->code }}</td><td>{{ $item->description }}</td><td><x-status-badge :status="$item->status"/></td><td class="col-actions"><x-row-actions :model="$item" :show-route="route('mhe-types.show', $item)" :edit-route="route('mhe-types.edit', $item)" :delete-route="route('mhe-types.destroy', $item)" /></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No records.</td></tr>@endforelse</tbody></table></div>@if($mheTypes->hasPages())<div class="card-footer">{{ $mheTypes->links() }}</div>@endif</div>
@endsection
