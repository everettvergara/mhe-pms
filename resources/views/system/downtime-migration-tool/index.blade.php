@extends('layouts.app')
@section('title', 'Migrate MHE Downtime Data')
@section('content')
@php
    $selectedDistrict = old('district', $filter['district_code'] ?? '');
    $selectedSites = array_map('intval', (array) old('sites', $filter['legacy_site_ids'] ?? []));
    $selectedStatuses = array_map('intval', (array) old('statuses', $filter['status_ids'] ?? []));
    $dateFrom = old('date_from', $filter['date_from'] ?? '');
    $dateTo = old('date_to', $filter['date_to'] ?? '');
@endphp

<x-page-header title="Migrate MHE Downtime Data" :breadcrumbs="['System' => null, 'Migrate MHE Downtime Data' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2">Copies MHE downtimes from fsc_web into this site for one district, then the sites you choose. Each downtime brings its action items and attachment records. Action items are stored as implemented (Confirmed). The fsc_web downtime ID is saved on the new record so the same row is never migrated twice.</p>
        <p class="mb-0 text-muted">Attachment files are not copied. A row is skipped when its site, MHE type, category, or creator cannot be matched. Limit per run: {{ number_format($maxRows) }}.</p>
    </div>
</div>

@if(session('migration_result'))
    @php $result = session('migration_result'); @endphp
    <div class="card mb-3 border-success">
        <div class="card-header">Migration result</div>
        <div class="card-body">
            <p class="mb-2">Batch <code>{{ $result['batch_id'] }}</code></p>
            <ul class="mb-2">
                <li>Downtimes created: {{ $result['downtimes_created'] }}</li>
                <li>Action items created: {{ $result['action_plans_created'] }}</li>
                <li>Downtime attachment records: {{ $result['downtime_attachments_created'] }}</li>
                <li>Action item attachment records: {{ $result['action_plan_attachments_created'] }}</li>
                <li>Already migrated, skipped: {{ $result['skipped'] }}</li>
                <li>Failed: {{ count($result['failed']) }}</li>
            </ul>
            @if($result['downtimes_created'] > 0)
                <a class="btn btn-outline-primary btn-sm" href="{{ route('mhe-downtimes.index', ['filters' => ['legacy_batch' => $result['batch_id']]]) }}">Open migrated downtimes</a>
            @endif
            @if($result['failed'] !== [])
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>fsc_web ID</th><th>Reason</th></tr></thead>
                        <tbody>
                            @foreach($result['failed'] as $failure)
                                <tr>
                                    <td>{{ $failure['legacy_id'] }}</td>
                                    <td>{{ $failure['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header">fsc_web connection</div>
    <div class="card-body">
        <dl class="row mb-3">
            <dt class="col-sm-3">Host</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['host'] ?: '(empty)' }}</code></dd>
            <dt class="col-sm-3">Database</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['database'] ?: '(empty)' }}</code></dd>
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['username'] ?: '(empty)' }}</code></dd>
        </dl>
        @if($connectionError)
            <div class="alert alert-warning mb-3">Could not load districts from fsc_web: {{ $connectionError }}</div>
        @endif
        <form method="POST" action="{{ route('system.downtime-migration-tool.test-connection') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">Test connection</button>
        </form>
    </div>
</div>

<form id="migration-filter" method="POST" action="{{ route('system.downtime-migration-tool.preview') }}" class="card mb-3">
    @csrf
    <div class="card-header">Filter</div>
    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label" for="district">District</label>
            <select id="district" name="district" class="form-select @error('district') is-invalid @enderror" required>
                <option value="">Select a district</option>
                @foreach($districts as $district)
                    <option value="{{ $district['code'] }}" @selected($selectedDistrict === $district['code']) @disabled(! $district['mapped'])>
                        {{ $district['code'] }} — {{ $district['name'] }}{{ $district['mapped'] ? '' : ' (not in mhe-pms)' }}{{ $district['local_deleted'] ? ' (deleted)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="sites">Sites</label>
            <select id="sites" name="sites[]" class="form-select" multiple size="8">
                <option value="" disabled>Select a district to load sites</option>
            </select>
            <div class="form-text">Leave unselected to include every site in the district. Each option shows the fsc_web site, the mhe-pms site, and how many downtimes fsc_web has.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="date_from">Incident from</label>
            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" class="form-control @error('date_from') is-invalid @enderror">
            @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="date_to">Incident to</label>
            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" class="form-control @error('date_to') is-invalid @enderror">
            @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <div class="form-label">fsc_web status</div>
            @foreach($legacyStatuses as $statusId => $statusName)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="statuses[]" id="status-{{ $statusId }}" value="{{ $statusId }}" @checked(in_array($statusId, $selectedStatuses, true))>
                    <label class="form-check-label" for="status-{{ $statusId }}">{{ $statusName }}</label>
                </div>
            @endforeach
            <div class="form-text">Leave unchecked to include every status, including rows with no status.</div>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary" formaction="{{ route('system.downtime-migration-tool.count') }}">Count</button>
            <button type="submit" class="btn btn-primary">Preview</button>
        </div>
    </div>
</form>

@if($summary)
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total in fsc_web</div><div class="fs-4">{{ $summary['total'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Ready to migrate</div><div class="fs-4">{{ $summary['ready'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Already migrated</div><div class="fs-4">{{ $summary['already_migrated'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Blocked</div><div class="fs-4">{{ $summary['blocked'] }}</div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-2">Ready rows will also copy <strong>{{ $summary['ready_action_plans'] }}</strong> action item(s), <strong>{{ $summary['ready_downtime_attachments'] }}</strong> downtime attachment record(s), and <strong>{{ $summary['ready_action_plan_attachments'] }}</strong> action item attachment record(s).</p>
            <div class="row">
                <div class="col-md-6">
                    <h6>Blocked by reason</h6>
                    <ul class="mb-0">
                        @foreach($summary['reason_labels'] as $reason => $label)
                            <li>{{ $label }}: {{ $summary['blocked_by_reason'][$reason] ?? 0 }}</li>
                        @endforeach
                    </ul>
                    @if($summary['unmapped_creators'] !== [])
                        <p class="mt-2 mb-0">Creators to migrate first: {{ implode(', ', $summary['unmapped_creators']) }}.
                            <a href="{{ route('system.user-migration-tool.index') }}">Open User Migration Tool</a>
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h6>By site</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>fsc_web site</th><th>mhe-pms site</th><th>Total</th><th>Ready</th><th>Blocked</th><th>Done</th></tr>
                            </thead>
                            <tbody>
                                @forelse($summary['by_site'] as $site)
                                    <tr>
                                        <td>{{ $site['legacy_site_code'] }}</td>
                                        <td>{{ $site['local_site_code'] ?: '—' }}</td>
                                        <td>{{ $site['total'] }}</td>
                                        <td>{{ $site['ready'] }}</td>
                                        <td>{{ $site['blocked'] }}</td>
                                        <td>{{ $site['already_migrated'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">No rows for this filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if($preview)
    <div class="d-flex gap-2 mb-3">
        @foreach(['ready' => 'Ready', 'blocked' => 'Blocked', 'already_migrated' => 'Already migrated'] as $tab => $label)
            <button type="submit" class="btn btn-sm {{ $preview['tab'] === $tab ? 'btn-primary' : 'btn-outline-primary' }}" form="migration-filter" formaction="{{ route('system.downtime-migration-tool.preview') }}" name="tab" value="{{ $tab }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if($preview['tab'] === 'ready' && $preview['ready_total'] > 0)
        <form method="POST" action="{{ route('system.downtime-migration-tool.migrate') }}" id="migration-import">
            @csrf
            <input type="hidden" name="preview_token" value="{{ $preview['preview_token'] }}">
    @endif

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        @if($preview['tab'] === 'ready')
                            <th style="width: 2rem;"><input type="checkbox" class="form-check-input" id="import-select-all" title="Select all on this page"></th>
                        @endif
                        <th>fsc_web ID</th>
                        <th>Title</th>
                        <th>Site</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Incident</th>
                        <th>Uptime</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Files</th>
                        @if($preview['tab'] !== 'ready')
                            <th>Detail</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($preview['rows'] as $row)
                        <tr>
                            @if($preview['tab'] === 'ready')
                                <td><input type="checkbox" class="form-check-input import-id" name="import_ids[]" value="{{ $row['legacy_id'] }}" checked></td>
                            @endif
                            <td>{{ $row['legacy_id'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>
                                {{ $row['legacy_site_code'] }}
                                <div class="text-muted small">→ {{ $row['local_site_code'] ?: 'UNMAPPED' }}</div>
                            </td>
                            <td>{{ $row['type_code'] ?: '—' }}</td>
                            <td>{{ $row['category_code'] ?: '—' }}</td>
                            <td>
                                {{ $row['ref_unit_no'] }}
                                <div class="text-muted small">{{ $row['inventory_matched'] ? 'inventory matched' : 'no inventory match' }}</div>
                            </td>
                            <td>{{ $row['date_of_incident'] ? \Illuminate\Support\Str::of($row['date_of_incident'])->substr(0, 16) : '—' }}</td>
                            <td>{{ $row['uptime'] ? \Illuminate\Support\Str::of($row['uptime'])->substr(0, 16) : '—' }}</td>
                            <td>{{ $row['hours_down'] ?? '—' }}</td>
                            <td>{{ $row['legacy_status_name'] }} → {{ $row['new_status'] }}</td>
                            <td>{{ count($row['action_plans']) }}</td>
                            <td>{{ count($row['downtime_attachments']) + count($row['action_plan_attachments']) + ($row['header_attachment'] ? 1 : 0) }}</td>
                            @if($preview['tab'] !== 'ready')
                                <td class="small">
                                    @if($row['status'] === 'already_migrated')
                                        Already migrated (mhe-pms #{{ $row['local_downtime_id'] }})
                                    @else
                                        {{ $row['block_message'] }}
                                    @endif
                                </td>
                            @endif
                        </tr>
                        @if($row['action_plans'] !== [] || $row['warnings'] !== [])
                            <tr>
                                <td colspan="{{ $preview['tab'] === 'ready' ? 13 : 13 }}">
                                    @if($row['warnings'] !== [])
                                        <div class="small text-warning">{{ implode(' ', $row['warnings']) }}</div>
                                    @endif
                                    @if($row['action_plans'] !== [])
                                        <details>
                                            <summary class="small">Action items (will be Implemented / Confirmed)</summary>
                                            <ul class="small mb-0">
                                                @foreach($row['action_plans'] as $plan)
                                                    <li>#{{ $plan['legacy_id'] }} {{ \Illuminate\Support\Str::limit($plan['text'], 120) }} — implemented {{ $plan['implemented_at'] ?: '—' }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-4">No rows on this tab.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($preview['last_page'] > 1)
            <div class="card-body d-flex justify-content-between">
                <span class="text-muted small">Page {{ $preview['page'] }} of {{ $preview['last_page'] }} ({{ $preview['total'] }} rows)</span>
                <div class="d-flex gap-2">
                    @if($preview['page'] > 1)
                        <button type="submit" class="btn btn-outline-secondary btn-sm" form="migration-filter" formaction="{{ route('system.downtime-migration-tool.preview', ['tab' => $preview['tab'], 'page' => $preview['page'] - 1]) }}">Previous</button>
                    @endif
                    @if($preview['page'] < $preview['last_page'])
                        <button type="submit" class="btn btn-outline-secondary btn-sm" form="migration-filter" formaction="{{ route('system.downtime-migration-tool.preview', ['tab' => $preview['tab'], 'page' => $preview['page'] + 1]) }}">Next</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($preview['tab'] === 'ready' && $preview['ready_total'] > 0)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="migrate_all" value="1" id="migrate-all">
                        <label class="form-check-label" for="migrate-all">Migrate all ready rows ({{ $preview['ready_total'] }}), including rows on other pages</label>
                    </div>
                    <label class="form-label" for="confirmation">Type <strong>{{ $confirmationPhrase }}</strong> to migrate</label>
                    <input type="text" name="confirmation" id="confirmation" class="form-control mb-3 @error('confirmation') is-invalid @enderror" autocomplete="off">
                    @error('confirmation')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
                    @error('import_ids')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-danger">Migrate selected</button>
                </div>
            </div>
        </form>
    @endif
@endif
@endsection

@push('scripts')
<script>
    const siteSelect = document.getElementById('sites');
    const districtSelect = document.getElementById('district');
    const selectedSiteIds = @json($selectedSites);
    const sitesUrl = @json(route('system.downtime-migration-tool.sites'));

    function renderSites(sites) {
        siteSelect.innerHTML = '';

        if (sites.length === 0) {
            const empty = document.createElement('option');
            empty.disabled = true;
            empty.textContent = 'No sites in this district';
            siteSelect.appendChild(empty);
            return;
        }

        sites.forEach((site) => {
            const option = document.createElement('option');
            option.value = site.id;
            const target = site.mapped ? site.local_site_code : 'UNMAPPED';
            option.textContent = site.code + ' → ' + target + ' (' + site.downtime_count + ')';
            if (selectedSiteIds.includes(site.id)) {
                option.selected = true;
            }
            siteSelect.appendChild(option);
        });
    }

    async function loadSites() {
        const district = districtSelect.value;

        if (!district) {
            siteSelect.innerHTML = '<option value="" disabled>Select a district to load sites</option>';
            return;
        }

        siteSelect.innerHTML = '<option value="" disabled>Loading sites…</option>';

        try {
            const response = await fetch(sitesUrl + '?district=' + encodeURIComponent(district), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const body = await response.json();

            if (!response.ok) {
                siteSelect.innerHTML = '<option value="" disabled>' + (body.message || 'Could not load sites') + '</option>';
                return;
            }

            renderSites(body.sites || []);
        } catch (error) {
            siteSelect.innerHTML = '<option value="" disabled>Could not load sites</option>';
        }
    }

    districtSelect.addEventListener('change', () => {
        selectedSiteIds.length = 0;
        loadSites();
    });

    document.getElementById('import-select-all')?.addEventListener('change', (event) => {
        document.querySelectorAll('.import-id').forEach((box) => {
            box.checked = event.target.checked;
        });
    });

    document.getElementById('migrate-all')?.addEventListener('change', (event) => {
        document.querySelectorAll('.import-id, #import-select-all').forEach((box) => {
            box.disabled = event.target.checked;
        });
    });

    if (districtSelect.value) {
        loadSites();
    }
</script>
@endpush
