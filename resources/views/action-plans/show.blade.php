@extends('layouts.app')
@section('title', $actionPlan->action_plan_no)
@section('content')
<x-page-header :title="$actionPlan->action_plan_no" :breadcrumbs="['Transactions'=>null,'Action Plans'=>route('action-plans.index'),$actionPlan->action_plan_no=>null]">
<x-slot:actions><x-status-badge :status="$actionPlan->status"/>@if($canEdit)<a href="{{ route('action-plans.edit',$actionPlan) }}" class="btn btn-primary btn-sm">Edit</a>@endif<a href="{{ route('action-plans.index') }}" class="btn btn-secondary btn-sm">Back</a></x-slot:actions>
</x-page-header>

@if($pms)
@include('action-plans.partials.pms-context-card', [
    'pms' => $pms,
    'highlightDetailId' => $highlightPmsDetailId,
    'progressStatuses' => $progressStatuses,
    'canManageActionPlans' => $canManageActionPlans ?? false,
])
@endif

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <strong>Action Plan</strong>
        <x-status-badge :status="$actionPlan->status"/>
    </div>
    <div class="card-body">
        @include('action-plans.partials.pms-action-detail-panel', [
            'plan' => $actionPlan,
            'progressStatuses' => $progressStatuses,
        ])
    </div>
</div>

<x-audit-info :model="$actionPlan"/>
@endsection
