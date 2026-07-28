@extends('layouts.app')
@section('title', $role->name)
@section('content')
<x-page-header :title="$role->name" :breadcrumbs="['Administration'=>null,'Roles'=>route('roles.index'),$role->name=>null]">
<x-slot:actions>@can('update',$role)<a href="{{ route('roles.edit',$role) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions></x-page-header>
<div class="card mb-3"><div class="card-body"><dl class="row mb-0"><dt class="col-sm-3">Slug</dt><dd class="col-sm-9">{{ $role->slug }}</dd><dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $role->description ?? '—' }}</dd><dt class="col-sm-3">System Role</dt><dd class="col-sm-9">{{ $role->is_system?'Yes':'No' }}</dd></dl></div></div>
<div class="card"><div class="card-header">Permissions</div><ul class="list-group list-group-flush">@forelse($role->permissions as $p)<li class="list-group-item">{{ $p->module }} — {{ $p->description }}</li>@empty<li class="list-group-item text-muted">No permissions assigned.</li>@endforelse</ul></div>
<x-audit-info :model="$role"/>
@can('delete',$role)<form method="POST" action="{{ route('roles.destroy',$role) }}" class="mt-3" onsubmit="return confirm('Delete role?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>@endcan
@endsection
