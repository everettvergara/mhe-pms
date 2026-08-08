@props(['plan', 'downtime', 'canManageActionPlans'])

@php
    $canManagePlan = auth()->user()->isSupplier() && auth()->user()->can('update', $plan);
    $canEdit = $canManagePlan && in_array($plan->status, [\App\Enums\DowntimeActionPlanStatus::Pending, \App\Enums\DowntimeActionPlanStatus::Rejected], true);
    $canDelete = $canManagePlan && $plan->status === \App\Enums\DowntimeActionPlanStatus::Pending;
    $contextLabel = ($downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? 'Unit').' — '.$downtime->title;
@endphp

<tr>
    <td class="small fw-medium">{{ $plan->action_plan_no }}</td>
    <td class="small">{{ $plan->title }}</td>
    <td class="small">{{ $plan->responsible_person }}</td>
    <td class="small text-nowrap">{{ $plan->timeline_from?->format('Y-m-d') }} – {{ $plan->timeline_to?->format('Y-m-d') }}</td>
    <td><x-status-badge :status="$plan->status" /></td>
    <td class="text-nowrap">
        @if($canEdit)
            <button
                type="button"
                class="btn btn-link btn-sm p-0 me-1"
                title="Edit"
                data-ap-action="edit"
                data-downtime-id="{{ $downtime->id }}"
                data-item-label="{{ $contextLabel }}"
                data-plan-id="{{ $plan->id }}"
                data-update-url="{{ route('mhe-downtimes.action-plans.update', [$downtime, $plan]) }}"
                data-title="{{ $plan->title }}"
                data-description="{{ $plan->description }}"
                data-responsible-person="{{ $plan->responsible_person }}"
                data-timeline-from="{{ $plan->timeline_from?->format('Y-m-d') }}"
                data-timeline-to="{{ $plan->timeline_to?->format('Y-m-d') }}"
            >
                <i class="bi bi-pencil"></i>
            </button>
        @endif
        @if($canDelete)
            <form method="POST" action="{{ route('mhe-downtimes.action-plans.destroy', [$downtime, $plan]) }}" class="d-inline" onsubmit="return confirm('Delete this action item?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="return_to" value="downtime">
                <button type="submit" class="btn btn-link btn-sm p-0 text-danger me-1" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        @endif
        @if($canManageActionPlans)
            <button
                type="button"
                class="btn btn-link btn-sm p-0"
                title="Update status"
                data-ap-action="detail"
                data-plan-id="{{ $plan->id }}"
                data-downtime-id="{{ $downtime->id }}"
                data-item-label="{{ $contextLabel }}"
            >
                <i class="bi bi-arrow-repeat"></i>
            </button>
        @endif
    </td>
</tr>
