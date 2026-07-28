@php
    $dateFrom = old('date_from', $pms->date_from?->format('Y-m-d'));
    $readOnly = $readOnly ?? false;
@endphp

<div class="row g-2 pms-compact-header">
    <div class="col-lg-8">
        <div class="row g-2 align-items-end">
            @if(!$isNew)
                <div class="col-md-4">
                    <label class="form-label mb-0">PMS No.</label>
                    @if($readOnly)
                        <div class="small fw-medium">{{ $pms->pms_no }}</div>
                    @else
                        <div class="form-control form-control-sm readonly-field">{{ $pms->pms_no }}</div>
                    @endif
                </div>
            @endif
            @if($isNew && ($suppliers ?? collect())->count() > 1)
                <div class="col-md-4">
                    <label class="form-label mb-0">Supplier <span class="required-mark">*</span></label>
                    <select name="supplier_id" class="form-select form-select-sm" required>
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="{{ $isNew ? 'col-12' : 'col-md-8' }}">
                <label class="form-label mb-0">Site @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->site?->site_name }}</div>
                @else
                    <select name="site_id" class="form-select form-select-sm" required>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id', $pms->site_id) == $site->id)>{{ $site->site_name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-0">MHE Type @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->mheType?->code }} — {{ $pms->mheType?->description }}</div>
                @else
                    <select name="mhe_type_id" class="form-select form-select-sm" required>
                        @foreach($mheTypes as $mheType)
                            <option value="{{ $mheType->id }}" @selected(old('mhe_type_id', $pms->mhe_type_id) == $mheType->id)>{{ $mheType->code }} — {{ $mheType->description }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0">Unit No. @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->unit_number }}</div>
                @else
                    <input name="unit_number" class="form-control form-control-sm" value="{{ old('unit_number', $pms->unit_number) }}" required>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Serial No. @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->serial_number }}</div>
                @else
                    <input name="serial_number" class="form-control form-control-sm" value="{{ old('serial_number', $pms->serial_number) }}" required>
                @endif
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0">Technician @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->technician_name }}</div>
                @else
                    <input name="technician_name" class="form-control form-control-sm" value="{{ old('technician_name', $pms->technician_name) }}" required>
                @endif
            </div>
            @if(!$readOnly)
            <div class="col-auto">
                <label class="form-label mb-0">Date From <span class="required-mark">*</span></label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">Date To <span class="required-mark">*</span></label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ old('date_to', $pms->date_to?->format('Y-m-d')) }}" min="{{ $dateFrom }}" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">Next PMS Date <span class="required-mark">*</span></label>
                <input type="date" name="next_schedule_date" class="form-control form-control-sm" value="{{ old('next_schedule_date', $pms->next_schedule_date?->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required>
            </div>
            @else
            <div class="col-md-4">
                <label class="form-label mb-0">Next PMS Date</label>
                <div class="small">{{ $pms->next_schedule_date?->format('Y-m-d') ?? '—' }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4 pms-audit-col border-lg-start ps-lg-3 mt-3 mt-lg-0">
        <div class="pms-status-hero text-center mb-3">
            @if($isNew)
                <x-status-badge class="pms-status-badge-lg" :status="\App\Enums\PmsStatus::Draft" />
            @else
                <x-status-badge class="pms-status-badge-lg" :status="$pms->status" />
            @endif
        </div>

        @if($readOnly)
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Date From</span>
            <span class="small ms-auto text-end">{{ $pms->date_from?->format('Y-m-d') ?? '—' }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Date To</span>
            <span class="small ms-auto text-end">{{ $pms->date_to?->format('Y-m-d') ?? '—' }}</span>
        </div>
        @endif
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Created By</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($pms->creator?->name ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Created At</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($pms->created_at?->format('Y-m-d H:i') ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Updated By</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($pms->updater?->name ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Updated At</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($pms->updated_at?->format('Y-m-d H:i') ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Finalized By</span>
            <span class="small ms-auto text-end">{{ $pms->submitter?->name ?? '—' }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Finalized At</span>
            <span class="small ms-auto text-end">{{ $pms->submitted_at?->format('Y-m-d H:i') ?? '—' }}</span>
        </div>
    </div>
</div>
