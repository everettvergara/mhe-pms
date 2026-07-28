@props(['pms', 'highlightDetailId' => null, 'progressStatuses' => [], 'canManageActionPlans' => false])

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <strong>PMS Information</strong>
            @if($pms->supplier)
                <span class="text-muted small ms-2">{{ $pms->supplier->supplier_name }}</span>
            @endif
        </div>
        <a href="{{ route('pms.show', $pms) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer">View full PMS</a>
    </div>
    @include('pms.partials.read-only-panel', [
        'pms' => $pms,
        'highlightDetailId' => $highlightDetailId,
        'progressStatuses' => $progressStatuses,
        'canManageActionPlans' => $canManageActionPlans,
    ])
</div>
