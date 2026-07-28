@props(['plan', 'progressStatuses'])

@php
    $canManagePlan = auth()->user()->isSupplier() && auth()->user()->can('update', $plan);
    $canEditFields = $canManagePlan && in_array($plan->status, [\App\Enums\ActionPlanStatus::Pending, \App\Enums\ActionPlanStatus::Rejected], true);
@endphp

<div class="border rounded p-3 mb-2 bg-white">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <strong>{{ $plan->action_plan_no }}</strong>
        <x-status-badge :status="$plan->status"/>
    </div>

    @if($canEditFields)
        <form method="POST" action="{{ route('action-plans.update', $plan) }}" class="mb-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="pms">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Title <span class="required-mark">*</span></label>
                    <input name="title" class="form-control form-control-sm" value="{{ old('title', $plan->title) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Responsible Person <span class="required-mark">*</span></label>
                    <input name="responsible_person" class="form-control form-control-sm" value="{{ old('responsible_person', $plan->responsible_person) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label small">Description <span class="required-mark">*</span></label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" required>{{ old('description', $plan->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Timeline From <span class="required-mark">*</span></label>
                    <input type="date" name="timeline_from" class="form-control form-control-sm" value="{{ old('timeline_from', $plan->timeline_from?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Timeline To <span class="required-mark">*</span></label>
                    <input type="date" name="timeline_to" class="form-control form-control-sm" value="{{ old('timeline_to', $plan->timeline_to?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">Update Action Plan</button>
                </div>
            </div>
        </form>
    @else
        <dl class="row small mb-2">
            <dt class="col-sm-3">Title</dt><dd class="col-sm-9">{{ $plan->title }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $plan->description }}</dd>
            <dt class="col-sm-3">Responsible</dt><dd class="col-sm-9">{{ $plan->responsible_person }}</dd>
            <dt class="col-sm-3">Timeline</dt><dd class="col-sm-9">{{ $plan->timeline_from?->format('Y-m-d') }} to {{ $plan->timeline_to?->format('Y-m-d') }}</dd>
            @if($plan->rejection_remarks)
                <dt class="col-sm-3">Rejection</dt><dd class="col-sm-9 text-danger">{{ $plan->rejection_remarks }}</dd>
            @endif
        </dl>
    @endif

    <div class="border-top pt-2 mt-2">
        <div class="small fw-medium mb-1">Progress Comments</div>
        <ul class="list-unstyled small mb-2">
            @forelse($plan->comments as $comment)
                <li class="mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $comment->creator?->name }}</strong>
                        <x-status-badge :status="$comment->progress_status"/>
                    </div>
                    <div class="text-muted">{{ $comment->created_at?->format('Y-m-d H:i') }}</div>
                    <div>{{ $comment->comment }}</div>
                </li>
            @empty
                <li class="text-muted mb-2">No comments yet.</li>
            @endforelse
        </ul>

        @if($canManagePlan)
            <form method="POST" action="{{ route('action-plans.comment', $plan) }}" class="mb-2">
                @csrf
                <input type="hidden" name="return_to" value="pms">
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
                    <input type="hidden" name="return_to" value="pms">
                    <button type="submit" class="btn btn-sm btn-success">Mark Implemented</button>
                </form>
            @endif
            @if($canManagePlan && $plan->status !== \App\Enums\ActionPlanStatus::Cancelled && $plan->status !== \App\Enums\ActionPlanStatus::Confirmed)
                <form method="POST" action="{{ route('action-plans.cancel', $plan) }}" onsubmit="return confirm('Cancel this action plan?')">
                    @csrf
                    <input type="hidden" name="return_to" value="pms">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                </form>
            @endif
        </div>
    </div>
</div>
