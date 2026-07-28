@extends('layouts.app')
@section('title', $isEdit?'Edit MHE Type':'New MHE Type')
@section('content')
<x-page-header :title="$isEdit?'Edit MHE Type':'New MHE Type'" :breadcrumbs="['Masters'=>null,'MHE Types'=>route('mhe-types.index'),($isEdit?'Edit':'New')=>null]" />
<form method="POST" action="{{ $isEdit?route('mhe-types.update',$mheType):route('mhe-types.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Code <span class="required-mark">*</span></label><input name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code',$mheType->code) }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-8"><label class="form-label">Description <span class="required-mark">*</span></label><input name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description',$mheType->description) }}" required>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status',$mheType->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('mhe-types.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
