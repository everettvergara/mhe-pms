@props(['downtime', 'canManageActionPlans', 'progressStatuses' => []])

@php
    $contextLabel = ($downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? 'Unit').' — '.$downtime->title;
@endphp

<template id="dt-ap-list-{{ $downtime->id }}">
    @if($downtime->actionPlans->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>AP No.</th>
                        <th>Title</th>
                        <th>Responsible</th>
                        <th>Timeline</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($downtime->actionPlans as $plan)
                        @include('mhe-downtimes.partials.downtime-action-list-row', [
                            'plan' => $plan,
                            'downtime' => $downtime,
                            'canManageActionPlans' => $canManageActionPlans,
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted small mb-0">No action items yet.</p>
    @endif
    @if($canManageActionPlans)
        <div class="mt-3">
            <button
                type="button"
                class="btn btn-sm btn-primary"
                data-ap-action="add"
                data-downtime-id="{{ $downtime->id }}"
                data-item-label="{{ $contextLabel }}"
            >
                <i class="bi bi-plus-lg me-1"></i>Add Action Item
            </button>
        </div>
    @endif
</template>

@foreach($downtime->actionPlans as $plan)
    <template id="dt-ap-detail-{{ $plan->id }}">
        @include('mhe-downtimes.partials.downtime-action-detail-panel', [
            'plan' => $plan,
            'downtime' => $downtime,
            'progressStatuses' => $progressStatuses,
            'returnTo' => 'downtime',
        ])
    </template>
@endforeach

@if(request()->filled('action_plan'))
    <div id="action-plan-deeplink" data-plan-id="{{ request('action_plan') }}" hidden></div>
@endif

<div class="modal fade" id="downtimeActionPlanModal" tabindex="-1" aria-labelledby="dt-ap-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" id="dt-ap-modal-title">Action Items</h5>
                    <div class="small text-muted" id="dt-ap-modal-context"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="dt-ap-view-list">
                    <div id="dt-ap-list-container"></div>
                </div>
                <div id="dt-ap-view-form" class="d-none">
                    <button type="button" class="btn btn-link btn-sm ps-0 mb-2" data-ap-action="back-list">
                        <i class="bi bi-arrow-left me-1"></i>Back to list
                    </button>
                    <form
                        id="dt-ap-form"
                        method="POST"
                        action="{{ route('mhe-downtimes.action-plans.store', $downtime) }}"
                        data-store-url="{{ route('mhe-downtimes.action-plans.store', $downtime) }}"
                    >
                        @csrf
                        <input type="hidden" name="return_to" value="downtime">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Title <span class="required-mark">*</span></label>
                                <input type="text" name="title" id="dt-ap-title" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Description <span class="required-mark">*</span></label>
                                <textarea name="description" id="dt-ap-description" class="form-control form-control-sm" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Responsible Person <span class="required-mark">*</span></label>
                                <input type="text" name="responsible_person" id="dt-ap-responsible-person" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Timeline From <span class="required-mark">*</span></label>
                                <input type="date" name="timeline_from" id="dt-ap-timeline-from" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Timeline To <span class="required-mark">*</span></label>
                                <input type="date" name="timeline_to" id="dt-ap-timeline-to" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary" id="dt-ap-form-submit">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="dt-ap-view-detail" class="d-none">
                    <button type="button" class="btn btn-link btn-sm ps-0 mb-2" data-ap-action="back-list">
                        <i class="bi bi-arrow-left me-1"></i>Back to list
                    </button>
                    <div id="dt-ap-detail-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>
