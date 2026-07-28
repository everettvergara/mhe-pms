@extends('layouts.app')
@section('title', $isEdit ? 'Edit Supplier' : 'New Supplier')
@section('content')
<x-page-header :title="$isEdit ? 'Edit Supplier' : 'New Supplier'" :breadcrumbs="['Masters' => null, 'Suppliers' => route('suppliers.index'), ($isEdit ? 'Edit' : 'New') => null]" />
<form method="POST" action="{{ $isEdit ? route('suppliers.update', $supplier) : route('suppliers.store') }}" enctype="multipart/form-data">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="card"><div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label d-block">Supplier Image</label>
                <div class="d-flex align-items-center gap-3">
                    @if ($supplier->imageUrl())
                        <img src="{{ $supplier->imageUrl() }}" alt="{{ $supplier->supplier_name }}" class="supplier-preview" width="80" height="80">
                    @else
                        <i class="bi bi-building text-muted" style="font-size: 4rem;"></i>
                    @endif
                    <div class="flex-grow-1">
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">JPG, PNG, or WEBP. Max 2 MB.</div>
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($isEdit && $supplier->image)
                            <div class="form-check mt-2">
                                <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="remove_image" @checked(old('remove_image'))>
                                <label class="form-check-label" for="remove_image">Remove current image</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier Code <span class="required-mark">*</span></label>
                <input type="text" name="supplier_code" class="form-control @error('supplier_code') is-invalid @enderror" value="{{ old('supplier_code', $supplier->supplier_code) }}" maxlength="20" required>
                @error('supplier_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label">Supplier Name <span class="required-mark">*</span></label>
                <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name', $supplier->supplier_name) }}" maxlength="150" required>
                @error('supplier_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $supplier->contact_number) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $supplier->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="required-mark">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $supplier->status?->value) === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Back</a>
    </div></div>
</form>
@endsection
