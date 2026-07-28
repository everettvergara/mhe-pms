@extends('layouts.app')
@section('title', 'Activity Logs')
@section('content')
<x-page-header title="Activity Logs" :breadcrumbs="['System'=>null,'Activity Logs'=>null]" />
<x-list-toolbar :route="route('activity-logs.index')" :state="$state" :show-create="false" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Date</th><th>User</th><th>Module</th><th>Action</th><th>Description</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($records as $log)<tr data-href="{{ route('activity-logs.show',$log) }}"><td>{{ $log->created_at?->format('Y-m-d H:i') }}</td><td>{{ $log->user?->name ?? '—' }}</td><td>{{ $log->module }}</td><td>{{ $log->action }}</td><td>{{ Str::limit($log->description, 80) }}</td><td class="col-actions"><x-row-actions :model="$log" :show-route="route('activity-logs.show', $log)" /></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No logs.</td></tr>@endforelse</tbody></table></div>@if($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif</div>
@endsection
