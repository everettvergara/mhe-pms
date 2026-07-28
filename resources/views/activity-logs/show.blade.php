@extends('layouts.app')
@section('title', 'Activity Log Details')
@section('content')
<x-page-header title="Activity Log Details" :breadcrumbs="['System'=>null,'Activity Logs'=>route('activity-logs.index'),'Details'=>null]">
<x-slot:actions><a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body">
<dl class="row mb-0">
<dt class="col-sm-3">Date/Time</dt><dd class="col-sm-9">{{ $activityLog->created_at?->format('Y-m-d H:i:s') }}</dd>
<dt class="col-sm-3">User</dt><dd class="col-sm-9">{{ $activityLog->user?->name ?? '—' }}</dd>
<dt class="col-sm-3">Module</dt><dd class="col-sm-9">{{ $activityLog->module }}</dd>
<dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ $activityLog->action }}</dd>
<dt class="col-sm-3">Record ID</dt><dd class="col-sm-9">{{ $activityLog->record_id ?? '—' }}</dd>
<dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $activityLog->description }}</dd>
<dt class="col-sm-3">IP Address</dt><dd class="col-sm-9">{{ $activityLog->ip_address ?? '—' }}</dd>
<dt class="col-sm-3">User Agent</dt><dd class="col-sm-9"><small>{{ $activityLog->user_agent ?? '—' }}</small></dd>
</dl></div></div>
@endsection
