@extends('layouts.app')
@section('title', 'MHE Uptime Summary')

@section('content')
<x-page-header title="MHE Uptime Summary" :breadcrumbs="['Dashboard' => route('dashboard'), 'MHE Uptime Summary' => null]" />

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
                <select name="district_id" id="uptime-district" data-controls-site="uptime-site" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Site</label>
                <select name="site_id" id="uptime-site" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" data-district-id="{{ $site->district_id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
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
                <button class="btn btn-primary btn-sm w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><strong>Unit Uptime</strong></div>
    @if($data['groups'] === [])
        <p class="text-center text-muted py-4 mb-0">No data for the selected filters.</p>
    @else
        @foreach($data['groups'] as $districtIndex => $district)
            @php $districtId = 'uptime-d-'.$districtIndex; @endphp
            <div class="border-top">
                <button
                    type="button"
                    class="uptime-toggle btn btn-link text-decoration-none text-body w-100 text-start px-3 py-2 d-flex align-items-center gap-2 collapsed bg-light"
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $districtId }}"
                    aria-expanded="false"
                    aria-controls="{{ $districtId }}"
                >
                    <i class="bi bi-chevron-right"></i>
                    <span class="fw-semibold">{{ $district['name'] }}</span>
                    <span class="badge text-bg-secondary">{{ $district['count'] }}</span>
                </button>
                <div class="collapse" id="{{ $districtId }}">
                    @foreach($district['sites'] as $siteIndex => $site)
                        @php $siteId = $districtId.'-s-'.$siteIndex; @endphp
                        <div class="border-top">
                            <button
                                type="button"
                                class="uptime-toggle btn btn-link text-decoration-none text-body w-100 text-start ps-4 pe-3 py-2 d-flex align-items-center gap-2 collapsed"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $siteId }}"
                                aria-expanded="false"
                                aria-controls="{{ $siteId }}"
                            >
                                <i class="bi bi-chevron-right"></i>
                                <span>{{ $site['name'] }}</span>
                                <span class="badge text-bg-secondary">{{ $site['count'] }}</span>
                            </button>
                            <div class="collapse" id="{{ $siteId }}">
                                @foreach($site['suppliers'] as $supplierIndex => $supplier)
                                    @php $supplierId = $siteId.'-sup-'.$supplierIndex; @endphp
                                    <div class="border-top">
                                        <button
                                            type="button"
                                            class="uptime-toggle btn btn-link text-decoration-none text-body w-100 text-start ps-5 pe-3 py-2 d-flex align-items-center gap-2 collapsed"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $supplierId }}"
                                            aria-expanded="false"
                                            aria-controls="{{ $supplierId }}"
                                        >
                                            <i class="bi bi-chevron-right"></i>
                                            <span>{{ $supplier['name'] }}</span>
                                            <span class="badge text-bg-secondary">{{ $supplier['count'] }}</span>
                                        </button>
                                        <div class="collapse" id="{{ $supplierId }}">
                                            <div class="table-responsive border-top">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="ps-5">Unit No</th>
                                                            <th>MHE Type</th>
                                                            <th class="text-end">Available Hrs</th>
                                                            <th class="text-end">Down Hrs</th>
                                                            <th class="text-end">Uptime Hrs</th>
                                                            <th class="text-end">Uptime %</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($supplier['units'] as $unit)
                                                            <tr>
                                                                <td class="ps-5">{{ $unit['unit_no'] }}</td>
                                                                <td>{{ $unit['mhe_type_name'] }}</td>
                                                                <td class="text-end">{{ number_format($unit['available_hours'], 1) }}</td>
                                                                <td class="text-end">{{ number_format($unit['hours_down'], 1) }}</td>
                                                                <td class="text-end">{{ number_format($unit['uptime_hours'], 1) }}</td>
                                                                <td class="text-end">{{ number_format($unit['uptime_pct'], 1) }}%</td>
                                                                <td>
                                                                    @php
                                                                        $badge = match($unit['status']) {
                                                                            'On target' => 'success',
                                                                            'At risk' => 'warning',
                                                                            default => 'danger',
                                                                        };
                                                                    @endphp
                                                                    <span class="badge text-bg-{{ $badge }}">{{ $unit['status'] }}</span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

@push('styles')
<style>
    .uptime-toggle .bi-chevron-right {
        display: inline-block;
        transition: transform .15s ease;
    }

    .uptime-toggle:not(.collapsed) .bi-chevron-right {
        transform: rotate(90deg);
    }
</style>
@endpush
