@extends('layouts.app')
@section('title', 'Roles')
@section('content')
<x-page-header title="User Access / Roles" :breadcrumbs="['Administration'=>null,'Roles'=>null]" />
<x-list-toolbar :route="route('roles.index')" :state="$state" :create-route="route('roles.create')" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Name</th><th>Slug</th><th>Permissions</th><th>System</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($roles as $r)<tr data-href="{{ route('roles.show',$r) }}"><td>{{ $r->name }}</td><td>{{ $r->slug }}</td><td>{{ $r->permissions_count }}</td><td>{{ $r->is_system?'Yes':'No' }}</td><td class="col-actions"><x-row-actions :model="$r" :show-route="route('roles.show', $r)" :edit-route="route('roles.edit', $r)" :delete-route="route('roles.destroy', $r)" delete-confirm="Delete this role?" /></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No roles.</td></tr>@endforelse</tbody></table></div>@if($roles->hasPages())<div class="card-footer">{{ $roles->links() }}</div>@endif</div>
@endsection
