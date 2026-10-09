@extends('layouts.app')
@section('title', 'User Migration Tool')
@section('content')
<x-page-header title="User Migration Tool" :breadcrumbs="['System' => null, 'User Migration Tool' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2">Imports active <strong>MHE Transaction</strong> users from fsc_web for the districts you select. New users are created in mhe-pms as Active FAST Administrators. Password is the username. Site access is copied from that user's own sites inside the selected districts.</p>
        <p class="mb-0 text-muted">Users that already exist in mhe-pms stay in the preview and are not imported. Run preview before import.</p>
    </div>
</div>

@if($connectionError)
    <div class="alert alert-warning">Could not load districts from fsc_web: {{ $connectionError }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('system.user-migration-tool.preview') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="districts">Districts from fsc_web</label>
                <select id="districts" name="districts[]" class="form-select @error('districts') is-invalid @enderror" multiple size="8" required>
                    @foreach($districts as $district)
                        <option value="{{ $district['code'] }}" @selected(in_array($district['code'], old('districts', $preview['districts'] ?? []), true))>
                            {{ $district['code'] }} — {{ $district['name'] }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Hold Ctrl to select more than one district.</div>
                @error('districts')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label" for="host">Host</label>
                        <input id="host" type="text" name="host" class="form-control" value="{{ old('host', $connection['host'] ?? '') }}" placeholder="Uses FSC_WEB_DB_HOST when blank">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="port">Port</label>
                        <input id="port" type="number" name="port" class="form-control" value="{{ old('port', $connection['port'] ?? 3306) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="database">Database</label>
                        <input id="database" type="text" name="database" class="form-control" value="{{ old('database', $connection['database'] ?? '') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="username">Username</label>
                        <input id="username" type="text" name="username" class="form-control" value="{{ old('username', $connection['username'] ?? '') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" class="form-control" placeholder="Blank uses FSC_WEB_DB_PASSWORD" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Preview</button>
            </div>
        </form>
    </div>
</div>

@if($preview)
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Will import</div><div class="fs-4">{{ $preview['metrics']['users_will_import'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Already exists</div><div class="fs-4">{{ $preview['metrics']['users_already_exist'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">No matching sites</div><div class="fs-4">{{ $preview['metrics']['users_no_matching_sites'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Also assigned outside</div><div class="fs-4">{{ $preview['metrics']['users_outside_district'] }}</div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Access after import</th>
                        <th>Sites to copy</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preview['users'] as $user)
                        <tr @class(['text-muted' => $user['status'] !== 'will_import'])>
                            <td>{{ $user['legacy_id'] }}</td>
                            <td>{{ $user['code'] }}</td>
                            <td>{{ $user['name'] }}</td>
                            <td>
                                @if($user['status'] === 'will_import')
                                    FAST Administrator, Active
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $user['site_codes'] === [] ? '—' : implode(', ', $user['site_codes']) }}
                                @if($user['unmapped_site_codes'] !== [])
                                    <div class="small text-danger">Not in mhe-pms: {{ implode(', ', $user['unmapped_site_codes']) }}</div>
                                @endif
                                @if($user['outside_site_codes'] !== [])
                                    <div class="small">Also assigned outside selected districts: {{ implode(', ', $user['outside_site_codes']) }}</div>
                                @endif
                            </td>
                            <td>
                                @switch($user['status'])
                                    @case('will_import')
                                        <span class="badge text-bg-success">Will import</span>
                                        @break
                                    @case('already_exists')
                                        <span class="badge text-bg-warning">Already exists — will not be imported</span>
                                        @break
                                    @case('email_conflict')
                                        <span class="badge text-bg-warning">Email conflict — will not be imported</span>
                                        @break
                                    @case('no_matching_sites')
                                        <span class="badge text-bg-warning">No matching sites — will not be imported</span>
                                        @break
                                    @default
                                        <span class="badge text-bg-warning">Username too long — will not be imported</span>
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No MHE Transaction users found for the selected districts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($preview['metrics']['users_will_import'] > 0)
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('system.user-migration-tool.import') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['preview_token'] }}">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="deactivate" value="1" id="deactivate" @checked(old('deactivate', $allowDeactivate)) @disabled(! $allowDeactivate)>
                            <label class="form-check-label" for="deactivate">Set imported users inactive in fsc_web</label>
                        </div>
                        @unless($allowDeactivate)
                            <div class="form-text">Deactivate is off until <code>FSC_WEB_IMPORT_ALLOW_SOURCE_DEACTIVATE=true</code>.</div>
                        @endunless
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="confirmation">Type <code>{{ $confirmationPhrase }}</code></label>
                        <input id="confirmation" type="text" name="confirmation" class="form-control @error('confirmation') is-invalid @enderror" value="{{ old('confirmation') }}" required>
                        @error('confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger">Import {{ $preview['metrics']['users_will_import'] }} user(s)</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endif
@endsection
