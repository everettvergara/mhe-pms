@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-page-header title="Supplier Dashboard" :breadcrumbs="['Dashboard' => null]" />

@if(($data['kpis']['downtimes_needing_action_plan'] ?? 0) > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        You have {{ number_format($data['kpis']['downtimes_needing_action_plan']) }} posted downtime(s) that need an action plan.
        <a href="{{ route('mhe-downtimes.index', ['filters' => ['needs_action_plan' => 1]]) }}" class="alert-link">View list</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'My Draft PMS', 'value' => $data['kpis']['draft_pms'], 'route' => route('pms.index', ['filters' => ['status' => 'Draft']])],
        ['label' => 'Submitted PMS', 'value' => $data['kpis']['submitted_pms'], 'route' => route('pms.index')],
        ['label' => 'With Findings', 'value' => $data['kpis']['with_findings'], 'route' => route('pms.index', ['filters' => ['status' => 'With Findings']])],
        ['label' => 'Pending Action Plans', 'value' => $data['kpis']['pending_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Pending']])],
        ['label' => 'Waiting Confirmation', 'value' => $data['kpis']['waiting_confirmation'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Waiting for FAST Confirmation']])],
        ['label' => 'Confirmed', 'value' => $data['kpis']['confirmed_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Confirmed']])],
        ['label' => 'Rejected', 'value' => $data['kpis']['rejected_action_plans'], 'route' => route('action-plans.index', ['filters' => ['status' => 'Rejected']])],
        ['label' => 'Downtimes Needing Action Plan', 'value' => $data['kpis']['downtimes_needing_action_plan'], 'route' => route('mhe-downtimes.index', ['filters' => ['needs_action_plan' => 1]])],
        ['label' => 'DT Pending Action Items', 'value' => $data['kpis']['downtime_pending_action_plans'], 'route' => route('mhe-downtimes.index')],
        ['label' => 'DT Waiting Confirmation', 'value' => $data['kpis']['downtime_waiting_confirmation'], 'route' => route('mhe-downtimes.index')],
        ['label' => 'DT Confirmed', 'value' => $data['kpis']['downtime_confirmed_action_plans'], 'route' => route('mhe-downtimes.index')],
        ['label' => 'DT Rejected', 'value' => $data['kpis']['downtime_rejected_action_plans'], 'route' => route('mhe-downtimes.index')],
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
            <div class="card-header bg-white"><strong>Downtime Action Items Requiring Attention</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>AP No.</th><th>Downtime</th><th>Title</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($data['downtime_action_plans_attention'] as $ap)
                            <tr data-href="{{ route('mhe-downtimes.show', $ap->mheDowntime) }}">
                                <td>{{ $ap->action_plan_no }}</td>
                                <td>#{{ $ap->mheDowntime?->id }}</td>
                                <td>{{ $ap->title }}</td>
                                <td>{{ $ap->timeline_to?->format('Y-m-d') }}</td>
                                <td><x-status-badge :status="$ap->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Downtime Action Items Waiting Confirmation</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>AP No.</th><th>Downtime</th><th>Title</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($data['downtime_waiting_confirmation'] as $ap)
                            <tr data-href="{{ route('mhe-downtimes.show', $ap->mheDowntime) }}">
                                <td>{{ $ap->action_plan_no }}</td>
                                <td>#{{ $ap->mheDowntime?->id }}</td>
                                <td>{{ $ap->title }}</td>
                                <td>{{ $ap->timeline_to?->format('Y-m-d') }}</td>
                                <td><x-status-badge :status="$ap->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Downtimes Needing Action Plan</strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>Site</th><th>Unit No.</th><th>Title</th><th>Posted</th></tr></thead>
                    <tbody>
                        @forelse($data['downtimes_needing_action_plan'] as $downtime)
                            <tr data-href="{{ route('mhe-downtimes.show', $downtime) }}">
                                <td>{{ $downtime->site?->site_name }}</td>
                                <td>{{ $downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? '—' }}</td>
                                <td>{{ $downtime->title }}</td>
                                <td>{{ $downtime->posted_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No downtimes need action plans.</td></tr>
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
