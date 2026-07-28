@extends('layouts.app')
@section('title', $isEdit?'Edit Role':'New Role')
@section('content')
<x-page-header :title="$isEdit?'Edit Role':'New Role'" :breadcrumbs="['Administration'=>null,'Roles'=>route('roles.index'),($isEdit?'Edit':'New')=>null]" />
<form method="POST" action="{{ $isEdit?route('roles.update',$role):route('roles.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Name <span class="required-mark">*</span></label><input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name',$role->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Slug <span class="required-mark">*</span></label><input name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug',$role->slug) }}" required @if($role->is_system) readonly @endif>@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description',$role->description) }}</textarea></div>
<div class="col-12"><label class="form-label">Permissions</label>
@php $grouped = $permissions->groupBy('module'); @endphp
@foreach($grouped as $module => $perms)<div class="mb-2"><strong>{{ $module }}</strong><div class="row">@foreach($perms as $p)<div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $p->id }}" id="perm_{{ $p->id }}" @checked(in_array($p->id, old('permissions', $assignedPermissionIds)))><label class="form-check-label" for="perm_{{ $p->id }}">{{ $p->description }}</label></div></div>@endforeach</div></div>@endforeach
</div>
</div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button><a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection
