@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $pmsDone = (int) $data['pms_month_done'];
    $pmsTotal = (int) $data['pms_month_total'];
    $pmsPct = $pmsTotal > 0 ? (int) round($pmsDone / $pmsTotal * 100) : 0;
@endphp

<x-page-header title="Administrator Dashboard" :breadcrumbs="['Dashboard' => null]" />

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
                    <a href="{{ route('dashboard.pms-schedule') }}" class="admin-stat admin-stat--pms">
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
                    <a href="#admin-pms-confirmations" class="admin-stat admin-stat--wait">
                        <span class="admin-stat__icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="admin-stat__label">Waiting for my confirmation</span>
                        <span class="admin-stat__value">{{ number_format($data['waiting_confirmation']) }}</span>
                    </a>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--pms mb-3" id="admin-pms-confirmations">
                <div class="card-header bg-white"><strong>Pending FAST Confirmations</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>PMS No.</th><th>Supplier</th><th>Action Plan</th><th>Status</th><th title="I guarantee that the unit is safe to use">Unit safe</th></tr></thead>
                        <tbody>
                            @forelse($data['pending_confirmations'] as $item)
                                <tr @if($item->parentShowUrl()) data-href="{{ $item->parentShowUrl() }}" @endif>
                                    <td><x-pms-no-cell :pms="$item->pmsDetail?->pmsHeader" /></td>
                                    <td><x-supplier-cell :supplier="$item->pmsDetail?->pmsHeader?->supplier" /></td>
                                    <td>
                                        <div>{{ $item->action_plan_no }}</div>
                                        <div class="text-muted small">{{ $item->title }}</div>
                                    </td>
                                    <td><x-status-badge :status="$item->status" /></td>
                                    <td><x-unit-safe-checkbox :checked="$item->unit_safe_guaranteed" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No pending confirmations.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--pms mb-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Units in my sites</strong>
                    <a href="{{ route('dashboard.pms-schedule') }}" class="admin-dash-show-all">Show all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>Site</th><th>Supplier</th><th>Unit</th><th>PMS</th></tr></thead>
                        <tbody>
                            @forelse($data['site_units'] as $unit)
                                <tr data-href="{{ route('mhe-inventories.show', $unit) }}">
                                    <td>{{ $unit->siteRelation?->site_name ?? $unit->site ?? '—' }}</td>
                                    <td><x-supplier-cell :supplier="$unit->supplier" /></td>
                                    <td>{{ $unit->unit_no ?? '—' }}</td>
                                    <td>
                                        @if($unit->pms_this_month)
                                            <span class="badge rounded-pill admin-pms-yes">Yes</span>
                                        @else
                                            <span class="badge rounded-pill admin-pms-no">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No units in your sites.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                        <span class="admin-stat__value">{{ number_format($data['currently_down_units']) }}</span>
                    </a>
                </div>
                <div class="col-sm-6">
                    <a href="#admin-downtime-confirmations" class="admin-stat admin-stat--dt-wait">
                        <span class="admin-stat__icon"><i class="bi bi-inbox"></i></span>
                        <span class="admin-stat__label">Waiting for my confirmation</span>
                        <span class="admin-stat__value">{{ number_format($data['downtime_waiting_confirmation']) }}</span>
                    </a>
                </div>
            </div>

            <div class="card admin-dash-card admin-dash-card--down mb-0" id="admin-downtime-confirmations">
                <div class="card-header bg-white"><strong>Pending FAST Confirmations</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>AP No.</th><th>Downtime</th><th>Supplier</th><th>Title</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($data['downtime_pending_confirmations'] as $item)
                                <tr @if($item->parentShowUrl()) data-href="{{ $item->parentShowUrl() }}" @endif>
                                    <td>{{ $item->action_plan_no }}</td>
                                    <td>#{{ $item->mheDowntime?->id }}</td>
                                    <td>{{ $item->mheDowntime?->supplier?->supplier_name }}</td>
                                    <td>{{ $item->title }}</td>
                                    <td><x-status-badge :status="$item->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No pending downtime confirmations.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
