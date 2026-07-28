@extends('layouts.app')
@section('title', 'Checklist Item')
@section('content')
<x-page-header title="Checklist Item" :breadcrumbs="['Masters'=>null,'Checklist Items'=>route('checklist-items.index'),'Details'=>null]">
<x-slot:actions>@can('update',$checklistItem)<a href="{{ route('checklist-items.edit',$checklistItem) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('checklist-items.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions></x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$checklistItem->status"/>
<dl class="row mt-2 mb-0"><dt class="col-sm-3">Group</dt><dd class="col-sm-9">{{ $checklistItem->checklistGroup?->group_name }}</dd><dt class="col-sm-3">Sequence</dt><dd class="col-sm-9">{{ $checklistItem->sequence }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $checklistItem->description }}</dd></dl></div></div>
<x-audit-info :model="$checklistItem"/>
@endsection
