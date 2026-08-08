@props(['downtime', 'canManageActionPlans'])

@php
    $planCount = $downtime->actionPlans->count();
    $contextLabel = ($downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? 'Unit').' — '.$downtime->title;
@endphp

<div class="action-items-toolbar">
    @if($planCount > 0)
        <button
            type="button"
            class="badge bg-secondary border-0"
            title="View action items"
            data-ap-action="list"
            data-downtime-id="{{ $downtime->id }}"
            data-item-label="{{ $contextLabel }}"
        >{{ $planCount }}</button>
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            title="View action items"
            data-ap-action="list"
            data-downtime-id="{{ $downtime->id }}"
            data-item-label="{{ $contextLabel }}"
        >
            <i class="bi bi-list-ul"></i>
        </button>
    @endif
    @if($canManageActionPlans)
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            title="Add action item"
            data-ap-action="add"
            data-downtime-id="{{ $downtime->id }}"
            data-item-label="{{ $contextLabel }}"
        >
            <i class="bi bi-plus-lg"></i>
        </button>
    @elseif($planCount > 0)
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            title="View action items"
            data-ap-action="list"
            data-downtime-id="{{ $downtime->id }}"
            data-item-label="{{ $contextLabel }}"
        >
            <i class="bi bi-eye"></i>
        </button>
    @else
        <span class="text-muted small">—</span>
    @endif
</div>
