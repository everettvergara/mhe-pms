@extends('layouts.app')
@section('title', 'MHE Downtime Summary')

@section('content')
<x-page-header title="MHE Downtime Summary" :breadcrumbs="['MHE Reports' => null, 'Downtime Summary' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="summary-filter" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">District</label>
                <select name="district_id" id="district_id" data-controls-site="site_id" class="form-select form-select-sm">
                    <option value="">All Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Site</label>
                <select name="site_id" id="site_id" class="form-select form-select-sm">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" data-district-id="{{ $site->district_id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">As of Date</label>
                <input type="date" name="as_of_date" id="as_of_date" class="form-control form-control-sm" value="{{ $filters['as_of_date'] }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Search</button>
            </div>
            <input type="hidden" name="ap_date_from" value="{{ $actionPlanFilters['date_from'] }}">
            <input type="hidden" name="ap_date_to" value="{{ $actionPlanFilters['date_to'] }}">
            <input type="hidden" name="ap_district_id" value="{{ $actionPlanFilters['district_id'] }}">
            <input type="hidden" name="ap_site_id" value="{{ $actionPlanFilters['site_id'] }}">
            <input type="hidden" name="ap_is_pending" value="{{ $actionPlanFilters['is_pending'] ? '1' : '0' }}">
            <input type="hidden" name="ap_is_implemented" value="{{ $actionPlanFilters['is_implemented'] ? '1' : '0' }}">
            <input type="hidden" name="ap_is_no_action_plan" value="{{ $actionPlanFilters['is_no_action_plan'] ? '1' : '0' }}">
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['id' => 'dailyChart', 'title' => 'Daily', 'data' => $charts['daily'], 'accent' => '#0f766e'],
        ['id' => 'monthlyChart', 'title' => 'Monthly', 'data' => $charts['monthly'], 'accent' => '#c2410c'],
        ['id' => 'quarterlyChart', 'title' => 'Quarterly', 'data' => $charts['quarterly'], 'accent' => '#6d28d9'],
        ['id' => 'yearlyChart', 'title' => 'Yearly', 'data' => $charts['yearly'], 'accent' => '#005BAC'],
    ] as $chart)
        <div class="col-lg-6 col-xl-3">
            <div class="card h-100 summary-chart-card" style="--chart-accent: {{ $chart['accent'] }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="summary-chart-kicker">{{ $chart['title'] }}</div>
                            <div class="summary-chart-total">{{ number_format(collect($chart['data'])->sum(fn ($row) => $row[1])) }}</div>
                        </div>
                        <span class="summary-chart-dot" aria-hidden="true"></span>
                    </div>
                    <div class="summary-chart-canvas">
                        <canvas id="{{ $chart['id'] }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Action Plan Filters</h6>
        <form method="GET" action="{{ route('mhes.summary') }}" id="action-plan-filter" class="row g-2 align-items-end">
            <input type="hidden" name="as_of_date" value="{{ $filters['as_of_date'] }}">
            <input type="hidden" name="district_id" value="{{ $filters['district_id'] }}">
            <input type="hidden" name="site_id" value="{{ $filters['site_id'] }}">
            <div class="col-md-3">
                <label class="form-label small mb-1" for="action_plan_district_id">District</label>
                <select name="ap_district_id" id="action_plan_district_id" data-controls-site="action_plan_site_id" class="form-select form-select-sm">
                    <option value="">All Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($actionPlanFilters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="action_plan_site_id">Site</label>
                <select name="ap_site_id" id="action_plan_site_id" class="form-select form-select-sm">
                    <option value="">All Sites</option>
                    @foreach($actionPlanSites as $site)
                        <option value="{{ $site->id }}" data-district-id="{{ $site->district_id }}" @selected(($actionPlanFilters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1" for="action_plan_date_from">From</label>
                <input type="date" name="ap_date_from" id="action_plan_date_from" class="form-control form-control-sm" value="{{ $actionPlanFilters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1" for="action_plan_date_to">To</label>
                <input type="date" name="ap_date_to" id="action_plan_date_to" class="form-control form-control-sm" value="{{ $actionPlanFilters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Retrieve</button>
            </div>
            <div class="col-12">
                <div class="row g-2">
                    <div class="col-auto form-check">
                        <input type="hidden" name="ap_is_pending" value="0">
                        <input class="form-check-input" type="checkbox" name="ap_is_pending" id="is_pending" value="1" @checked($actionPlanFilters['is_pending'])>
                        <label class="form-check-label" for="is_pending">Pending</label>
                    </div>
                    <div class="col-auto form-check">
                        <input type="hidden" name="ap_is_implemented" value="0">
                        <input class="form-check-input" type="checkbox" name="ap_is_implemented" id="is_implemented" value="1" @checked($actionPlanFilters['is_implemented'])>
                        <label class="form-check-label" for="is_implemented">Implemented</label>
                    </div>
                    <div class="col-auto form-check">
                        <input type="hidden" name="ap_is_no_action_plan" value="0">
                        <input class="form-check-input" type="checkbox" name="ap_is_no_action_plan" id="is_no_action_plan" value="1" @checked($actionPlanFilters['is_no_action_plan'])>
                        <label class="form-check-label" for="is_no_action_plan">No Action Plan</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@include('mhe-reports.partials.action-plans', ['groups' => $actionPlanGroups, 'filters' => $actionPlanFilters])
@endsection

@push('styles')
<style>
    .summary-chart-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .summary-chart-card::before {
        content: '';
        display: block;
        height: 4px;
        background: linear-gradient(90deg, color-mix(in srgb, var(--chart-accent) 35%, white), var(--chart-accent));
    }

    .summary-chart-kicker {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .summary-chart-total {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1.1;
        color: #0f172a;
    }

    .summary-chart-dot {
        width: 0.7rem;
        height: 0.7rem;
        margin-top: 0.2rem;
        border-radius: 999px;
        background: var(--chart-accent);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--chart-accent) 18%, white);
    }

    .summary-chart-canvas {
        position: relative;
        height: 190px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.Chart) {
        const charts = @json($charts);
        const themes = {
            dailyChart: { from: '#99f6e4', to: '#0f766e' },
            monthlyChart: { from: '#fdba74', to: '#c2410c' },
            quarterlyChart: { from: '#c4b5fd', to: '#6d28d9' },
            yearlyChart: { from: '#7dd3fc', to: '#005BAC' },
        };

        const hexToRgb = (hex) => {
            const value = hex.replace('#', '');

            return [
                parseInt(value.slice(0, 2), 16),
                parseInt(value.slice(2, 4), 16),
                parseInt(value.slice(4, 6), 16),
            ];
        };

        const mix = (from, to, amount) => from.map((channel, index) => Math.round(channel + (to[index] - channel) * amount));

        const rgba = (rgb, alpha) => `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${alpha})`;

        Object.entries(themes).forEach(([id, theme]) => {
            const rows = charts[id.replace('Chart', '')];
            const canvas = document.getElementById(id);
            const from = hexToRgb(theme.from);
            const to = hexToRgb(theme.to);
            const colors = rows.map((_, index) => mix(from, to, rows.length === 1 ? 1 : (index + 1) / rows.length));

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: rows.map(row => row[0]),
                    datasets: [{
                        label: 'Incidents',
                        data: rows.map(row => row[1]),
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const color = colors[dataIndex];
                            const { chartArea, ctx } = chart;

                            if (!chartArea) {
                                return rgba(color, 1);
                            }

                            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                            gradient.addColorStop(0, rgba(color, 0.28));
                            gradient.addColorStop(1, rgba(color, 0.95));

                            return gradient;
                        },
                        hoverBackgroundColor: colors.map(color => rgba(color, 1)),
                        borderRadius: 10,
                        borderSkipped: false,
                        maxBarThickness: 36,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Inter, sans-serif', weight: '600' },
                            bodyFont: { family: 'Inter, sans-serif' },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter, sans-serif', size: 11 },
                                maxRotation: 0,
                                autoSkip: true,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.28)' },
                            border: { display: false },
                            ticks: {
                                color: '#94a3b8',
                                precision: 0,
                                font: { family: 'Inter, sans-serif', size: 11 },
                            },
                        },
                    },
                },
            });
        });
    }

});
</script>
@endpush
