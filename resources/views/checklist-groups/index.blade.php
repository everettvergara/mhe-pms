@extends('layouts.app')
@section('title', 'Checklist Groups')
@section('content')
<x-page-header title="Checklist Groups" :breadcrumbs="['Masters'=>null,'Checklist Groups'=>null]" />
<x-list-toolbar :route="route('checklist-groups.index')" :state="$state" :create-route="route('checklist-groups.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Sequence</th><th>Group Name</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($checklistGroups as $g)<tr data-href="{{ route('checklist-groups.show',$g) }}"><td>{{ $g->sequence }}</td><td>{{ $g->group_name }}</td><td><x-status-badge :status="$g->status"/></td><td class="col-actions"><x-row-actions :model="$g" :show-route="route('checklist-groups.show', $g)" :edit-route="route('checklist-groups.edit', $g)" :delete-route="route('checklist-groups.destroy', $g)" /></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No records.</td></tr>@endforelse</tbody></table></div>@if($checklistGroups->hasPages())<div class="card-footer">{{ $checklistGroups->links() }}</div>@endif</div>
@endsection
