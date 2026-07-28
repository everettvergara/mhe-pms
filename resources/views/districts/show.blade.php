@extends('layouts.app')
@section('title', $district->district_name)
@section('content')
<x-page-header :title="$district->district_name" :breadcrumbs="['Masters'=>null,'Districts'=>route('districts.index'),$district->district_code=>null]">
<x-slot:actions>@can('update',$district)<a href="{{ route('districts.edit',$district) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('districts.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$district->status" class="mb-2"/>
<dl class="row mb-0"><dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $district->district_code }}</dd><dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $district->district_name }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $district->description ?? '—' }}</dd></dl></div></div>
<x-audit-info :model="$district" />
@can('delete',$district)<form method="POST" action="{{ route('districts.destroy',$district) }}" class="mt-3" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>@endcan
@endsection
