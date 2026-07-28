@extends('layouts.app')
@section('title', $title)
@section('content')
@php
    $filterQuery = collect($filters)->except('view')->filter(function ($value) {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && $value !== '';
    })->all();
    $tableUrl = route('reports.show', array_merge(['type' => $type, 'view' => 'table'], $filterQuery));
    $chartMonthSupplierUrl = route('reports.show', array_merge(['type' => $type, 'view' => 'chart-month-supplier'], $filterQuery));
    $chartSupplierSiteUrl = route('reports.show', array_merge(['type' => $type, 'view' => 'chart-supplier-site'], $filterQuery));
    $supplierColors = ['#005BAC', '#4F9DDA', '#F58220', '#198754', '#6c757d', '#6610f2'];
@endphp
<x-page-header :title="$title" :breadcrumbs="[$title => null]">
    <x-slot:actions>
        @if($view === 'table')
            <a href="{{ route('reports.export', [$type, 'csv']) }}?{{ http_build_query($filterQuery) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
            <a href="{{ route('reports.export', [$type, 'pdf']) }}?{{ http_build_query($filterQuery) }}" class="btn btn-outline-danger btn-sm">Export PDF</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="view" value="{{ $view }}">
            @include('reports.partials.supplier-compliance-filters')
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a href="{{ $tableUrl }}" class="nav-link {{ $view === 'table' ? 'active' : '' }}">Table</a>
    </li>
    <li class="nav-item">
        <a href="{{ $chartMonthSupplierUrl }}" class="nav-link {{ $view === 'chart-month-supplier' ? 'active' : '' }}">Chart per Month/Supplier</a>
    </li>
    <li class="nav-item">
        <a href="{{ $chartSupplierSiteUrl }}" class="nav-link {{ $view === 'chart-supplier-site' ? 'active' : '' }}">Chart per Supplier/Site</a>
    </li>
</ul>

@if($view === 'table')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        @foreach($columns as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->show_supplier ? $row->supplier_name : '' }}</td>
                            <td>{{ $row->show_district ? $row->district_name : '' }}</td>
                            <td>{{ $row->site_name }}</td>
                            <td>{{ $row->show_month ? $row->month_label : '' }}</td>
                            <td>{{ $row->completed_on_time }}</td>
                            <td>{{ $row->items_to_complete }}</td>
                            <td>{{ $row->compliance_percent }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@elseif($view === 'chart-month-supplier')
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="text-center fw-bold text-uppercase mb-4">{{ $summaryChartData['title'] }}</h5>
            <div style="max-width: 960px; margin: 0 auto;">
                <canvas id="summaryComplianceChart" height="120"></canvas>
            </div>
        </div>
    </div>
@elseif($view === 'chart-supplier-site')
    @forelse($supplierSiteCharts as $index => $chart)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="text-center fw-bold text-uppercase mb-4">{{ $chart['title'] }}</h5>
                <div style="max-width: 960px; margin: 0 auto;">
                    <canvas id="supplierSiteChart{{ $index }}" class="supplier-site-chart" height="120"></canvas>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-4">No data.</div>
        </div>
    @endforelse
@endif
@endsection

@if(in_array($view, ['chart-month-supplier', 'chart-supplier-site'], true))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Chart) {
                return;
            }

            const supplierColors = @json($supplierColors);

            const dataLabelPlugin = {
                id: 'dataLabelPlugin',
                afterDatasetsDraw(chart) {
                    const { ctx, data } = chart;

                    ctx.save();
                    ctx.font = 'bold 12px sans-serif';
                    ctx.fillStyle = '#333';
                    ctx.textAlign = 'center';

                    data.datasets.forEach((dataset, datasetIndex) => {
                        const meta = chart.getDatasetMeta(datasetIndex);
                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index];
                            if (value === null || value === undefined) {
                                return;
                            }
                            ctx.fillText(value + '%', bar.x, bar.y - 8);
                        });
                    });

                    ctx.restore();
                },
            };

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 120,
                        ticks: {
                            stepSize: 20,
                            callback: (value) => value + '%',
                        },
                        grid: { color: '#e9ecef' },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' } },
                    },
                },
            };

            function renderBarChart(canvasId, config) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) {
                    return;
                }

                const isMultiDataset = Array.isArray(config.datasets) && config.datasets.length > 0;
                const chartConfig = {
                    type: 'bar',
                    data: isMultiDataset
                        ? {
                            labels: config.labels,
                            datasets: config.datasets.map((dataset, index) => ({
                                label: dataset.label,
                                data: dataset.data,
                                backgroundColor: supplierColors[index % supplierColors.length],
                                borderRadius: 2,
                                barPercentage: 0.7,
                                categoryPercentage: 0.8,
                            })),
                        }
                        : {
                            labels: config.labels,
                            datasets: [{
                                data: config.values,
                                backgroundColor: '#005BAC',
                                borderRadius: 2,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                            }],
                        },
                    options: {
                        ...baseOptions,
                        plugins: {
                            legend: { display: isMultiDataset },
                        },
                    },
                    plugins: [dataLabelPlugin],
                };

                new Chart(canvas, chartConfig);
            }

            @if($view === 'chart-month-supplier')
                renderBarChart('summaryComplianceChart', @json($summaryChartData));
            @endif

            @if($view === 'chart-supplier-site')
                const supplierSiteCharts = @json($supplierSiteCharts);
                supplierSiteCharts.forEach((chart, index) => {
                    renderBarChart('supplierSiteChart' + index, chart);
                });
            @endif
        });
    </script>
    @endpush
@endif
