@extends('layouts.app')

@section('title', 'PMS Schedule')

@section('content')
<x-page-header title="PMS Schedule" :breadcrumbs="['Dashboard' => route('dashboard'), 'PMS Schedule' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.pms-schedule') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1" for="pms-schedule-district">District</label>
                <select name="district_id" id="pms-schedule-district" data-controls-site="pms-schedule-site" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="pms-schedule-site">Site</label>
                <select name="site_id" id="pms-schedule-site" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" data-district-id="{{ $site->district_id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="pms-schedule-year">Year</label>
                <select name="year" id="pms-schedule-year" class="form-select form-select-sm">
                    @foreach($years as $year)
                        <option value="{{ $year }}" @selected((int) ($filters['year'] ?? now()->year) === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="pms-schedule-month">Month</label>
                <select name="month" id="pms-schedule-month" class="form-select form-select-sm">
                    @foreach($months as $month => $label)
                        <option value="{{ $month }}" @selected((int) ($filters['month'] ?? now()->month) === (int) $month)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <strong>PMS Schedule</strong>
        <span class="text-muted small ms-2">{{ \Illuminate\Support\Carbon::create((int) $filters['year'], (int) $filters['month'], 1)->format('F Y') }}</span>
    </div>
    <div class="card-body p-0">
        @include('dashboard.partials.pms-schedule-report-table', ['groups' => $groups])
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const districtSelect = document.getElementById('pms-schedule-district');
        const siteSelect = document.getElementById('pms-schedule-site');

        if (!districtSelect || !siteSelect) {
            return;
        }

        const form = districtSelect.closest('form');
        const yearSelect = document.getElementById('pms-schedule-year');
        const monthSelect = document.getElementById('pms-schedule-month');

        const submitFilters = () => {
            form?.requestSubmit();
        };

        districtSelect.addEventListener('change', () => {
            window.applyDistrictSiteFilter?.(districtSelect);
            submitFilters();
        });
        siteSelect.addEventListener('change', submitFilters);
        yearSelect?.addEventListener('change', submitFilters);
        monthSelect?.addEventListener('change', submitFilters);
    });
</script>
@endpush
