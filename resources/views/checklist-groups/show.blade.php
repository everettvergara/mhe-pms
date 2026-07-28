@extends('layouts.app')
@section('title', $checklistGroup->group_name)
@section('content')
<x-page-header :title="$checklistGroup->group_name" :breadcrumbs="['Masters'=>null,'Checklist Groups'=>route('checklist-groups.index'),$checklistGroup->group_name=>null]">
<x-slot:actions>@can('update',$checklistGroup)<a href="{{ route('checklist-groups.edit',$checklistGroup) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('checklist-groups.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions></x-page-header>
<div class="card mb-3"><div class="card-body"><x-status-badge :status="$checklistGroup->status"/><dl class="row mt-2 mb-0"><dt class="col-sm-3">Sequence</dt><dd class="col-sm-9">{{ $checklistGroup->sequence }}</dd></dl></div></div>
<div class="card"><div class="card-header">Checklist Items</div><ul class="list-group list-group-flush">@forelse($checklistGroup->checklistItems as $item)<li class="list-group-item d-flex justify-content-between"><span>{{ $item->sequence }}. {{ $item->description }}</span><x-status-badge :status="$item->status"/></li>@empty<li class="list-group-item text-muted">No items.</li>@endforelse</ul></div>
<x-audit-info :model="$checklistGroup"/>
@endsection
