@php
    $highlightDetailId = $highlightDetailId ?? null;
    $progressStatuses = $progressStatuses ?? [];
    $canManageActionPlans = $canManageActionPlans ?? false;
@endphp

<div class="card-body py-2">
    @include('pms.partials.header-form', [
        'pms' => $pms,
        'isNew' => false,
        'readOnly' => true,
    ])
</div>

<div class="card-body border-top py-2">
    @include('pms.partials.pms-header-attachments', [
        'pms' => $pms,
        'canUploadAttachments' => false,
    ])
</div>

@if($pms->pmsDetails->isNotEmpty())
    <div class="card-body border-top px-0 py-2">
        <div class="px-3 pb-1"><strong class="small">Checklist</strong></div>
        @include('pms.partials.checklist-table', [
            'pms' => $pms,
            'editable' => false,
            'canManageActionPlans' => $canManageActionPlans,
            'canUploadAttachments' => false,
            'highlightDetailId' => $highlightDetailId,
            'progressStatuses' => $progressStatuses,
        ])
    </div>
@endif
