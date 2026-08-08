@props(['downtime'])

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <strong>MHE Downtime Information</strong>
            @if($downtime->supplier)
                <span class="text-muted small ms-2">{{ $downtime->supplier->supplier_name }}</span>
            @endif
        </div>
        <a href="{{ route('mhe-downtimes.show', $downtime) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer">View full downtime</a>
    </div>
    <div class="card-body">
        <dl class="row small mb-0">
            <dt class="col-sm-3">Downtime #</dt><dd class="col-sm-9">{{ $downtime->id }}</dd>
            <dt class="col-sm-3">Site</dt><dd class="col-sm-9">{{ $downtime->site?->site_name }}</dd>
            <dt class="col-sm-3">Unit No.</dt><dd class="col-sm-9">{{ $downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? '—' }}</dd>
            <dt class="col-sm-3">Title</dt><dd class="col-sm-9">{{ $downtime->title }}</dd>
            <dt class="col-sm-3">Incident Date</dt><dd class="col-sm-9">{{ $downtime->date_of_incident?->format('Y-m-d') ?? '—' }}</dd>
            <dt class="col-sm-3">Hours Down</dt><dd class="col-sm-9">{{ $downtime->hours_down ?? '—' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><x-status-badge :status="$downtime->status" /></dd>
        </dl>
    </div>
</div>
