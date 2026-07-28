@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-page-header title="Administrator Dashboard" :breadcrumbs="['Dashboard' => null]" />

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Total PMS', 'value' => $data['kpis']['total_pms'], 'route' => route('pms.index')],
        ['label' => 'Draft PMS', 'value' => $data['kpis']['draft_pms'], 'route' => route('pms.index', ['filters' => ['status' => 'Draft']])],
        ['label' => 'No Findings', 'value' => $data['kpis']['no_findings'], 'route' => route('pms.index', ['filters' => ['status' => 'No Findings']])],
        ['label' => 'With Findings', 'value' => $data['kpis']['with_findings'], 'route' => route('pms.index', ['filters' => ['status' => 'With Findings']])],
        ['label' => 'Pending Action Plans', 'value' => $data['kpis']['pending_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Pending']])],
        ['label' => 'Waiting Confirmation', 'value' => $data['kpis']['waiting_confirmation'], 'route' => route('action-plan-confirmations.index')],
        ['label' => 'Confirmed', 'value' => $data['kpis']['confirmed_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Confirmed']])],
        ['label' => 'Rejected', 'value' => $data['kpis']['rejected_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Rejected']])],
        ['label' => 'Cancelled PMS', 'value' => $data['kpis']['cancelled_pms'], 'route' => route('pms.index', ['filters' => ['status' => 'Cancelled']])],
    ] as $kpi)
        <div class="col-md-4 col-lg-3">
            <a href="{{ $kpi['route'] }}" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $kpi['label'] }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($kpi['value']) }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Pending FAST Confirmations</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>PMS No.</th><th>Supplier</th><th>Action Plan</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($data['pending_confirmations'] as $item)
                            <tr data-href="{{ route('action-plan-confirmations.show', $item) }}">
                                <td><x-pms-no-cell :pms="$item->pmsDetail?->pmsHeader" /></td>
                                <td><x-supplier-cell :supplier="$item->pmsDetail?->pmsHeader?->supplier" /></td>
                                <td>
                                    <div>{{ $item->action_plan_no }}</div>
                                    <div class="text-muted small">{{ $item->title }}</div>
                                </td>
                                <td><x-status-badge :status="$item->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No pending confirmations.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Pending Checklists (With Findings)</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>PMS No.</th><th>Supplier</th><th>Site</th><th>Submitted</th></tr></thead>
                    <tbody>
                        @forelse($data['pending_checklists'] as $pms)
                            <tr data-href="{{ route('pms.show', $pms) }}">
                                <td><x-pms-no-cell :pms="$pms" /></td>
                                <td><x-supplier-cell :supplier="$pms->supplier" /></td>
                                <td>{{ $pms->site?->site_name }}</td>
                                <td>{{ $pms->submitted_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No pending checklists.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Recent PMS</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>PMS No.</th><th>Supplier</th><th>Site</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($data['recent_pms'] as $pms)
                            <tr data-href="{{ route('pms.show', $pms) }}">
                                <td><x-pms-no-cell :pms="$pms" /></td>
                                <td><x-supplier-cell :supplier="$pms->supplier" /></td>
                                <td>{{ $pms->site?->site_name }}</td>
                                <td><x-status-badge :status="$pms->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Upcoming PMS Schedule</strong>
                <a href="{{ route('dashboard.pms-schedule') }}" class="small">View all</a>
            </div>
            @include('dashboard.partials.pms-schedule-table', [
                'records' => $data['upcoming_pms_schedule'],
                'showSupplier' => true,
            ])
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Recent Activities</strong></div>
            <ul class="list-group list-group-flush">
                @foreach($data['recent_activities'] as $log)
                    <li class="list-group-item small">
                        <strong>{{ $log->user?->name ?? 'System' }}</strong> — {{ $log->description }}
                        <div class="text-muted">{{ $log->created_at?->diffForHumans() }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
