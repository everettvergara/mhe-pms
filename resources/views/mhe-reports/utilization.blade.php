@extends('layouts.app')
@section('title', 'MHE Utilization')

@section('content')
<x-page-header title="MHE System Utilization" :breadcrumbs="['MHE Reports' => null, 'Utilization' => null]" />

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
                <select name="district_id" class="form-select form-select-sm">
                    <option value="">All Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Site</label>
                <select name="site_id" class="form-select form-select-sm">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>District</th>
                    <th>Site</th>
                    @if(!empty($pivot))
                        @foreach(array_keys(reset($pivot)['dates']) as $date)
                            <th class="text-center">{{ $date }}</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($pivot as $siteName => $details)
                    <tr>
                        <td>{{ $details['district'] }}</td>
                        <td>{{ $siteName }}</td>
                        @foreach($details['dates'] as $utilized)
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" @checked($utilized === 1) disabled>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
