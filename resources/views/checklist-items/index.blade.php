@extends('layouts.app')
@section('title', 'Checklist Items')
@section('content')
<x-page-header title="Checklist Items" :breadcrumbs="['Masters'=>null,'Checklist Items'=>null]" />
<x-list-toolbar :route="route('checklist-items.index')" :state="$state" :create-route="route('checklist-items.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Group</th><th>Seq</th><th>Description</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($checklistItems as $item)<tr data-href="{{ route('checklist-items.show',$item) }}"><td>{{ $item->checklistGroup?->group_name }}</td><td>{{ $item->sequence }}</td><td>{{ $item->description }}</td><td><x-status-badge :status="$item->status"/></td><td class="col-actions"><x-row-actions :model="$item" :show-route="route('checklist-items.show', $item)" :edit-route="route('checklist-items.edit', $item)" :delete-route="route('checklist-items.destroy', $item)" /></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No records.</td></tr>@endforelse</tbody></table></div>@if($checklistItems->hasPages())<div class="card-footer">{{ $checklistItems->links() }}</div>@endif</div>
@endsection
