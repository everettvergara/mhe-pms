@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $pmsDone = (int) $data['kpis']['pms_month_done'];
    $pmsTotal = (int) $data['kpis']['pms_month_total'];
    $pmsPct = $pmsTotal > 0 ? (int) round($pmsDone / $pmsTotal * 100) : 0;
@endphp

<x-page-header title="Supplier Dashboard" :breadcrumbs="['Dashboard' => null]" />

@include('dashboard.partials.quick-shortcuts')

<div class="row g-4 admin-dash">
    <div class="col-lg-6">
        <section class="admin-dash-col admin-dash-col--pms h-100">
            <header class="admin-dash-col__title">
                <i class="bi bi-clipboard2-check"></i>
                PMS
            </header>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <a href="{{ route('dashboard.units') }}" class="admin-stat admin-stat--pms">
                        <span class="admin-stat__icon"><i class="bi bi-calendar2-check"></i></span>
                        <span class="admin-stat__label">PMS for the month</span>
                        <span class="admin-stat__month">{{ now()->format('F Y') }}</span>
                        <span class="admin-stat__value">{{ $pmsDone }}/{{ $pmsTotal }}</span>
                        <span class="admin-stat__meter" role="progressbar" aria-valuenow="{{ $pmsPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="PMS completed this month">
                            <span style="width: {{ $pmsPct }}%"></span>
                        </span>
                    </a>
                </div>
                <div class="col-sm-6">
                    <a href="#supplier-pms-waiting" class="admin-stat admin-stat--wait">
                        <span class="admin-stat__icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="admin-stat__label">Waiting for implementation</span>
                        <span class="admin-stat__value">{{ number_format($data['kpis']['pms_waiting_implementation']) }}</span>
                    </a>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--pms mb-3" id="supplier-pms-waiting">
                <div class="card-header bg-white"><strong>Waiting for my implementation</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Site</th>
                                <th>Action plan</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['pms_waiting_implementation'] as $plan)
                                <tr @if($plan->parentShowUrl()) data-href="{{ $plan->parentShowUrl() }}" @endif>
                                    <td>{{ $plan->pmsDetail?->pmsHeader?->unit_number ?? '—' }}</td>
                                    <td>{{ $plan->pmsDetail?->pmsHeader?->site?->site_name ?? '—' }}</td>
                                    <td>
                                        <div>{{ $plan->action_plan_no }}</div>
                                        <div class="text-muted small">{{ $plan->title }}</div>
                                    </td>
                                    <td>{{ $plan->timeline_to?->format('Y-m-d') ?? '—' }}</td>
                                    <td><x-status-badge :status="$plan->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">None.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--pms mb-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Units for my sites</strong>
                    <a href="{{ route('dashboard.units') }}" class="admin-dash-show-all">Show all</a>
                </div>
                @include('dashboard.partials.supplier-units-table', ['units' => $data['units']])
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="admin-dash-col admin-dash-col--down h-100">
            <header class="admin-dash-col__title">
                <i class="bi bi-wrench-adjustable"></i>
                Downtime
            </header>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <a href="{{ route('mhe-downtimes.index', ['filters' => ['currently_down' => 1]]) }}" class="admin-stat admin-stat--down">
                        <span class="admin-stat__icon"><i class="bi bi-exclamation-octagon"></i></span>
                        <span class="admin-stat__label">Currently down units</span>
                        <span class="admin-stat__value">{{ number_format($data['kpis']['currently_down_units']) }}</span>
                    </a>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('mhe-downtimes.index') }}" class="admin-stat admin-stat--dt-wait">
                        <span class="admin-stat__icon"><i class="bi bi-inbox"></i></span>
                        <span class="admin-stat__label">Waiting for my implementation</span>
                        <span class="admin-stat__value">{{ number_format($data['kpis']['downtime_waiting_implementation']) }}</span>
                    </a>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--down mb-0">
                <div class="card-header bg-white"><strong>Waiting for my implementation</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Site</th>
                                <th>Title</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['downtime_waiting_implementation'] as $plan)
                                <tr @if($plan->parentShowUrl()) data-href="{{ $plan->parentShowUrl() }}" @endif>
                                    <td>{{ $plan->mheDowntime?->mheInventory?->unit_no ?? $plan->mheDowntime?->ref_unit_no ?? '—' }}</td>
                                    <td>{{ $plan->mheDowntime?->site?->site_name ?? '—' }}</td>
                                    <td>{{ $plan->title }}</td>
                                    <td>{{ $plan->timeline_to?->format('Y-m-d') ?? '—' }}</td>
                                    <td><x-status-badge :status="$plan->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">None.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
