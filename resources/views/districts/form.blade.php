@extends('layouts.app')
@section('title', $isEdit ? 'Edit District' : 'New District')
@section('content')
<x-page-header :title="$isEdit ? 'Edit District' : 'New District'" :breadcrumbs="['Masters' => null, 'Districts' => route('districts.index'), ($isEdit ? 'Edit' : 'New') => null]" />
<form method="POST" action="{{ $isEdit ? route('districts.update', $district) : route('districts.store') }}">@csrf @if($isEdit) @method('PUT') @endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">District Code <span class="required-mark">*</span></label><input type="text" name="district_code" class="form-control @error('district_code') is-invalid @enderror" value="{{ old('district_code', $district->district_code) }}" required>@error('district_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-8"><label class="form-label">District Name <span class="required-mark">*</span></label><input type="text" name="district_name" class="form-control @error('district_name') is-invalid @enderror" value="{{ old('district_name', $district->district_name) }}" required>@error('district_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $district->description) }}</textarea></div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status', $district->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('districts.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
