@extends('layouts.app')
@section('title', $mheCategory->name)
@section('content')
<x-page-header :title="$mheCategory->name" :breadcrumbs="['Masters'=>null,'MHE Categories'=>route('mhe-categories.index'),$mheCategory->code=>null]" />
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label text-muted">Code</label><div>{{ $mheCategory->code }}</div></div>
<div class="col-md-4"><label class="form-label text-muted">Name</label><div>{{ $mheCategory->name }}</div></div>
<div class="col-md-4"><label class="form-label text-muted">Status</label><div><x-status-badge :status="$mheCategory->status"/></div></div>
<div class="col-12"><label class="form-label text-muted">Remarks</label><div>{{ $mheCategory->remarks ?: '—' }}</div></div>
</div><div class="card-footer d-flex gap-2">
@can('update', $mheCategory)<a href="{{ route('mhe-categories.edit', $mheCategory) }}" class="btn btn-primary">Edit</a>@endcan
<a href="{{ route('mhe-categories.index') }}" class="btn btn-secondary">Back</a>
</div></div>
@endsection
