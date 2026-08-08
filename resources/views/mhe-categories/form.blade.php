@extends('layouts.app')
@section('title', $isEdit?'Edit MHE Category':'New MHE Category')
@section('content')
<x-page-header :title="$isEdit?'Edit MHE Category':'New MHE Category'" :breadcrumbs="['Masters'=>null,'MHE Categories'=>route('mhe-categories.index'),($isEdit?'Edit':'New')=>null]" />
<form method="POST" action="{{ $isEdit?route('mhe-categories.update',$mheCategory):route('mhe-categories.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Code <span class="required-mark">*</span></label><input name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code',$mheCategory->code) }}" required maxlength="30">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Name <span class="required-mark">*</span></label><input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name',$mheCategory->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select name="status" class="form-select">@foreach(\App\Enums\RecordStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status',$mheCategory->status?->value)===$s->value)>{{ $s->value }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks',$mheCategory->remarks) }}</textarea></div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('mhe-categories.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
