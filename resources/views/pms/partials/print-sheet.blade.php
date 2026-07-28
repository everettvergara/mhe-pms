<div class="pms-print-sheet d-none">
    <h1 class="print-doc-title">Preventive Maintenance</h1>
    <div class="print-doc-meta">{{ $pms->pms_no }} · Printed {{ now()->format('Y-m-d H:i') }}</div>

    <div class="mb-3">
        @include('pms.partials.header-form', [
            'pms' => $pms,
            'isNew' => false,
            'readOnly' => true,
        ])
    </div>

    @if($pms->attachments->isNotEmpty())
        <div class="mb-3">
            <h2 class="h6 mb-2">Transaction Photos</h2>
            <x-attachment-thumbnails :attachments="$pms->attachments" compact />
        </div>
    @endif

    <hr class="my-3">

    <h2 class="h6 mb-2">Checklist</h2>
    @include('pms.partials.checklist-table', [
        'pms' => $pms,
        'editable' => false,
        'canManageActionPlans' => false,
        'forPrint' => true,
        'progressStatuses' => $progressStatuses ?? [],
    ])
</div>
