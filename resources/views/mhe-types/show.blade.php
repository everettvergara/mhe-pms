@extends('layouts.app')
@section('title', $mheType->code)
@section('content')
<x-page-header :title="$mheType->code" :breadcrumbs="['Masters'=>null,'MHE Types'=>route('mhe-types.index'),$mheType->code=>null]">
<x-slot:actions>@can('update',$mheType)<a href="{{ route('mhe-types.edit',$mheType) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('mhe-types.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions></x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$mheType->status"/><dl class="row mt-2 mb-0"><dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $mheType->code }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $mheType->description }}</dd></dl></div></div>
<x-audit-info :model="$mheType"/>
@can('delete',$mheType)<form method="POST" action="{{ route('mhe-types.destroy',$mheType) }}" class="mt-3" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>@endcan
@endsection
