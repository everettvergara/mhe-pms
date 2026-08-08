@extends('layouts.app')
@section('title', $region->region_name)
@section('content')
<x-page-header :title="$region->region_name" :breadcrumbs="['Masters'=>null,'Regions'=>route('regions.index'),$region->region_code=>null]">
<x-slot:actions>@can('update',$region)<a href="{{ route('regions.edit',$region) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('regions.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$region->status" class="mb-2"/>
<dl class="row mb-0"><dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $region->region_code }}</dd><dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $region->region_name }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $region->description ?? '—' }}</dd></dl></div></div>
<x-audit-info :model="$region" />
@can('delete',$region)<form method="POST" action="{{ route('regions.destroy',$region) }}" class="mt-3" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>@endcan
@endsection
