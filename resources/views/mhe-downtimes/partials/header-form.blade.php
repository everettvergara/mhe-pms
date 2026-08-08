@php
    $canEditHeader = $canEdit ?? false;
    $canEditDatetimeFields = ($canEdit ?? false) || ($canEditTimes ?? false);
    $now = now()->format('Y-m-d\TH:i');
    $selectedSiteId = old('site_id', $downtime->site_id);
    $selectedSite = ($sites ?? collect())->firstWhere('id', (int) $selectedSiteId) ?? $downtime->site;
    $siteLabel = $selectedSite
        ? $selectedSite->site_name.' ('.$selectedSite->site_code.')'
        : '';
    $defaultSupplierId = old('supplier_id', $downtime->supplier_id ?? $downtime->mheInventory?->supplier_id);
@endphp

<div class="row g-2 pms-compact-header">
    <div class="col-lg-8">
        <div class="row g-2 align-items-end">
            <div class="col-12">
                <label class="form-label mb-0">Site <span class="required-mark" id="site-required-mark">*</span></label>
                <input type="text"
                       id="site_search"
                       list="site-options"
                       class="form-control @error('site_id') is-invalid @enderror"
                       value="{{ old('site_search', $siteLabel) }}"
                       autocomplete="off"
                       @disabled(!$canEditHeader)
                       @if($canEditHeader) required @endif>
                <input type="hidden" name="site_id" id="site_id" value="{{ $selectedSiteId }}">
                <datalist id="site-options"></datalist>
                <div id="site-hint" class="form-text text-warning d-none">Select a site from the list.</div>
                @error('site_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0">Supplier <span class="required-mark d-none" id="supplier-required-mark">*</span></label>
                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" @disabled(!$canEditHeader)>
                    <option value="">Select supplier</option>
                    @foreach($suppliers ?? [] as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) $defaultSupplierId === (int) $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">MHE Type <span class="required-mark">*</span></label>
                <select name="mhe_type_id" id="mhe_type_id" class="form-select @error('mhe_type_id') is-invalid @enderror" @disabled(!$canEditHeader) required>
                    <option value="">Select type</option>
                    @foreach($mheTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('mhe_type_id', $downtime->mhe_type_id) == $type->id)>{{ $type->code }} — {{ $type->description }}</option>
                    @endforeach
                </select>
                @error('mhe_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Ref Unit No.</label>
                <input name="ref_unit_no" id="ref_unit_no" list="unit-numbers" class="form-control @error('ref_unit_no') is-invalid @enderror" value="{{ old('ref_unit_no', $downtime->ref_unit_no) }}" @disabled(!$canEditHeader) autocomplete="off">
                <datalist id="unit-numbers"></datalist>
                @error('ref_unit_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-2 align-items-end mt-1">
            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="w_spare_unit" value="0">
                    <input type="checkbox" name="w_spare_unit" value="1" class="form-check-input" id="w_spare_unit" @checked(old('w_spare_unit', $downtime->w_spare_unit)) @disabled(!$canEditHeader)>
                    <label class="form-check-label" for="w_spare_unit">With Spare Unit</label>
                </div>
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-0">Issue Category <span class="required-mark">*</span></label>
                <select name="mhe_category_id" class="form-select @error('mhe_category_id') is-invalid @enderror" @disabled(!$canEditHeader) required>
                    <option value="">Select category</option>
                    @foreach($mheCategories as $category)
                        <option value="{{ $category->id }}" @selected(old('mhe_category_id', $downtime->mhe_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('mhe_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label mb-0">What <span class="required-mark">*</span></label>
                <input name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $downtime->title) }}" @disabled(!$canEditHeader) required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-2 align-items-end mt-1">
            <div class="col-md-4">
                <label class="form-label mb-0">When From <span class="required-mark">*</span></label>
                <input type="datetime-local" id="date_of_incident" name="date_of_incident" class="form-control @error('date_of_incident') is-invalid @enderror"
                       value="{{ old('date_of_incident', optional($downtime->date_of_incident)->format('Y-m-d\TH:i') ?: ($isNew ? $now : '')) }}"
                       @disabled(!$canEditDatetimeFields) required>
                @error('date_of_incident')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">When To</label>
                <input type="datetime-local" id="uptime" name="uptime" class="form-control @error('uptime') is-invalid @enderror"
                       value="{{ old('uptime', optional($downtime->uptime)->format('Y-m-d\TH:i') ?: ($isNew ? $now : '')) }}"
                       @disabled(!$canEditDatetimeFields)>
                @error('uptime')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Hours Down</label>
                <input id="hours_down" class="form-control" value="{{ old('hours_down', $downtime->hours_down) }}" disabled>
            </div>
        </div>

        <div class="row g-2 align-items-end mt-1">
            <div class="col-12">
                <label class="form-label mb-0">Root Cause</label>
                <textarea name="root_cause" class="form-control @error('root_cause') is-invalid @enderror" rows="3" @disabled(!$canEditHeader)>{{ old('root_cause', $downtime->root_cause) }}</textarea>
                @error('root_cause')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-2 align-items-end mt-1">
            <div class="col-12">
                <label class="form-label mb-0">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" @disabled(!$canEditHeader)>{{ old('description', $downtime->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4 pms-audit-col border-lg-start ps-lg-3 mt-3 mt-lg-0">
        <div class="pms-status-hero text-center mb-3">
            @if($isNew)
                <x-status-badge class="pms-status-badge-lg" :status="\App\Enums\DowntimeStatus::Draft" />
            @else
                <x-status-badge class="pms-status-badge-lg" :status="$downtime->status" />
            @endif
        </div>

        @if(!$isNew)
            <div class="d-flex py-1">
                <span class="audit-label small text-muted">ID</span>
                <span class="small ms-auto text-end">{{ $downtime->id }}</span>
            </div>
        @endif
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Created By</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($downtime->creator?->name ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Created At</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($downtime->created_at?->format('Y-m-d H:i') ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Updated By</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($downtime->updater?->name ?? '—') }}</span>
        </div>
        <div class="d-flex py-1">
            <span class="audit-label small text-muted">Updated At</span>
            <span class="small ms-auto text-end">{{ $isNew ? '—' : ($downtime->updated_at?->format('Y-m-d H:i') ?? '—') }}</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const siteSearch = document.getElementById('site_search');
    const siteIdInput = document.getElementById('site_id');
    const siteDatalist = document.getElementById('site-options');
    const siteHint = document.getElementById('site-hint');
    const typeSelect = document.getElementById('mhe_type_id');
    const unitInput = document.getElementById('ref_unit_no');
    const unitDatalist = document.getElementById('unit-numbers');
    const supplierSelect = document.getElementById('supplier_id');
    const supplierRequiredMark = document.getElementById('supplier-required-mark');
    const dateFromInput = document.getElementById('date_of_incident');
    const dateToInput = document.getElementById('uptime');
    const hoursDownInput = document.getElementById('hours_down');
    const downtimeForm = document.getElementById('downtime-form');

    if (!siteSearch || !siteIdInput || !unitInput) {
        return;
    }

    const siteMap = new Map();
    let siteDebounceTimer = null;
    let unitDebounceTimer = null;

    const resolveSite = async () => {
        const term = siteSearch.value.trim();
        if (!term) {
            siteIdInput.value = '';
            return false;
        }

        const params = new URLSearchParams({ q: term });
        const response = await fetch(`{{ route('mhe-downtimes.lookup-site') }}?${params.toString()}`);
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

    const setSupplierRequired = (required) => {
        if (!supplierSelect || !supplierRequiredMark) return;
        supplierSelect.required = required;
        supplierRequiredMark.classList.toggle('d-none', !required);
        if (required) {
            supplierSelect.disabled = false;
        }
    };

    const applyInventoryMatch = (matched, supplierId = null) => {
        if (!supplierSelect) return;
        const headerEditable = !siteSearch.disabled;
        if (!headerEditable) {
            supplierSelect.disabled = true;
            return;
        }
        if (matched) {
            if (supplierId) {
                supplierSelect.value = String(supplierId);
            }
            supplierSelect.disabled = true;
            setSupplierRequired(false);
        } else {
            supplierSelect.disabled = false;
            setSupplierRequired(true);
        }
    };

    const updateHoursDown = () => {
        if (!hoursDownInput) return;
        const from = dateFromInput?.value;
        const to = dateToInput?.value;
        if (!from || !to) {
            hoursDownInput.value = '';
            return;
        }
        const diffMs = new Date(to).getTime() - new Date(from).getTime();
        if (diffMs < 0) {
            hoursDownInput.value = '';
            return;
        }
        hoursDownInput.value = (Math.round((diffMs / 3600000) * 100) / 100).toFixed(2);
    };

    const loadSites = async () => {
        const params = new URLSearchParams();
        if (siteSearch.value) params.set('q', siteSearch.value);
        const response = await fetch(`{{ route('mhe-downtimes.search-sites') }}?${params.toString()}`);
        const sites = await response.json();
        siteMap.clear();
        siteDatalist.innerHTML = sites.map(site => {
            siteMap.set(site.label, String(site.id));
            return `<option value="${site.label}"></option>`;
        }).join('');
    };

    const onSiteResolved = async () => {
        const resolved = await resolveSite();
        if (resolved) {
            await loadUnits();
            await lookupUnit();
        }
        return resolved;
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
        const response = await fetch(`{{ route('mhe-downtimes.search-units') }}?${params.toString()}`);
        const units = await response.json();
        unitDatalist.innerHTML = units.map(unit => `<option value="${unit.unit_no}"></option>`).join('');
    };

    const lookupUnit = async () => {
        const siteId = siteIdInput.value;
        const refUnitNo = unitInput.value.trim();
        if (!siteId) {
            return;
        }

        if (!refUnitNo) {
            applyInventoryMatch(false);

            return;
        }

        const params = new URLSearchParams({ site_id: siteId, ref_unit_no: refUnitNo });
        const response = await fetch(`{{ route('mhe-downtimes.lookup-unit') }}?${params.toString()}`);
        const result = await response.json();
        applyInventoryMatch(result.matched, result.supplier_id);
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

    [dateFromInput, dateToInput].forEach((input) => {
        input?.addEventListener('change', updateHoursDown);
        input?.addEventListener('input', updateHoursDown);
    });

    downtimeForm?.addEventListener('submit', async (event) => {
        if (siteSearch.disabled) {
            return;
        }

        event.preventDefault();

        const resolved = await resolveSite();
        if (!resolved) {
            siteSearch.classList.add('is-invalid');
            siteHint?.classList.remove('d-none');
            siteSearch.focus();
            return;
        }

        siteSearch.classList.remove('is-invalid');
        siteHint?.classList.add('d-none');

        const formData = new FormData(downtimeForm);
        const submitter = event.submitter;

        if (submitter?.name) {
            formData.set(submitter.name, submitter.value);
        }

        const response = await fetch(downtimeForm.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            redirect: 'follow',
        });

        window.location.assign(response.url);
    });

    loadSites().then(async () => {
        await resolveSite();
        await loadUnits();
        await lookupUnit();
        updateHoursDown();
    });
});
</script>
@endpush
