@extends('layouts.app')
@section('title', $inventory->unit_no ?? 'MHE Inventory')
@section('content')
<x-page-header
    :title="$inventory->unit_no ?? 'MHE Inventory'"
    :breadcrumbs="['Masters' => null, 'MHE Inventories' => route('mhe-inventories.index'), ($inventory->unit_no ?? '#'.$inventory->id) => null]"
>
    <x-slot:actions>
        @can('update', $inventory)
            <a href="{{ route('mhe-inventories.edit', $inventory) }}" class="btn btn-primary btn-sm">Edit</a>
        @endcan
        <a href="{{ route('mhe-inventories.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <x-status-badge :status="$inventory->equipment_status" />
        <dl class="row mt-3 mb-0">
            <dt class="col-sm-3">Unit No</dt>
            <dd class="col-sm-9">{{ $inventory->unit_no ?? '—' }}</dd>
            <dt class="col-sm-3">Site</dt>
            <dd class="col-sm-9">{{ $inventory->siteRelation?->site_name ?? $inventory->site ?? '—' }}</dd>
            <dt class="col-sm-3">District</dt>
            <dd class="col-sm-9">{{ $inventory->siteRelation?->district?->district_name ?? $inventory->district ?? '—' }}</dd>
            <dt class="col-sm-3">MHE Type</dt>
            <dd class="col-sm-9">
                @if($inventory->mheType)
                    {{ $inventory->mheType->code }} — {{ $inventory->mheType->description }}
                @else
                    {{ $inventory->equipment_type ?? '—' }}
                @endif
            </dd>
            <dt class="col-sm-3">Supplier</dt>
            <dd class="col-sm-9">{{ $inventory->supplier?->supplier_name ?? $inventory->provider ?? '—' }}</dd>
            <dt class="col-sm-3">Brand</dt>
            <dd class="col-sm-9">{{ $inventory->brand ?? '—' }}</dd>
            <dt class="col-sm-3">Model</dt>
            <dd class="col-sm-9">{{ $inventory->model ?? '—' }}</dd>
            <dt class="col-sm-3">Unit Role</dt>
            <dd class="col-sm-9">{{ $inventory->unit_role ?? '—' }}</dd>
            <dt class="col-sm-3">Client / FSC</dt>
            <dd class="col-sm-9">{{ $inventory->client_fsc ?? '—' }}</dd>
            <dt class="col-sm-3">Next PMS Date</dt>
            <dd class="col-sm-9">
                {{ $inventory->next_pms_date?->format('Y-m-d') ?? '—' }}
                @if($inventory->lastPmsHeader)
                    <span class="text-muted small ms-2">
                        (<a href="{{ route('pms.show', $inventory->lastPmsHeader) }}">{{ $inventory->lastPmsHeader->pms_no }}</a>)
                    </span>
                @endif
            </dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Operations</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Years in Service</dt>
            <dd class="col-sm-9">{{ $inventory->years_in_service ?? '—' }}</dd>
            <dt class="col-sm-3">Total KL Run</dt>
            <dd class="col-sm-9">{{ $inventory->total_kl_run ?? '—' }}</dd>
            <dt class="col-sm-3">Total Down Hours</dt>
            <dd class="col-sm-9">{{ $inventory->total_down_hours ?? '—' }}</dd>
            <dt class="col-sm-3">Battery / Unit No</dt>
            <dd class="col-sm-9">{{ $inventory->battery_unit_no ?? '—' }}</dd>
            <dt class="col-sm-3">Battery Years</dt>
            <dd class="col-sm-9">{{ $inventory->battery_years ?? '—' }}</dd>
            <dt class="col-sm-3">Battery Man Count</dt>
            <dd class="col-sm-9">{{ $inventory->battery_man_count ?? '—' }}</dd>
            <dt class="col-sm-3">Technicians on Site</dt>
            <dd class="col-sm-9">{{ $inventory->technicians_on_site ?? '—' }}</dd>
            <dt class="col-sm-3">Branch Location</dt>
            <dd class="col-sm-9">{{ $inventory->branch_location ?? '—' }}</dd>
            <dt class="col-sm-3">Total Technicians</dt>
            <dd class="col-sm-9">{{ $inventory->total_technicians ?? '—' }}</dd>
            <dt class="col-sm-3">Remarks</dt>
            <dd class="col-sm-9">{{ $inventory->remarks ?? '—' }}</dd>
        </dl>
    </div>
</div>

<x-audit-info :model="$inventory" />

@can('delete', $inventory)
    <form method="POST" action="{{ route('mhe-inventories.destroy', $inventory) }}" class="mt-3" onsubmit="return confirm('Delete this inventory unit?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm">Delete</button>
    </form>
@endcan
@endsection
