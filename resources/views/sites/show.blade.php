@extends('layouts.app')
@section('title', $site->site_name)
@section('content')
<x-page-header :title="$site->site_name" :breadcrumbs="['Masters'=>null,'Sites'=>route('sites.index'),$site->site_code=>null]">
<x-slot:actions>@can('update',$site)<a href="{{ route('sites.edit',$site) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('sites.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$site->status" class="mb-2"/>
<dl class="row mb-0"><dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $site->site_code }}</dd><dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $site->site_name }}</dd><dt class="col-sm-3">District</dt><dd class="col-sm-9">{{ $site->district?->district_name ?? '—' }}</dd><dt class="col-sm-3">Region</dt><dd class="col-sm-9">{{ $site->region?->region_name ?? '—' }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $site->description ?? '—' }}</dd></dl></div></div>
<x-audit-info :model="$site" />
@can('delete',$site)<form method="POST" action="{{ route('sites.destroy',$site) }}" class="mt-3" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>@endcan
@endsection
