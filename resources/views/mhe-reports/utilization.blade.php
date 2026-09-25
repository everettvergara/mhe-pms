@extends('layouts.app')
@section('title', 'MHE + PMS Site Utilization')

@section('content')
<x-page-header title="MHE + PMS Site Utilization" :breadcrumbs="['MHE Reports' => null, 'Site Utilization' => null]" />

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
            <div class="col-md-3">
                <label class="form-label small mb-1">District</label>
                <select name="district_id" id="utilization-district" data-controls-site="utilization-site" class="form-select form-select-sm">
                    <option value="">All Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Site</label>
                <select name="site_id" id="utilization-site" class="form-select form-select-sm">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" data-district-id="{{ $site->district_id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Search</button>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">
            A site is using the system when it has a submitted PMS or a posted MHE downtime in this range. Usage is the combined count. Drafts and cancelled records are left out.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Sites using PMS / MHE</span>
                <span class="badge text-bg-primary">{{ count($utilization['used']) }} {{ \Illuminate\Support\Str::plural('site', count($utilization['used'])) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Site code</th>
                            <th>Site</th>
                            <th>District</th>
                            <th class="text-end">PMS</th>
                            <th class="text-end">MHE</th>
                            <th class="text-end">Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($utilization['used'] as $row)
                            <tr>
                                <td>{{ $row['site_code'] }}</td>
                                <td>{{ $row['site_name'] }}</td>
                                <td>{{ $row['district'] }}</td>
                                <td class="text-end">{{ $row['pms'] }}</td>
                                <td class="text-end">{{ $row['mhe'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No site used PMS or MHE in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>No PMS or MHE in this period</span>
                <span class="badge text-bg-secondary">{{ count($utilization['unused']) }} {{ \Illuminate\Support\Str::plural('site', count($utilization['unused'])) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Site code</th>
                            <th>Site</th>
                            <th>District</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($utilization['unused'] as $row)
                            <tr>
                                <td>{{ $row['site_code'] }}</td>
                                <td>{{ $row['site_name'] }}</td>
                                <td>{{ $row['district'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Every site in this filter used PMS or MHE.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
