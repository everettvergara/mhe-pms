@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-page-header title="Users" :breadcrumbs="['Administration'=>null,'Users'=>null]" />
<x-list-toolbar :route="route('users.index')" :state="$state" :create-route="route('users.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($users as $u)<tr data-href="{{ route('users.show',$u) }}"><td>{{ $u->username }}@if($u->isSuperAdmin()) <span class="badge text-bg-primary">Super Admin</span>@endif</td><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role?->name }}</td><td><x-status-badge :status="$u->status"/></td><td class="col-actions"><x-row-actions :model="$u" :show-route="route('users.show', $u)" :edit-route="route('users.edit', $u)" :delete-route="route('users.destroy', $u)" delete-confirm="Delete this user?" /></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No users.</td></tr>@endforelse</tbody></table></div>@if($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif</div>
@endsection
