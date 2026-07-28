@props(['status', 'label' => null])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $display = $label ?? $value;
    $class = match($value) {
        'Good' => 'bg-success',
        'No Good' => 'bg-danger',
        'Draft' => 'bg-secondary',
        'With Findings' => 'bg-warning text-dark',
        'No Findings' => 'bg-success',
        'Cancelled' => 'bg-dark',
        'Pending' => 'bg-secondary',
        'Waiting for FAST Confirmation' => 'bg-info text-dark',
        'Confirmed' => 'bg-success',
        'Rejected' => 'bg-danger',
        'Active' => 'bg-success',
        'Inactive' => 'bg-secondary',
        default => 'bg-primary',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>{{ $display }}</span>
