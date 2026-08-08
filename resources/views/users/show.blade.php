@extends('layouts.app')
@section('title', $user->username)
@section('content')
<x-page-header :title="$user->name" :breadcrumbs="['Administration'=>null,'Users'=>route('users.index'),$user->username=>null]">
<x-slot:actions>@can('update',$user)<a href="{{ route('users.edit',$user) }}" class="btn btn-primary btn-sm">Edit</a>@endcan<a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions></x-page-header>
<div class="card"><div class="card-body"><x-status-badge :status="$user->status"/>
@if($user->isSuperAdmin())<span class="badge text-bg-primary ms-2">Super Admin</span>@endif
<dl class="row mt-2 mb-0">
<dt class="col-sm-3">Username</dt><dd class="col-sm-9">{{ $user->username }}</dd>
<dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
<dt class="col-sm-3">Contact</dt><dd class="col-sm-9">{{ $user->contact_number ?? '—' }}</dd>
<dt class="col-sm-3">Role</dt><dd class="col-sm-9">{{ $user->role?->name }}</dd>
<dt class="col-sm-3">Super Admin</dt><dd class="col-sm-9">{{ $user->isSuperAdmin() ? 'Yes' : 'No' }}</dd>
@if(!$user->isSuperAdmin())
<dt class="col-sm-3">Assigned Suppliers</dt><dd class="col-sm-9">{{ $user->suppliers->pluck('supplier_name')->join(', ') ?: '—' }}</dd>
<dt class="col-sm-3">Assigned Districts</dt>
<dd class="col-sm-9">
    @if($assignedDistrictNames === [])
        <span class="text-warning">No site access assigned</span>
    @else
        {{ implode(', ', $assignedDistrictNames) }}
    @endif
</dd>
<dt class="col-sm-3">Assigned Sites</dt>
<dd class="col-sm-9">
    @if($sitesByDistrict->isEmpty())
        <span class="text-warning">No sites assigned — user cannot access scoped data.</span>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach($sitesByDistrict as $districtName => $sites)
                <div>
                    <div class="fw-semibold">{{ $districtName }}</div>
                    <div>{{ $sites->pluck('site_name')->join(', ') }}</div>
                </div>
            @endforeach
        </div>
    @endif
</dd>
@endif
</dl></div></div>
<x-audit-info :model="$user"/>
@endsection
