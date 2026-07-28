@extends('layouts.app')
@section('title', $isEdit?'Edit Checklist Item':'New Checklist Item')
@section('content')
<x-page-header :title="$isEdit?'Edit Checklist Item':'New Checklist Item'" :breadcrumbs="['Masters'=>null,'Checklist Items'=>route('checklist-items.index'),($isEdit?'Edit':'New')=>null]" />
<form method="POST" action="{{ $isEdit?route('checklist-items.update',$checklistItem):route('checklist-items.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Checklist Group <span class="required-mark">*</span></label><select name="checklist_group_id" class="form-select" required>@foreach($checklistGroups as $g)<option value="{{ $g->id }}" @selected(old('checklist_group_id',$checklistItem->checklist_group_id)==$g->id)>{{ $g->group_name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Sequence <span class="required-mark">*</span></label><input type="number" name="sequence" min="1" class="form-control" value="{{ old('sequence',$checklistItem->sequence) }}" required></div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status',$checklistItem->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Description <span class="required-mark">*</span></label><textarea name="description" class="form-control" rows="3" required>{{ old('description',$checklistItem->description) }}</textarea></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('checklist-items.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
