@extends('layouts.app')
@section('title', $isEdit?'Edit Checklist Group':'New Checklist Group')
@section('content')
<x-page-header :title="$isEdit?'Edit Checklist Group':'New Checklist Group'" :breadcrumbs="['Masters'=>null,'Checklist Groups'=>route('checklist-groups.index'),($isEdit?'Edit':'New')=>null]" />
<form method="POST" action="{{ $isEdit?route('checklist-groups.update',$checklistGroup):route('checklist-groups.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-8"><label class="form-label">Group Name <span class="required-mark">*</span></label><input name="group_name" class="form-control @error('group_name') is-invalid @enderror" value="{{ old('group_name',$checklistGroup->group_name) }}" required>@error('group_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Sequence <span class="required-mark">*</span></label><input type="number" name="sequence" min="1" class="form-control" value="{{ old('sequence',$checklistGroup->sequence) }}" required></div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status',$checklistGroup->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('checklist-groups.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
