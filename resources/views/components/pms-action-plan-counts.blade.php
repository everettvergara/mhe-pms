@props(['pms'])

@php
    $total = (int) ($pms->action_plans_count ?? 0);
@endphp

@if($total === 0)
    <span class="text-muted">—</span>
@else
    <div class="d-flex flex-wrap gap-1 align-items-center">
        <span class="badge bg-secondary border-0 fw-semibold">{{ $total }}</span>
        @foreach(\App\Enums\ActionPlanStatus::cases() as $status)
            @php $count = (int) ($pms->{$status->countAttribute()} ?? 0); @endphp
            @if($count > 0)
                <x-status-badge :status="$status" :label="$status->value.' '.$count" class="small" />
            @endif
        @endforeach
    </div>
@endif
