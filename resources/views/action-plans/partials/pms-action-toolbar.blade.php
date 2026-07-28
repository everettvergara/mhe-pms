@props(['detail', 'canManageActionPlans'])

@php
    $planCount = $detail->actionPlans->count();
    $itemLabel = $detail->checklistItem?->description ?? '';
@endphp

<div class="action-items-toolbar">
    @if($planCount > 0)
        <button
            type="button"
            class="badge bg-secondary border-0"
            title="View action items"
            data-ap-action="list"
            data-pms-detail-id="{{ $detail->id }}"
            data-item-label="{{ $itemLabel }}"
        >{{ $planCount }}</button>
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            title="View action items"
            data-ap-action="list"
            data-pms-detail-id="{{ $detail->id }}"
            data-item-label="{{ $itemLabel }}"
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
            data-pms-detail-id="{{ $detail->id }}"
            data-item-label="{{ $itemLabel }}"
        >
            <i class="bi bi-plus-lg"></i>
        </button>
    @elseif($planCount > 0)
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            title="View action items"
            data-ap-action="list"
            data-pms-detail-id="{{ $detail->id }}"
            data-item-label="{{ $itemLabel }}"
        >
            <i class="bi bi-eye"></i>
        </button>
    @else
        <span class="text-muted small">—</span>
    @endif
</div>
