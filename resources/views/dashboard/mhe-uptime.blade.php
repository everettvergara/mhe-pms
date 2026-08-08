@extends('layouts.app')
@section('title', 'MHE Uptime')

@section('content')
@php
    $kpis = $data['kpis'];
    $uptimeClass = $kpis['uptime_pct'] >= $kpis['target'] ? 'text-success' : ($kpis['uptime_pct'] >= $kpis['target'] - 5 ? 'text-warning' : 'text-danger');
    $grain = $filters['grain'] ?? 'monthly';
@endphp

<x-page-header title="MHE Uptime" :breadcrumbs="['Dashboard' => route('dashboard'), 'MHE Uptime' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">District</label>
                <select name="district_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Site</label>
                <select name="site_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">MHE Type</label>
                <select name="mhe_type_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($mheTypes as $type)
                        <option value="{{ $type->id }}" @selected(($filters['mhe_type_id'] ?? '') == $type->id)>{{ $type->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="hidden" name="grain" value="{{ $grain }}">
                <button class="btn btn-primary btn-sm w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-lg-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Uptime</div>
            <div class="fs-3 fw-bold {{ $uptimeClass }}">{{ number_format($kpis['uptime_pct'], 1) }}%</div>
        </div></div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Hours Down</div>
            <div class="fs-3 fw-bold">{{ number_format($kpis['hours_down'], 1) }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Hours Available</div>
            <div class="fs-3 fw-bold">{{ number_format($kpis['hours_available'], 1) }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Units</div>
            <div class="fs-3 fw-bold">{{ number_format($kpis['unit_count']) }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">vs Target ({{ number_format($kpis['target'], 0) }}%)</div>
            <div class="fs-3 fw-bold {{ $kpis['vs_target'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $kpis['vs_target'] >= 0 ? '+' : '' }}{{ number_format($kpis['vs_target'], 1) }}%</div>
        </div></div>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly'] as $slug => $label)
        <li class="nav-item">
            <a href="{{ route('dashboard.mhe-uptime', array_merge($filters, ['grain' => $slug])) }}" class="nav-link {{ $grain === $slug ? 'active' : '' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="text-center fw-bold text-uppercase mb-4">Uptime % — {{ ucfirst($grain) }}</h5>
        <div style="max-width: 960px; margin: 0 auto;">
            <canvas id="uptimeBarChart" height="120"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="text-center fw-bold text-uppercase mb-4">Downtime Hours by Supplier</h5>
                <canvas id="supplierPieChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="text-center fw-bold text-uppercase mb-4">Downtime Hours by MHE Type</h5>
                <canvas id="mheTypePieChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><strong>Site / Type Detail</strong></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>MHE Type</th>
                    <th>Supplier</th>
                    <th class="text-end">Available Hrs</th>
                    <th class="text-end">Down Hrs</th>
                    <th class="text-end">Uptime %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['details'] as $row)
                    <tr>
                        <td>{{ $row->site_name }}</td>
                        <td>{{ $row->mhe_type_name }}</td>
                        <td>{{ $row->supplier_name }}</td>
                        <td class="text-end">{{ number_format($row->available_hours, 1) }}</td>
                        <td class="text-end">{{ number_format($row->hours_down, 1) }}</td>
                        <td class="text-end">{{ number_format($row->uptime_pct, 1) }}%</td>
                        <td>
                            @php
                                $badge = match($row->status) {
                                    'On target' => 'success',
                                    'At risk' => 'warning',
                                    default => 'danger',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $row->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    const colors = @json($chartColors);
    const target = @json($data['barChart']['target']);
    const barLabels = @json($data['barChart']['labels']);
    const barValues = @json($data['barChart']['values']);

    const barColors = barValues.map(v => v >= target ? '#198754' : (v >= target - 5 ? '#ffc107' : '#dc3545'));

    new Chart(document.getElementById('uptimeBarChart'), {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Uptime %',
                data: barValues,
                backgroundColor: barColors,
                borderRadius: 4,
            }, {
                type: 'line',
                label: 'Target',
                data: barLabels.map(() => target),
                borderColor: '#005BAC',
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
            }],
        },
        options: {
            responsive: true,
            scales: {
                y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + '%' } },
            },
        },
    });

    function pieChart(canvasId, labels, values) {
        const el = document.getElementById(canvasId);
        if (!el || !labels.length) return;

        new Chart(el, {
            type: 'pie',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, i) => colors[i % colors.length]),
                }],
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' hrs (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
    }

    pieChart('supplierPieChart', @json($data['pieSupplier']['labels']), @json($data['pieSupplier']['values']));
    pieChart('mheTypePieChart', @json($data['pieMheType']['labels']), @json($data['pieMheType']['values']));
});
</script>
@endpush
