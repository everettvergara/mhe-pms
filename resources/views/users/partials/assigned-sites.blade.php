<div class="col-12" id="sites-section">
    <label class="form-label">Assigned Sites <span class="required-mark sites-required-mark d-none">*</span></label>

    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1" for="site-district-filter">District</label>
            <select id="site-district-filter" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($districtsWithSites as $district)
                    <option value="{{ $district->id }}">{{ $district->district_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-site-bulk="select-visible">Select all</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-site-bulk="unselect-visible">Unselect all</button>
        </div>
    </div>

    <div id="assigned-sites-groups" class="border rounded p-3 bg-light">
        @forelse($districtsWithSites as $district)
            <div class="district-site-group mb-3" data-district-id="{{ $district->id }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="fw-semibold">{{ $district->district_name }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-site-bulk="select-district" data-district-id="{{ $district->id }}">Select all in district</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-site-bulk="unselect-district" data-district-id="{{ $district->id }}">Unselect all in district</button>
                    </div>
                </div>
                <div class="row">
                    @foreach($district->sites as $site)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    name="site_ids[]"
                                    value="{{ $site->id }}"
                                    class="form-check-input site-checkbox"
                                    id="site_{{ $site->id }}"
                                    data-district-id="{{ $district->id }}"
                                    @checked(in_array($site->id, old('site_ids', $assignedSiteIds)))
                                >
                                <label class="form-check-label" for="site_{{ $site->id }}">{{ $site->site_name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No active sites are available. Add sites under Administration first.</p>
        @endforelse
    </div>

    <div class="form-text mt-2" id="site-selection-summary" data-site-summary>0 sites selected</div>
    @error('site_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
