@props(['plan', 'progressStatuses', 'returnTo' => 'downtime', 'downtime' => null])

@php
    $downtime = $downtime ?? $plan->mheDowntime;
    $canManagePlan = auth()->user()->isSupplier() && auth()->user()->can('update', $plan);
    $canEditFields = $canManagePlan && in_array($plan->status, [\App\Enums\DowntimeActionPlanStatus::Pending, \App\Enums\DowntimeActionPlanStatus::Rejected], true);
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
        @unless($canEditFields)
            <dt class="col-sm-3">Unit safe</dt>
            <dd class="col-sm-9">
                <x-unit-safe-checkbox :checked="$plan->unit_safe_guaranteed" />
                <span class="ms-1">I guarantee that the unit is safe to use</span>
            </dd>
        @endunless
        @if($plan->rejection_remarks)
            <dt class="col-sm-3">Rejection</dt><dd class="col-sm-9 text-danger">{{ $plan->rejection_remarks }}</dd>
        @endif
    </dl>

    @if($plan->attachments->isNotEmpty())
        <div class="mb-3">
            <div class="small fw-medium mb-2">Photos</div>
            <x-attachment-thumbnails :attachments="$plan->attachments" />
        </div>
    @endif

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
            <form method="POST" action="{{ route('mhe-downtimes.action-plans.comment', [$downtime, $plan]) }}" class="mb-3">
                @csrf
                @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
                <div class="row g-2">
                    <div class="col-md-8">
                        <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Add progress comment..." required></textarea>
                    </div>
                    <div class="col-md-4">
                        <select name="progress_status" class="form-select form-select-sm" data-progress-status required>
                            @foreach($progressStatuses as $status)
                                <option value="{{ $status->value }}">{{ $status->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($canEditFields)
                        <div class="col-12">
                            <label class="form-check-label d-flex align-items-start gap-2 mb-0">
                                <input
                                    type="checkbox"
                                    name="unit_safe_guaranteed"
                                    value="1"
                                    class="form-check-input mt-1"
                                    data-unit-safe-checkbox
                                    disabled
                                >
                                <span>I guarantee that the unit is safe to use</span>
                            </label>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Add Comment</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="d-flex flex-wrap gap-2">
            @if($canManagePlan && $plan->status !== \App\Enums\DowntimeActionPlanStatus::Cancelled && $plan->status !== \App\Enums\DowntimeActionPlanStatus::Confirmed)
                <form method="POST" action="{{ route('mhe-downtimes.action-plans.cancel', [$downtime, $plan]) }}" onsubmit="return confirm('Cancel this action item?')">
                    @csrf
                    @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                </form>
            @endif
        </div>

        @if(auth()->user()->can('confirm', $plan) && $plan->status === \App\Enums\DowntimeActionPlanStatus::WaitingForFastConfirmation)
            <div class="d-flex flex-wrap gap-2 mt-3 align-items-end">
                <form method="POST" action="{{ route('mhe-downtime-action-plan-confirmations.confirm', $plan) }}" class="d-flex flex-wrap gap-2 align-items-end" onsubmit="return confirm('Confirm this action item?')">
                    @csrf
                    <div>
                        <label class="form-label mb-0 small" for="date_implemented_{{ $plan->id }}">Implemented at</label>
                        <input type="datetime-local"
                               name="date_implemented"
                               id="date_implemented_{{ $plan->id }}"
                               class="form-control form-control-sm @error('date_implemented') is-invalid @enderror"
                               value="{{ old('date_implemented', $plan->date_implemented?->format('Y-m-d\TH:i')) }}"
                               required>
                        @error('date_implemented')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-sm btn-success">Confirm</button>
                </form>
                <form method="POST" action="{{ route('mhe-downtime-action-plan-confirmations.reject', $plan) }}" class="flex-grow-1" style="max-width:500px" onsubmit="return confirm('Reject this action item?')">
                    @csrf
                    <div class="input-group">
                        <textarea name="rejection_remarks" class="form-control form-control-sm" rows="1" placeholder="Rejection remarks (required)" required></textarea>
                        <button class="btn btn-sm btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
