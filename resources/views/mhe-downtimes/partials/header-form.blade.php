@php
    $canEditHeader = $canEdit ?? false;
    $canEditDatetimeFields = ($canEdit ?? false) || ($canEditTimes ?? false);
    $now = now()->format('Y-m-d\TH:i');
    $selectedSiteId = old('site_id', $downtime->site_id);
    if ($isNew && blank($selectedSiteId)) {
        $assignedSites = $sites ?? collect();
        $onlySite = $assignedSites->count() === 1 ? $assignedSites->first() : null;
        if ($onlySite?->status === \App\Enums\RecordStatus::Active) {
            $selectedSiteId = $onlySite->id;
        }
    }
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
                <div class="suggest-field">
                    <input type="text"
                           id="site_search"
                           class="form-control suggest-field-input @error('site_id') is-invalid @enderror"
                           value="{{ old('site_search', $siteLabel) }}"
                           autocomplete="off"
                           @disabled(!$canEditHeader)
                           @if($canEditHeader) required @endif>
                    <button type="button" class="suggest-field-toggle" id="site-options-toggle" aria-label="Show sites" @disabled(!$canEditHeader)>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div id="site-options" class="suggest-menu">
                        <button type="button" class="suggest-scroll-btn" data-scroll="-1" aria-label="Scroll up"><i class="bi bi-chevron-up"></i></button>
                        <div id="site-options-list" class="suggest-menu-list"></div>
                        <button type="button" class="suggest-scroll-btn" data-scroll="1" aria-label="Scroll down"><i class="bi bi-chevron-down"></i></button>
                    </div>
                </div>
                <input type="hidden" name="site_id" id="site_id" value="{{ $selectedSiteId }}">
                <div id="site-hint" class="form-text text-warning d-none">Select a site from the list.</div>
                @error('site_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-12">
                <label class="form-label mb-0">Ref Unit No.</label>
                <select name="ref_unit_no" id="ref_unit_no" class="form-select @error('ref_unit_no') is-invalid @enderror" data-selected="{{ old('ref_unit_no', $downtime->ref_unit_no) }}" @disabled(!$canEditHeader)>
                    <option value="">Select unit</option>
                </select>
                @error('ref_unit_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="row g-2 align-items-end mt-1">
            <div class="col-md-6">
                <label class="form-label mb-0">MHE Type <span class="required-mark">*</span></label>
                <select name="mhe_type_id" id="mhe_type_id" class="form-select @error('mhe_type_id') is-invalid @enderror" @disabled(!$canEditHeader) required>
                    <option value="">Select type</option>
                    @foreach($mheTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('mhe_type_id', $downtime->mhe_type_id) == $type->id)>{{ $type->code }} — {{ $type->description }}</option>
                    @endforeach
                </select>
                @error('mhe_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label mb-0">Supplier <span class="required-mark d-none" id="supplier-required-mark">*</span></label>
                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" @disabled(!$canEditHeader)>
                    <option value="">Select supplier</option>
                    @foreach($suppliers ?? [] as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) $defaultSupplierId === (int) $supplier->id)>{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

        <div class="row g-2 align-items-start mt-1">
            <div class="col-md-4">
                <label class="form-label mb-0">When From <span class="required-mark">*</span></label>
                <input type="datetime-local" id="date_of_incident" name="date_of_incident" class="form-control @error('date_of_incident') is-invalid @enderror"
                       value="{{ old('date_of_incident', optional($downtime->date_of_incident)->format('Y-m-d\TH:i') ?: ($isNew ? $now : '')) }}"
                       @disabled(!$canEditDatetimeFields) required>
                @error('date_of_incident')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">When To</label>
                <input type="datetime-local" id="uptime" class="form-control"
                       value="{{ optional($downtime->uptime)->format('Y-m-d\TH:i') }}"
                       disabled>
                <div class="form-text text-danger">Automatically updated when implemented by supplier</div>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Hours Down</label>
                <input id="hours_down" class="form-control" value="{{ old('hours_down', $downtime->hours_down) }}" disabled>
                <div class="form-text text-danger">Automatically updated when implemented by supplier</div>
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
    const siteMenu = document.getElementById('site-options');
    const siteMenuList = document.getElementById('site-options-list');
    const siteToggle = document.getElementById('site-options-toggle');
    const siteHint = document.getElementById('site-hint');
    const typeSelect = document.getElementById('mhe_type_id');
    const unitSelect = document.getElementById('ref_unit_no');
    const supplierSelect = document.getElementById('supplier_id');
    const supplierRequiredMark = document.getElementById('supplier-required-mark');
    const dateFromInput = document.getElementById('date_of_incident');
    const dateToInput = document.getElementById('uptime');
    const hoursDownInput = document.getElementById('hours_down');
    const downtimeForm = document.getElementById('downtime-form');

    if (!siteSearch || !siteIdInput || !unitSelect) {
        return;
    }

    const siteMap = new Map();
    let siteDebounceTimer = null;
    let selectedSiteLabel = siteSearch.value.trim();
    let loadedUnits = [];
    let unitsLoadedOnce = false;

    const escapeAttr = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');

    const unitOptionLabel = (unit) => {
        let label = `(${unit.unit_no})`;
        if (unit.mhe_type_label) {
            label += ` ${unit.mhe_type_label}`;
        }
        if (unit.supplier_name) {
            label += ` — ${unit.supplier_name}`;
        }
        return label;
    };

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
            selectedSiteLabel = result.label;
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

    const applyInventoryMatch = (matched, supplierId = null, mheTypeId = null) => {
        const headerEditable = !siteSearch.disabled;
        if (!headerEditable) {
            if (supplierSelect) {
                supplierSelect.disabled = true;
            }
            return;
        }
        if (matched && mheTypeId && typeSelect) {
            typeSelect.value = String(mheTypeId);
        }
        if (!supplierSelect) return;
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

    const closeSiteMenu = () => siteMenu?.classList.remove('is-open');

    const openSiteMenu = () => {
        if (!siteMenu || siteSearch.disabled) return;
        siteMenu.classList.add('is-open');
        requestAnimationFrame(() => {
            const overflows = siteMenuList.scrollHeight > siteMenuList.clientHeight + 1;
            siteMenu.querySelectorAll('.suggest-scroll-btn').forEach((button) => {
                button.hidden = !overflows;
            });
        });
    };

    const loadSites = async (openMenu = true) => {
        if (!siteMenuList || siteSearch.disabled) return;
        const params = new URLSearchParams();
        const value = siteSearch.value.trim();
        if (value && value !== selectedSiteLabel) params.set('q', value);
        const response = await fetch(`{{ route('mhe-downtimes.search-sites') }}?${params.toString()}`);
        const sites = await response.json();
        siteMap.clear();
        siteMenuList.innerHTML = sites.length
            ? sites.map(site => {
                siteMap.set(site.label, String(site.id));
                return `<button type="button" class="suggest-menu-item" data-label="${escapeAttr(site.label)}">${escapeAttr(site.label)}</button>`;
            }).join('')
            : '<div class="suggest-menu-empty">No sites found</div>';
        if (openMenu) {
            openSiteMenu();
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

    const loadUnits = async () => {
        const siteId = siteIdInput.value;
        const selected = unitsLoadedOnce ? unitSelect.value : (unitSelect.dataset.selected || '');
        if (!siteId) {
            unitSelect.innerHTML = '<option value="">Select unit</option>';
            loadedUnits = [];
            unitsLoadedOnce = true;
            return;
        }
        const params = new URLSearchParams({ site_id: siteId, all: '1' });
        const response = await fetch(`{{ route('mhe-downtimes.search-units') }}?${params.toString()}`);
        const units = await response.json();
        loadedUnits = units;
        const seen = new Set();
        const options = ['<option value="">Select unit</option>'];
        units.forEach(unit => {
            const unitNo = String(unit.unit_no ?? '');
            if (!unitNo || seen.has(unitNo)) return;
            seen.add(unitNo);
            const selectedAttr = unitNo === selected ? ' selected' : '';
            options.push(
                `<option value="${escapeAttr(unitNo)}" data-mhe-type-id="${escapeAttr(unit.mhe_type_id ?? '')}" data-supplier-id="${escapeAttr(unit.supplier_id ?? '')}"${selectedAttr}>${escapeAttr(unitOptionLabel(unit))}</option>`
            );
        });
        if (selected && !seen.has(selected)) {
            options.push(`<option value="${escapeAttr(selected)}" selected>${escapeAttr(selected)}</option>`);
        }
        unitSelect.innerHTML = options.join('');
        unitsLoadedOnce = true;
    };

    const lookupUnit = async () => {
        const siteId = siteIdInput.value;
        const refUnitNo = unitSelect.value.trim();
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
        applyInventoryMatch(result.matched, result.supplier_id, result.mhe_type_id);
    };

    siteMenu?.addEventListener('mousedown', (event) => event.preventDefault());
    siteToggle?.addEventListener('mousedown', (event) => event.preventDefault());

    siteMenuList?.addEventListener('click', (event) => {
        const item = event.target.closest('.suggest-menu-item');
        if (!item) return;
        siteSearch.value = item.dataset.label;
        selectedSiteLabel = item.dataset.label;
        closeSiteMenu();
        onSiteResolved();
    });

    siteMenu?.querySelectorAll('[data-scroll]').forEach((button) => {
        button.addEventListener('click', () => {
            siteMenuList?.scrollBy({ top: Number(button.dataset.scroll) * 72, behavior: 'smooth' });
        });
    });

    siteToggle?.addEventListener('click', () => {
        if (siteMenu?.classList.contains('is-open')) {
            closeSiteMenu();
            return;
        }
        loadSites();
    });

    siteSearch.addEventListener('input', () => {
        clearTimeout(siteDebounceTimer);
        siteDebounceTimer = setTimeout(loadSites, 250);
    });

    siteSearch.addEventListener('focus', () => {
        loadSites();
    });

    siteSearch.addEventListener('blur', () => {
        closeSiteMenu();
        onSiteResolved();
    });

    unitSelect.addEventListener('change', lookupUnit);

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
        if (supplierSelect) {
            supplierSelect.disabled = false;
        }

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

    loadSites(false).then(async () => {
        await resolveSite();
        await loadUnits();
        await lookupUnit();
        updateHoursDown();
    });
});
</script>
@endpush
