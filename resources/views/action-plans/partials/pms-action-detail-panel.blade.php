@props(['plan', 'progressStatuses', 'returnTo' => null])

@php
    $canManagePlan = auth()->user()->isSupplier() && auth()->user()->can('update', $plan);
    $canEditFields = $canManagePlan && in_array($plan->status, [\App\Enums\ActionPlanStatus::Pending, \App\Enums\ActionPlanStatus::Rejected], true);
@endphp

<div class="pms-action-detail-panel">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <strong>{{ $plan->action_plan_no }}</strong>
        <x-status-badge :status="$plan->status" />
    </div>

    <dl class="row small mb-3">
        <dt class="col-sm-3">Title</dt><dd class="col-sm-9">{{ $plan->title }}</dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $plan->description }}</dd>
        <dt class="col-sm-3">Responsible</dt><dd class="col-sm-9">{{ $plan->responsible_person }}</dd>
        <dt class="col-sm-3">Timeline</dt><dd class="col-sm-9">{{ $plan->timeline_from?->format('Y-m-d') }} to {{ $plan->timeline_to?->format('Y-m-d') }}</dd>
        @if($plan->rejection_remarks)
            <dt class="col-sm-3">Rejection</dt><dd class="col-sm-9 text-danger">{{ $plan->rejection_remarks }}</dd>
        @endif
    </dl>

    <div class="border-top pt-3">
        <div class="small fw-medium mb-2">Progress Comments</div>
        <ul class="list-unstyled small mb-3">
            @forelse($plan->comments as $comment)
                <li class="mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $comment->creator?->name }}</strong>
                        <x-status-badge :status="$comment->progress_status" />
                    </div>
                    <div class="text-muted">{{ $comment->created_at?->format('Y-m-d H:i') }}</div>
                    <div>{{ $comment->comment }}</div>
                </li>
            @empty
                <li class="text-muted mb-2">No comments yet.</li>
            @endforelse
        </ul>

        @if($canManagePlan)
            <form method="POST" action="{{ route('action-plans.comment', $plan) }}" class="mb-3">
                @csrf
                @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
                <div class="row g-2">
                    <div class="col-md-8">
                        <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Add progress comment..." required></textarea>
                    </div>
                    <div class="col-md-4">
                        <select name="progress_status" class="form-select form-select-sm mb-1" required>
                            @foreach($progressStatuses as $status)
                                <option value="{{ $status->value }}">{{ $status->value }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Add Comment</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="d-flex flex-wrap gap-2">
            @if($canManagePlan && in_array($plan->status, [\App\Enums\ActionPlanStatus::Pending, \App\Enums\ActionPlanStatus::Rejected], true))
                <form method="POST" action="{{ route('action-plans.mark-implemented', $plan) }}" onsubmit="return confirm('Mark as implemented?')">
                    @csrf
                    @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
                    <button type="submit" class="btn btn-sm btn-success">Mark Implemented</button>
                </form>
            @endif
            @if($canManagePlan && $plan->status !== \App\Enums\ActionPlanStatus::Cancelled && $plan->status !== \App\Enums\ActionPlanStatus::Confirmed)
                <form method="POST" action="{{ route('action-plans.cancel', $plan) }}" onsubmit="return confirm('Cancel this action plan?')">
                    @csrf
                    @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                </form>
            @endif
        </div>
    </div>
</div>
