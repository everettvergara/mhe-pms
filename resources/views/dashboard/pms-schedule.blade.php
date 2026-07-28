@extends('layouts.app')

@section('title', 'PMS Schedule')

@section('content')
<x-page-header title="Upcoming PMS Schedule" :breadcrumbs="['Dashboard' => route('dashboard'), 'PMS Schedule' => null]" />

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Overdue', 'value' => $data['kpis']['overdue'], 'class' => 'text-danger'],
        ['label' => 'Due Today', 'value' => $data['kpis']['due_today'], 'class' => 'text-warning'],
        ['label' => 'Due This Week', 'value' => $data['kpis']['due_this_week'], 'class' => 'text-primary'],
        ['label' => 'Due This Month', 'value' => $data['kpis']['due_this_month'], 'class' => 'text-primary'],
        ['label' => 'Total Scheduled', 'value' => $data['kpis']['total_scheduled'], 'class' => 'text-secondary'],
    ] as $kpi)
        <div class="col-md-4 col-lg">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $kpi['label'] }}</div>
                    <div class="fs-3 fw-bold {{ $kpi['class'] }}">{{ number_format($kpi['value']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Scheduled Preventive Maintenance</strong>
        <span class="text-muted small">Sorted by next schedule date</span>
    </div>
    <div class="card-body p-0">
        @include('dashboard.partials.pms-schedule-table', [
            'records' => $data['records'],
            'showSupplier' => ! $isSupplier,
        ])
    </div>
</div>
@endsection
