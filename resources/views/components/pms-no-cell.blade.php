@props(['pms'])

@if($pms)
    <div>{{ $pms->pms_no }}</div>
    <div class="text-muted small">{{ $pms->mheType?->code ?? '—' }} · {{ $pms->unit_number ?? '—' }}</div>
@else
    —
@endif
