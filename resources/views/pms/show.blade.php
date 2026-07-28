@extends('layouts.app')
@section('title', $isNew ? 'New PMS' : $pms->pms_no)
@section('content')
<x-page-header
    :title="$isNew ? 'New PMS' : $pms->pms_no"
    :breadcrumbs="['Transactions'=>null,'PMS'=>route('pms.index'),($isNew ? 'New' : $pms->pms_no)=>null]"
/>

<div class="pms-screen-content">
    <div class="card mb-3">
        @if($isNew || $canEdit)
            <form id="pms-save-form" method="POST" action="{{ $isNew ? route('pms.store') : route('pms.update', $pms) }}">
                @csrf
                @if(!$isNew) @method('PUT') @endif
                <div class="card-body py-2">
                    @include('pms.partials.header-form', [
                        'pms' => $pms,
                        'isNew' => $isNew,
                        'sites' => $sites,
                        'suppliers' => $suppliers ?? collect(),
                        'mheTypes' => $mheTypes,
                    ])
                </div>

                @if(!$isNew && $pms->pmsDetails->isNotEmpty())
                    <div class="card-body border-top px-0 py-2">
                        <div class="px-3 pb-1"><strong class="small">Checklist</strong></div>
                        @include('pms.partials.checklist-table', [
                            'pms' => $pms,
                            'editable' => true,
                            'canManageActionPlans' => $canManageActionPlans ?? false,
                            'canUploadAttachments' => $canUploadAttachments ?? false,
                            'progressStatuses' => $progressStatuses,
                            'renderModals' => false,
                        ])
                    </div>
                @endif
            </form>

            @if(!$isNew)
                <div class="card-body border-top py-2">
                    @include('pms.partials.pms-header-attachments', [
                        'pms' => $pms,
                        'canUploadAttachments' => $canUploadAttachments ?? false,
                    ])
                </div>
            @endif

            @if(!$isNew && $pms->pmsDetails->isNotEmpty())
                @php
                    $pmsSortedDetails = $pms->pmsDetails->sortBy(
                        fn ($detail) => ($detail->checklistItem?->checklistGroup?->sequence ?? 0).'-'.($detail->checklistItem?->sequence ?? 0)
                    );
                    $showPmsActionModals = ($canManageActionPlans ?? false)
                        || $pmsSortedDetails->contains(fn ($detail) => $detail->actionPlans->isNotEmpty());
                @endphp
                @if($showPmsActionModals)
                    @include('action-plans.partials.pms-action-modals', [
                        'pms' => $pms,
                        'sortedDetails' => $pmsSortedDetails,
                        'canManageActionPlans' => $canManageActionPlans ?? false,
                        'pmsIsDraft' => $pms->isDraft(),
                        'progressStatuses' => $progressStatuses,
                    ])
                @endif
            @endif

            @if(!$isNew && ($canUploadAttachments ?? false) && $pms->pmsDetails->isNotEmpty())
                @include('pms.partials.checklist-attachment-forms', [
                    'pms' => $pms,
                    'canUploadAttachments' => $canUploadAttachments ?? false,
                ])
            @endif
        @else
            @include('pms.partials.read-only-panel', [
                'pms' => $pms,
                'canManageActionPlans' => $canManageActionPlans ?? false,
                'progressStatuses' => $progressStatuses,
            ])
        @endif

        <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
            @if($isNew || $canEdit)
                <button type="submit" form="pms-save-form" name="save_as" value="draft" class="btn btn-primary btn-sm">Save as Draft</button>
            @endif
            @if(($canFinalize ?? false) && !$isNew)
                <button type="submit" form="pms-save-form" name="save_as" value="final" class="btn btn-success btn-sm" onclick="return confirm('Save this PMS as final? Every checklist item must be answered. No Good items require remarks and at least one action item.')">Save as Final</button>
            @endif
            <a href="{{ route('pms.index') }}" class="btn btn-secondary btn-sm">Back</a>
            @if(!$isNew && !($canEdit ?? false) && ($canRevertToDraft ?? false))
                <form method="POST" action="{{ route('pms.revert-to-draft', $pms) }}" class="d-inline" onsubmit="return confirm('Revert this PMS to draft? You will be able to edit it again.')">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">Revert to Draft</button>
                </form>
            @endif
            @if(($isNew || $canEdit) && !$isNew && auth()->user()->can('cancel', $pms) && !$pms->isCancelled())
                <form method="POST" action="{{ route('pms.cancel', $pms) }}" class="d-inline" onsubmit="return confirm('Cancel this PMS?')">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                </form>
            @endif
            @if(!$isNew)
                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto d-print-none" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            @endif
        </div>
    </div>
</div>

@if(!$isNew)
    @include('pms.partials.print-sheet', [
        'pms' => $pms,
        'progressStatuses' => $progressStatuses,
    ])
@endif
@endsection
