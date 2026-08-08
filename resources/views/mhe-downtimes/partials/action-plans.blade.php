@if(!($isNew ?? false))
<div class="border-top pt-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Action Items</strong>
        @if($canManageActionPlans ?? false)
            @include('mhe-downtimes.partials.downtime-action-toolbar', [
                'downtime' => $downtime,
                'canManageActionPlans' => $canManageActionPlans,
            ])
        @endif
    </div>
    <p class="text-muted small mb-3">Action items are managed by the supplier after the downtime is posted.</p>

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
                        @if($canManageActionPlans ?? false)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($downtime->actionPlans as $actionPlan)
                        @include('mhe-downtimes.partials.downtime-action-list-row', [
                            'plan' => $actionPlan,
                            'downtime' => $downtime,
                            'canManageActionPlans' => $canManageActionPlans ?? false,
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted small mb-0">No action items yet.</p>
        @if($canManageActionPlans ?? false)
            <button
                type="button"
                class="btn btn-sm btn-primary mt-2"
                data-ap-action="add"
                data-downtime-id="{{ $downtime->id }}"
                data-item-label="{{ ($downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? 'Unit').' — '.$downtime->title }}"
            >
                <i class="bi bi-plus-lg me-1"></i>Add Action Item
            </button>
        @endif
    @endif

    @if($downtime->isPosted())
        @include('mhe-downtimes.partials.downtime-action-modals', [
            'downtime' => $downtime,
            'canManageActionPlans' => $canManageActionPlans ?? false,
            'progressStatuses' => $progressStatuses ?? [],
        ])
    @endif
</div>
@endif
