@props(['pms', 'sortedDetails', 'canManageActionPlans', 'pmsIsDraft', 'progressStatuses' => []])

@foreach($sortedDetails as $detail)
    <template id="ap-list-{{ $detail->id }}">
        @if($detail->actionPlans->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>AP No.</th>
                            <th>Title</th>
                            <th>Responsible</th>
                            <th>Timeline</th>
                            <th>Status</th>
                            <th title="I guarantee that the unit is safe to use">Unit safe</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detail->actionPlans as $plan)
                            @include('action-plans.partials.pms-action-list-row', [
                                'plan' => $plan,
                                'pmsDetail' => $detail,
                                'pmsIsDraft' => $pmsIsDraft,
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
                    data-pms-detail-id="{{ $detail->id }}"
                    data-item-label="{{ $detail->checklistItem?->description ?? '' }}"
                >
                    <i class="bi bi-plus-lg me-1"></i>Add Action Item
                </button>
            </div>
        @endif
    </template>
@endforeach

@foreach($sortedDetails as $detail)
    @foreach($detail->actionPlans as $plan)
        <template id="ap-detail-{{ $plan->id }}">
            @include('action-plans.partials.pms-action-detail-panel', [
                'plan' => $plan,
                'progressStatuses' => $progressStatuses,
                'returnTo' => 'pms',
            ])
        </template>
    @endforeach
@endforeach

@if(request()->filled('action_plan'))
    <div id="action-plan-deeplink" data-plan-id="{{ request('action_plan') }}" hidden></div>
@endif

<div class="modal fade" id="pmsActionPlanModal" tabindex="-1" aria-labelledby="ap-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" id="ap-modal-title">Action Items</h5>
                    <div class="small text-muted" id="ap-modal-context"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ap-view-list">
                    <div id="ap-list-container"></div>
                </div>
                <div id="ap-view-form" class="d-none">
                    <button type="button" class="btn btn-link btn-sm ps-0 mb-2" data-ap-action="back-list">
                        <i class="bi bi-arrow-left me-1"></i>Back to list
                    </button>
                    <form
                        id="ap-form"
                        method="POST"
                        action="{{ route('action-plans.store') }}"
                        data-store-url="{{ route('action-plans.store') }}"
                    >
                        @csrf
                        <input type="hidden" name="return_to" value="pms">
                        <input type="hidden" name="pms_detail_id" id="ap-pms-detail-id" value="">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Title <span class="required-mark">*</span></label>
                                <input type="text" name="title" id="ap-title" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Description <span class="required-mark">*</span></label>
                                <textarea name="description" id="ap-description" class="form-control form-control-sm" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Responsible Person <span class="required-mark">*</span></label>
                                <input type="text" name="responsible_person" id="ap-responsible-person" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Timeline From <span class="required-mark">*</span></label>
                                <input type="date" name="timeline_from" id="ap-timeline-from" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Timeline To <span class="required-mark">*</span></label>
                                <input type="date" name="timeline_to" id="ap-timeline-to" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary" id="ap-form-submit">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="ap-view-detail" class="d-none">
                    <button type="button" class="btn btn-link btn-sm ps-0 mb-2" data-ap-action="back-list">
                        <i class="bi bi-arrow-left me-1"></i>Back to list
                    </button>
                    <div id="ap-detail-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>
