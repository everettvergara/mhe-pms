@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-page-header title="Supplier Dashboard" :breadcrumbs="['Dashboard' => null]" />

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'My Draft PMS', 'value' => $data['kpis']['draft_pms'], 'route' => route('pms.index', ['filters' => ['status' => 'Draft']])],
        ['label' => 'Submitted PMS', 'value' => $data['kpis']['submitted_pms'], 'route' => route('pms.index')],
        ['label' => 'With Findings', 'value' => $data['kpis']['with_findings'], 'route' => route('pms.index', ['filters' => ['status' => 'With Findings']])],
        ['label' => 'Pending Action Plans', 'value' => $data['kpis']['pending_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Pending']])],
        ['label' => 'Waiting Confirmation', 'value' => $data['kpis']['waiting_confirmation'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Waiting for FAST Confirmation']])],
        ['label' => 'Confirmed', 'value' => $data['kpis']['confirmed_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Confirmed']])],
        ['label' => 'Rejected', 'value' => $data['kpis']['rejected_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Rejected']])],
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
            <div class="card-header bg-white"><strong>My Draft Checklists</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>PMS No.</th><th>Site</th><th>Updated</th></tr></thead>
                    <tbody>
                        @forelse($data['draft_checklists'] as $pms)
                            <tr data-href="{{ route('pms.show', $pms) }}">
                                <td><x-pms-no-cell :pms="$pms" /></td>
                                <td>{{ $pms->site?->site_name }}</td>
                                <td>{{ $pms->updated_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No drafts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Action Plans Requiring Attention</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>PMS No.</th><th>Responsible</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($data['action_plans_attention'] as $ap)
                            <tr data-href="{{ route('action-plans.show', $ap) }}">
                                <td><x-pms-no-cell :pms="$ap->pmsDetail?->pmsHeader" /></td>
                                <td>{{ $ap->responsible_person }}</td>
                                <td>{{ $ap->timeline_to?->format('Y-m-d') }}</td>
                                <td><x-status-badge :status="$ap->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">None.</td></tr>
                        @endforelse
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
                'showSupplier' => false,
            ])
        </div>
    </div>
</div>
@endsection
