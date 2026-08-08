@php
    $dateFrom = old('date_from', $pms->date_from?->format('Y-m-d'));
    $readOnly = $readOnly ?? false;
    $selectedSiteId = old('site_id', $pms->site_id);
    $selectedSite = ($sites ?? collect())->firstWhere('id', (int) $selectedSiteId) ?? $pms->site;
    $siteLabel = $selectedSite
        ? $selectedSite->site_name.' ('.$selectedSite->site_code.')'
        : '';
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
                    <select name="supplier_id" id="pms_supplier_id" class="form-select form-select-sm" required>
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
                    <input type="text"
                           id="pms_site_search"
                           list="pms-site-options"
                           class="form-control form-control-sm @error('site_id') is-invalid @enderror"
                           value="{{ old('site_search', $siteLabel) }}"
                           autocomplete="off"
                           required>
                    <input type="hidden" name="site_id" id="pms_site_id" value="{{ $selectedSiteId }}">
                    <datalist id="pms-site-options"></datalist>
                    <div id="pms-site-hint" class="form-text text-warning d-none">Select a site from the list.</div>
                    @error('site_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                    <select name="mhe_type_id" id="pms_mhe_type_id" class="form-select form-select-sm" required>
                        <option value="">Select type</option>
                        @foreach($mheTypes as $mheType)
                            <option value="{{ $mheType->id }}" @selected(old('mhe_type_id', $pms->mhe_type_id) == $mheType->id)>{{ $mheType->code }} — {{ $mheType->description }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-7">
                <label class="form-label mb-0">Unit No. @if(!$readOnly)<span class="required-mark">*</span>@endif</label>
                @if($readOnly)
                    <div class="small">{{ $pms->unit_number }}</div>
                @else
                    <input name="unit_number" id="pms_unit_number" list="pms-unit-numbers" class="form-control form-control-sm @error('unit_number') is-invalid @enderror" value="{{ old('unit_number', $pms->unit_number) }}" autocomplete="off" required>
                    <datalist id="pms-unit-numbers"></datalist>
                    @error('unit_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

@if(!$readOnly)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const siteSearch = document.getElementById('pms_site_search');
    const siteIdInput = document.getElementById('pms_site_id');
    const siteDatalist = document.getElementById('pms-site-options');
    const siteHint = document.getElementById('pms-site-hint');
    const typeSelect = document.getElementById('pms_mhe_type_id');
    const unitInput = document.getElementById('pms_unit_number');
    const unitDatalist = document.getElementById('pms-unit-numbers');
    const supplierSelect = document.getElementById('pms_supplier_id');
    const pmsForm = document.getElementById('pms-save-form');

    if (!siteSearch || !siteIdInput || !unitInput) {
        return;
    }

    let siteDebounceTimer = null;
    let unitDebounceTimer = null;

    const resolveSite = async () => {
        const term = siteSearch.value.trim();
        if (!term) {
            siteIdInput.value = '';
            return false;
        }

        const params = new URLSearchParams({ q: term });
        const response = await fetch(`{{ route('pms.lookup-site') }}?${params.toString()}`);
        const result = await response.json();

        if (result.matched) {
            siteIdInput.value = String(result.id);
            siteSearch.value = result.label;
            siteSearch.classList.remove('is-invalid');
            siteHint?.classList.add('d-none');
            return true;
        }

        siteIdInput.value = '';
        return false;
    };

    const loadSites = async () => {
        const params = new URLSearchParams();
        if (siteSearch.value) params.set('q', siteSearch.value);
        const response = await fetch(`{{ route('pms.search-sites') }}?${params.toString()}`);
        const sites = await response.json();
        siteDatalist.innerHTML = sites.map(site => `<option value="${site.label}"></option>`).join('');
    };

    const loadUnits = async () => {
        if (!unitDatalist) return;
        const siteId = siteIdInput.value;
        if (!siteId) {
            unitDatalist.innerHTML = '';
            return;
        }
        const params = new URLSearchParams({ site_id: siteId });
        if (typeSelect?.value) params.set('mhe_type_id', typeSelect.value);
        if (unitInput.value) params.set('q', unitInput.value);
        const response = await fetch(`{{ route('pms.search-units') }}?${params.toString()}`);
        const units = await response.json();
        unitDatalist.innerHTML = units.map(unit => `<option value="${unit.unit_no}"></option>`).join('');
    };

    const lookupUnit = async () => {
        const siteId = siteIdInput.value;
        const unitNumber = unitInput.value.trim();
        if (!siteId || !unitNumber) {
            return;
        }

        const params = new URLSearchParams({ site_id: siteId, unit_number: unitNumber });
        const response = await fetch(`{{ route('pms.lookup-unit') }}?${params.toString()}`);
        const result = await response.json();

        if (result.matched) {
            if (result.mhe_type_id && typeSelect) {
                typeSelect.value = String(result.mhe_type_id);
            }
            if (result.supplier_id && supplierSelect) {
                supplierSelect.value = String(result.supplier_id);
            }
            if (result.unit_no) {
                unitInput.value = result.unit_no;
            }
        }
    };

    const onSiteResolved = async () => {
        const resolved = await resolveSite();
        if (resolved) {
            await loadUnits();
            await lookupUnit();
        }
        return resolved;
    };

    siteSearch.addEventListener('input', () => {
        clearTimeout(siteDebounceTimer);
        siteDebounceTimer = setTimeout(loadSites, 250);
    });

    siteSearch.addEventListener('focus', () => {
        loadSites();
    });

    siteSearch.addEventListener('change', () => {
        onSiteResolved();
    });

    siteSearch.addEventListener('blur', () => {
        onSiteResolved();
    });

    typeSelect?.addEventListener('change', () => {
        loadUnits();
        lookupUnit();
    });

    unitInput.addEventListener('focus', async () => {
        const resolved = await resolveSite();
        if (!resolved) {
            siteHint?.classList.remove('d-none');
            siteSearch.focus();
            return;
        }
        loadUnits();
    });

    unitInput.addEventListener('input', () => {
        clearTimeout(unitDebounceTimer);
        unitDebounceTimer = setTimeout(() => {
            loadUnits();
            lookupUnit();
        }, 250);
    });

    unitInput.addEventListener('change', lookupUnit);
    unitInput.addEventListener('blur', lookupUnit);

    pmsForm?.addEventListener('submit', async (event) => {
        const resolved = await resolveSite();
        if (!resolved) {
            event.preventDefault();
            siteSearch.classList.add('is-invalid');
            siteHint?.classList.remove('d-none');
            siteSearch.focus();
        }
    });

    loadSites().then(async () => {
        await resolveSite();
        await loadUnits();
        await lookupUnit();
    });
});
</script>
@endpush
@endif
