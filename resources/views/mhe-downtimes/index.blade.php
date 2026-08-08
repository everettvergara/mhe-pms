@extends('layouts.app')
@section('title', 'MHE Downtimes')
@section('content')
<x-page-header title="MHE Downtimes" :breadcrumbs="['Transactions'=>null,'MHE Downtimes'=>null]" />
<div class="d-flex justify-content-end gap-2 mb-2">
    @if(auth()->user()->hasPermission('mhe-downtimes.import'))
        <a href="{{ route('mhe-downtimes.import.index') }}" class="btn btn-outline-secondary btn-sm">Import from fsc_web</a>
    @endif
</div>
<x-list-toolbar :route="route('mhe-downtimes.index')" :state="$state" :create-route="auth()->user()->can('create', \App\Models\MheDowntime::class) ? route('mhe-downtimes.create') : null" />

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('mhe-downtimes.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="filters[date_from]" class="form-control form-control-sm" value="{{ $state['filters']['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="filters[date_to]" class="form-control form-control-sm" value="{{ $state['filters']['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">District</label>
                <select name="filters[district_id]" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" @selected(($state['filters']['district_id'] ?? '') == $district->id)>{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Site</label>
                <select name="filters[site_id]" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(($state['filters']['site_id'] ?? '') == $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="filters[status]" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($state['filters']['status'] ?? '') === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Action Plan</label>
                <select name="filters[needs_action_plan]" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" @selected(($state['filters']['needs_action_plan'] ?? '') === '1')>No Action Plan</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('mhe-downtimes.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>What</th>
                    <th>Site</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Unit No.</th>
                    <th>Hours Down</th>
                    <th>Status</th>
                    <th>Action Plan</th>
                    <th>Incident</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr data-href="{{ route('mhe-downtimes.show', $record) }}">
                        <td>{{ $record->id }}</td>
                        <td>{{ $record->title }}</td>
                        <td>{{ $record->site?->site_name }}</td>
                        <td>{{ $record->mheCategory?->name }}</td>
                        <td>{{ $record->mheType?->code }}</td>
                        <td>{{ $record->ref_unit_no }}</td>
                        <td>{{ $record->hours_down ?? '—' }}</td>
                        <td><span class="badge text-bg-secondary">{{ $record->status->value }}</span></td>
                        <td>
                            @if($record->status->value === 'Posted' && $record->action_plans_count === 0)
                                <span class="badge text-bg-danger">No Action Plan</span>
                            @elseif($record->action_plans_count > 0)
                                <span class="badge text-bg-success">{{ $record->action_plans_count }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $record->date_of_incident?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
