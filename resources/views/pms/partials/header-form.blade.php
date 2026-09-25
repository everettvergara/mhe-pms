@php
    $dateFrom = old('date_from', $pms->date_from?->format('Y-m-d'));
    $readOnly = $readOnly ?? false;
    $selectedSiteId = old('site_id', $pms->site_id);
    if ($isNew && blank($selectedSiteId)) {
        $assignedSites = $sites ?? collect();
        $onlySite = $assignedSites->count() === 1 ? $assignedSites->first() : null;
        if ($onlySite?->status === \App\Enums\RecordStatus::Active) {
            $selectedSiteId = $onlySite->id;
        }
    }
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
                    <div class="suggest-field">
                        <input type="text"
                               id="pms_site_search"
                               class="form-control form-control-sm suggest-field-input @error('site_id') is-invalid @enderror"
                               value="{{ old('site_search', $siteLabel) }}"
                               autocomplete="off"
                               required>
                        <button type="button" class="suggest-field-toggle" id="pms-site-options-toggle" aria-label="Show sites">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="pms-site-options" class="suggest-menu">
                            <button type="button" class="suggest-scroll-btn" data-scroll="-1" aria-label="Scroll up"><i class="bi bi-chevron-up"></i></button>
                            <div id="pms-site-options-list" class="suggest-menu-list"></div>
                            <button type="button" class="suggest-scroll-btn" data-scroll="1" aria-label="Scroll down"><i class="bi bi-chevron-down"></i></button>
                        </div>
                    </div>
                    <input type="hidden" name="site_id" id="pms_site_id" value="{{ $selectedSiteId }}">
                    <div id="pms-site-hint" class="form-text text-warning d-none">Select a site from the list.</div>
                    @error('site_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
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
    const siteMenu = document.getElementById('pms-site-options');
    const siteMenuList = document.getElementById('pms-site-options-list');
    const siteToggle = document.getElementById('pms-site-options-toggle');
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
    let selectedSiteLabel = siteSearch.value.trim();
    let unitDebounceTimer = null;
    let loadedUnits = [];

    const bareUnitQuery = (value) => {
        const trimmed = value.trim();
        const match = trimmed.match(/^(.*)\s+\([^)]*\)$/);

        return match ? match[1].trim() : trimmed;
    };

    const escapeAttr = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');

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
            selectedSiteLabel = result.label;
            siteSearch.classList.remove('is-invalid');
            siteHint?.classList.add('d-none');
            return true;
        }

        siteIdInput.value = '';
        return false;
    };

    const closeSiteMenu = () => siteMenu?.classList.remove('is-open');

    const openSiteMenu = () => {
        if (!siteMenu) return;
        siteMenu.classList.add('is-open');
        requestAnimationFrame(() => {
            const overflows = siteMenuList.scrollHeight > siteMenuList.clientHeight + 1;
            siteMenu.querySelectorAll('.suggest-scroll-btn').forEach((button) => {
                button.hidden = !overflows;
            });
        });
    };

    const loadSites = async () => {
        if (!siteMenuList) return;
        const params = new URLSearchParams();
        const value = siteSearch.value.trim();
        if (value && value !== selectedSiteLabel) params.set('q', value);
        const response = await fetch(`{{ route('pms.search-sites') }}?${params.toString()}`);
        const sites = await response.json();
        siteMenuList.innerHTML = sites.length
            ? sites.map(site => `<button type="button" class="suggest-menu-item" data-label="${escapeAttr(site.label)}">${escapeAttr(site.label)}</button>`).join('')
            : '<div class="suggest-menu-empty">No sites found</div>';
        openSiteMenu();
    };

    const loadUnits = async () => {
        if (!unitDatalist) return;
        const siteId = siteIdInput.value;
        if (!siteId) {
            unitDatalist.innerHTML = '';
            loadedUnits = [];
            return;
        }
        const params = new URLSearchParams({ site_id: siteId });
        const query = bareUnitQuery(unitInput.value);
        if (query) params.set('q', query);
        const response = await fetch(`{{ route('pms.search-units') }}?${params.toString()}`);
        const units = await response.json();
        loadedUnits = units;
        unitDatalist.innerHTML = units.map(unit => `<option value="${escapeAttr(unit.label || unit.unit_no)}"></option>`).join('');
    };

    const applyUnitSelection = () => {
        const raw = unitInput.value.trim();
        if (!raw) {
            return false;
        }

        const bare = bareUnitQuery(raw);
        const match = loadedUnits.find(unit =>
            unit.label === raw || unit.unit_no === raw || unit.unit_no === bare
        );

        if (!match) {
            return false;
        }

        unitInput.value = match.unit_no ?? '';
        if (match.mhe_type_id && typeSelect) {
            typeSelect.value = String(match.mhe_type_id);
        }
        if (match.supplier_id && supplierSelect) {
            supplierSelect.value = String(match.supplier_id);
        }

        return true;
    };

    const lookupUnit = async () => {
        applyUnitSelection();

        const siteId = siteIdInput.value;
        const unitNumber = bareUnitQuery(unitInput.value);
        if (unitNumber !== unitInput.value.trim()) {
            unitInput.value = unitNumber;
        }
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

    typeSelect?.addEventListener('change', () => {
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
