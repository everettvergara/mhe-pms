@props(['plan', 'pmsIsDraft' => false])

@php
    $canManage = auth()->user()->isSupplier() && auth()->user()->can('update', $plan);
    $canEdit = $canManage && in_array($plan->status, [\App\Enums\ActionPlanStatus::Pending, \App\Enums\ActionPlanStatus::Rejected], true);
    $canDelete = $pmsIsDraft && auth()->user()->can('delete', $plan);
@endphp

<div class="compact-action-item d-flex flex-wrap align-items-center gap-2 py-1 border-bottom">
  <span class="fw-medium small">{{ $plan->action_plan_no }}</span>
  <span class="small text-truncate" style="max-width: 12rem;" title="{{ $plan->title }}">{{ $plan->title }}</span>
  <span class="small text-muted">{{ $plan->responsible_person }}</span>
  <span class="small text-muted">{{ $plan->timeline_from?->format('Y-m-d') }}–{{ $plan->timeline_to?->format('Y-m-d') }}</span>
  <x-status-badge :status="$plan->status" />
  @if($canEdit)
    <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="collapse" data-bs-target="#edit-plan-{{ $plan->id }}">Edit</button>
  @endif
  @if($canDelete)
    <form method="POST" action="{{ route('action-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Delete this action item?')">
      @csrf
      @method('DELETE')
      <input type="hidden" name="return_to" value="pms">
      <button type="submit" class="btn btn-sm btn-outline-danger py-0">Delete</button>
    </form>
  @endif
</div>
@if($canEdit)
  <div class="collapse mb-1" id="edit-plan-{{ $plan->id }}">
    @include('action-plans.partials.compact-form', ['pmsDetail' => $plan->pmsDetail, 'actionPlan' => $plan])
  </div>
@endif
