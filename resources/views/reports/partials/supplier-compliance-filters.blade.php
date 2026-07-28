@php
    $defaultPeriod = now()->format('Y-m');
    $selectedPeriodFrom = $filters['period_from'] ?? $defaultPeriod;
    $selectedPeriodTo = $filters['period_to'] ?? $defaultPeriod;
    $selectedSiteIds = $filters['site_ids'] ?? [];
@endphp
<div class="col-md-2">
    <label class="form-label small">From</label>
    <input type="month" name="period_from" class="form-control form-control-sm" value="{{ $selectedPeriodFrom }}">
</div>
<div class="col-md-2">
    <label class="form-label small">To</label>
    <input type="month" name="period_to" class="form-control form-control-sm" value="{{ $selectedPeriodTo }}">
</div>
<div class="col-md-5">
    <label class="form-label small">Sites</label>
    <select name="site_ids[]" class="form-select form-select-sm" multiple size="4">
        @foreach($sites as $site)
            <option value="{{ $site->id }}" @selected(in_array($site->id, $selectedSiteIds, true))>{{ $site->site_name }}</option>
        @endforeach
    </select>
    <div class="form-text">Leave unselected to include all accessible sites.</div>
</div>
