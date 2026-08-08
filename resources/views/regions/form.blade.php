@extends('layouts.app')
@section('title', $isEdit ? 'Edit Region' : 'New Region')
@section('content')
<x-page-header :title="$isEdit ? 'Edit Region' : 'New Region'" :breadcrumbs="['Masters' => null, 'Regions' => route('regions.index'), ($isEdit ? 'Edit' : 'New') => null]" />
<form method="POST" action="{{ $isEdit ? route('regions.update', $region) : route('regions.store') }}">@csrf @if($isEdit) @method('PUT') @endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Region Code <span class="required-mark">*</span></label><input type="text" name="region_code" class="form-control @error('region_code') is-invalid @enderror" value="{{ old('region_code', $region->region_code) }}" required>@error('region_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-8"><label class="form-label">Region Name <span class="required-mark">*</span></label><input type="text" name="region_name" class="form-control @error('region_name') is-invalid @enderror" value="{{ old('region_name', $region->region_name) }}" required>@error('region_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $region->description) }}</textarea></div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status', $region->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('regions.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
