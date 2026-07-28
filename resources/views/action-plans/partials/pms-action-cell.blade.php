@props(['detail', 'canManageActionPlans', 'forPrint' => false])

<div class="pms-action-cell">
    @if($detail->actionPlans->isNotEmpty())
        <div class="mb-1">
            @foreach($detail->actionPlans as $plan)
                <div class="ap-inline-item">
                    <span class="fw-medium">{{ $plan->action_plan_no }}</span>
                    <x-status-badge :status="$plan->status" />
                </div>
            @endforeach
        </div>
    @elseif($forPrint)
        <span class="text-muted small">—</span>
    @endif

    @unless($forPrint)
        @include('action-plans.partials.pms-action-toolbar', [
            'detail' => $detail,
            'canManageActionPlans' => $canManageActionPlans,
        ])
    @endunless
</div>
