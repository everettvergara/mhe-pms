@extends('layouts.app')
@section('title', $isEdit ? 'Edit MHE Inventory' : 'New MHE Inventory')
@section('content')
<x-page-header
    :title="$isEdit ? 'Edit MHE Inventory' : 'New MHE Inventory'"
    :breadcrumbs="['Masters' => null, 'MHE Inventories' => route('mhe-inventories.index'), ($isEdit ? 'Edit' : 'New') => null]"
/>
<form method="POST" action="{{ $isEdit ? route('mhe-inventories.update', $inventory) : route('mhe-inventories.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="card mb-3">
        <div class="card-header">Unit</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Unit No <span class="required-mark">*</span></label>
                <input type="text" name="unit_no" class="form-control @error('unit_no') is-invalid @enderror" value="{{ old('unit_no', $inventory->unit_no) }}" required>
                @error('unit_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">MHE Type</label>
                <select name="mhe_type_id" class="form-select @error('mhe_type_id') is-invalid @enderror">
                    <option value="">Select type</option>
                    @foreach($mheTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) old('mhe_type_id', $inventory->mhe_type_id) === (string) $type->id)>{{ $type->code }} — {{ $type->description }}</option>
                    @endforeach
                </select>
                @error('mhe_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Unit Role</label>
                <input type="text" name="unit_role" class="form-control @error('unit_role') is-invalid @enderror" value="{{ old('unit_role', $inventory->unit_role ?? 'Primary') }}">
                @error('unit_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Site <span class="required-mark">*</span></label>
                <select name="site_id" class="form-select @error('site_id') is-invalid @enderror" required>
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('site_id', $inventory->site_id) === (string) $site->id)>{{ $site->site_code }} — {{ $site->site_name }}</option>
                    @endforeach
                </select>
                @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $inventory->supplier_id) === (string) $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="required-mark">*</span></label>
                <select name="equipment_status" class="form-select @error('equipment_status') is-invalid @enderror" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('equipment_status', $inventory->equipment_status?->value) === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
                @error('equipment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Brand</label>
                <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand', $inventory->brand) }}">
                @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Model</label>
                <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $inventory->model) }}">
                @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Client / FSC</label>
                <input type="text" name="client_fsc" class="form-control @error('client_fsc') is-invalid @enderror" value="{{ old('client_fsc', $inventory->client_fsc) }}">
                @error('client_fsc')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Operations</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Years in Service</label>
                <input type="text" name="years_in_service" class="form-control" value="{{ old('years_in_service', $inventory->years_in_service) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Total KL Run</label>
                <input type="text" name="total_kl_run" class="form-control" value="{{ old('total_kl_run', $inventory->total_kl_run) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Total Down Hours</label>
                <input type="text" name="total_down_hours" class="form-control" value="{{ old('total_down_hours', $inventory->total_down_hours) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Battery / Unit No</label>
                <input type="text" name="battery_unit_no" class="form-control" value="{{ old('battery_unit_no', $inventory->battery_unit_no) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Battery Years</label>
                <input type="text" name="battery_years" class="form-control" value="{{ old('battery_years', $inventory->battery_years) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Battery Man Count</label>
                <input type="text" name="battery_man_count" class="form-control" value="{{ old('battery_man_count', $inventory->battery_man_count) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Technicians on Site</label>
                <input type="text" name="technicians_on_site" class="form-control" value="{{ old('technicians_on_site', $inventory->technicians_on_site) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Branch Location</label>
                <input type="text" name="branch_location" class="form-control" value="{{ old('branch_location', $inventory->branch_location) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Total Technicians</label>
                <input type="text" name="total_technicians" class="form-control" value="{{ old('total_technicians', $inventory->total_technicians) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $inventory->remarks) }}</textarea>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
            <a href="{{ route('mhe-inventories.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</form>
@endsection
