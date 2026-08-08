@extends('layouts.app')
@section('title', 'MHE Summary')

@section('content')
<x-page-header title="MHE Summary" :breadcrumbs="['MHE Reports' => null, 'Summary' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="summary-filter" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">District</label>
                <select name="district_id" id="district_id" class="form-select form-select-sm">
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
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
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
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['id' => 'dailyChart', 'title' => 'Daily', 'data' => $charts['daily']],
        ['id' => 'monthlyChart', 'title' => 'Monthly', 'data' => $charts['monthly']],
        ['id' => 'quarterlyChart', 'title' => 'Quarterly', 'data' => $charts['quarterly']],
        ['id' => 'yearlyChart', 'title' => 'Yearly', 'data' => $charts['yearly']],
    ] as $chart)
        <div class="col-lg-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-center text-uppercase fw-bold mb-3">{{ $chart['title'] }}</h6>
                    <canvas id="{{ $chart['id'] }}" height="180"></canvas>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Action Plan Filters</h6>
        <div class="row g-2">
            <div class="col-auto form-check">
                <input class="form-check-input" type="checkbox" name="is_pending" id="is_pending" value="1" form="summary-filter" @checked($filters['is_pending'] ?? true)>
                <label class="form-check-label" for="is_pending">Pending</label>
            </div>
            <div class="col-auto form-check">
                <input class="form-check-input" type="checkbox" name="is_implemented" id="is_implemented" value="1" form="summary-filter" @checked($filters['is_implemented'] ?? true)>
                <label class="form-check-label" for="is_implemented">Implemented</label>
            </div>
            <div class="col-auto form-check">
                <input class="form-check-input" type="checkbox" name="is_no_action_plan" id="is_no_action_plan" value="1" form="summary-filter" @checked($filters['is_no_action_plan'] ?? true)>
                <label class="form-check-label" for="is_no_action_plan">No Action Plan</label>
            </div>
        </div>
    </div>
</div>

<div id="action-plans-content"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.Chart) {
        const charts = @json($charts);
        const configs = [
            ['dailyChart', charts.daily],
            ['monthlyChart', charts.monthly],
            ['quarterlyChart', charts.quarterly],
            ['yearlyChart', charts.yearly],
        ];

        configs.forEach(([id, rows]) => {
            new Chart(document.getElementById(id), {
                type: 'bar',
                data: {
                    labels: rows.map(r => r[0]),
                    datasets: [{
                        label: 'Incidents',
                        data: rows.map(r => r[1]),
                        backgroundColor: '#005BAC',
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        });
    }

    const container = document.getElementById('action-plans-content');

    function loadActionPlans() {
        const params = new URLSearchParams(new FormData(document.getElementById('summary-filter')));
        fetch('{{ route('mhes.summary.action-plans') }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.text())
            .then(html => { container.innerHTML = html; })
            .catch(() => { container.innerHTML = '<p class="text-danger">Failed to load action plans.</p>'; });
    }

    loadActionPlans();
    document.getElementById('summary-filter').addEventListener('change', loadActionPlans);
});
</script>
@endpush
