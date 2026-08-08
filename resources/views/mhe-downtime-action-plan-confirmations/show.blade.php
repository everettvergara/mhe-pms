@extends('layouts.app')
@section('title', 'Confirm Action Item')
@section('content')
<x-page-header :title="$actionPlan->action_plan_no" :breadcrumbs="['Transactions'=>null,'Downtime Confirmation'=>route('mhe-downtime-action-plan-confirmations.index'),'Review'=>null]">
<x-slot:actions><x-status-badge :status="$actionPlan->status"/><a href="{{ route('mhe-downtime-action-plan-confirmations.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>

@include('mhe-downtimes.partials.downtime-context-card', ['downtime' => $downtime])

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <strong>Action Plan Under Review</strong>
        <x-status-badge :status="$actionPlan->status"/>
    </div>
    <div class="card-body">
        @include('mhe-downtimes.partials.downtime-action-detail-panel', [
            'plan' => $actionPlan,
            'downtime' => $downtime,
            'progressStatuses' => $progressStatuses,
        ])
    </div>
</div>

<x-audit-info :model="$actionPlan"/>

<div class="d-flex flex-wrap gap-2 mt-3">
    <form method="POST" action="{{ route('mhe-downtime-action-plan-confirmations.confirm', $actionPlan) }}" onsubmit="return confirm('Confirm this action item?')">@csrf<button class="btn btn-success">Confirm</button></form>
    <form method="POST" action="{{ route('mhe-downtime-action-plan-confirmations.reject', $actionPlan) }}" class="flex-grow-1" style="max-width:500px" onsubmit="return confirm('Reject this action item?')">@csrf
        <div class="input-group">
            <textarea name="rejection_remarks" class="form-control" rows="1" placeholder="Rejection remarks (required)" required></textarea>
            <button class="btn btn-danger">Reject</button>
        </div>
    </form>
</div>
@endsection
