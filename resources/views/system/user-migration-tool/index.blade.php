@extends('layouts.app')
@section('title', 'User Migration Tool')
@section('content')
<x-page-header title="User Migration Tool" :breadcrumbs="['System' => null, 'User Migration Tool' => null]" />

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2">Imports active <strong>MHE Transaction</strong> users from fsc_web for the districts you select. New users are created in mhe-pms as Active FAST Administrators. Password is the username. Choose which users to import and which of their in-district fsc_web sites (matched in mhe-pms) to copy.</p>
        <p class="mb-0 text-muted">Users that already exist in mhe-pms stay in the preview and are not imported. Run preview before import.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">fsc_web connection loaded by the app</div>
    <div class="card-body">
        <dl class="row mb-3">
            <dt class="col-sm-3">FSC_WEB_DB_HOST</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['host'] !== null && $fscConnection['host'] !== '' ? $fscConnection['host'] : '(empty)' }}</code></dd>
            <dt class="col-sm-3">FSC_WEB_DB_PORT</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['port'] !== null && $fscConnection['port'] !== '' ? $fscConnection['port'] : '(empty)' }}</code></dd>
            <dt class="col-sm-3">FSC_WEB_DB_DATABASE</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['database'] !== null && $fscConnection['database'] !== '' ? $fscConnection['database'] : '(empty)' }}</code></dd>
            <dt class="col-sm-3">FSC_WEB_DB_USERNAME</dt>
            <dd class="col-sm-9"><code>{{ $fscConnection['username'] !== null && $fscConnection['username'] !== '' ? $fscConnection['username'] : '(empty)' }}</code></dd>
            <dt class="col-sm-3">FSC_WEB_DB_PASSWORD</dt>
            <dd class="col-sm-9"><code>{{ ($fscConnection['password'] ?? '') !== '' ? $fscConnection['password'] : '(empty)' }}</code></dd>
        </dl>
        <form method="POST" action="{{ route('system.user-migration-tool.test-connection') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">Test connection</button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('system.user-migration-tool.preview') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="districts">Districts</label>
                <select id="districts" name="districts[]" class="form-select @error('districts') is-invalid @enderror" multiple size="8" required>
                    @foreach($districts as $district)
                        <option value="{{ $district->district_code }}" @selected(in_array($district->district_code, old('districts', $preview['districts'] ?? []), true))>
                            {{ $district->district_code }} — {{ $district->district_name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Hold Ctrl to select more than one district.</div>
                @error('districts')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
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

    @php
        $defaultImportUserIds = collect($preview['users'])
            ->where('status', 'will_import')
            ->pluck('legacy_id')
            ->all();
        $selectedImportUserIds = array_map('intval', (array) old('import_users', $defaultImportUserIds));
    @endphp

    @if($preview['metrics']['users_will_import'] > 0)
        <form method="POST" action="{{ route('system.user-migration-tool.import') }}" data-user-migration-import>
            @csrf
            <input type="hidden" name="preview_token" value="{{ $preview['preview_token'] }}">

            <div class="card mb-3">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 3rem;">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="import-users-select-all"
                                        data-import-select-all
                                        title="Select all eligible users"
                                    >
                                </th>
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
                                @php
                                    $canImport = $user['status'] === 'will_import';
                                    $defaultSiteCodes = collect($user['sites'] ?? [])
                                        ->where('mapped', true)
                                        ->pluck('code')
                                        ->all();
                                    $oldSitesForUser = old('import_sites.'.$user['legacy_id']);
                                    $checkedSiteCodes = $oldSitesForUser !== null
                                        ? (array) $oldSitesForUser
                                        : $defaultSiteCodes;
                                    $userChecked = $canImport && in_array($user['legacy_id'], $selectedImportUserIds, true);
                                @endphp
                                <tr @class(['text-muted' => ! $canImport]) data-import-user-row @if($canImport) data-import-eligible @endif>
                                    <td class="text-center align-middle">
                                        @if($canImport)
                                            <input
                                                type="checkbox"
                                                class="form-check-input import-user-checkbox"
                                                name="import_users[]"
                                                value="{{ $user['legacy_id'] }}"
                                                data-import-user
                                                @checked($userChecked)
                                            >
                                        @endif
                                    </td>
                                    <td>{{ $user['legacy_id'] }}</td>
                                    <td>{{ $user['code'] }}</td>
                                    <td>{{ $user['name'] }}</td>
                                    <td>
                                        @if($canImport)
                                            FAST Administrator, Active
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if(($user['sites'] ?? []) === [])
                                            —
                                        @else
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($user['sites'] as $site)
                                                    <div class="form-check mb-0">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input import-site-checkbox"
                                                            name="import_sites[{{ $user['legacy_id'] }}][]"
                                                            value="{{ $site['code'] }}"
                                                            id="import_site_{{ $user['legacy_id'] }}_{{ $site['code'] }}"
                                                            data-import-site
                                                            data-import-user-id="{{ $user['legacy_id'] }}"
                                                            @checked(in_array($site['code'], $checkedSiteCodes, true))
                                                            @disabled(! $site['mapped'] || ! $canImport)
                                                        >
                                                        <label
                                                            class="form-check-label @unless($site['mapped']) text-danger @endunless"
                                                            for="import_site_{{ $user['legacy_id'] }}_{{ $site['code'] }}"
                                                        >
                                                            {{ $site['code'] }}
                                                            @unless($site['mapped'])
                                                                <span class="small">(not in mhe-pms)</span>
                                                            @endunless
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($user['outside_site_codes'] !== [])
                                            <div class="small mt-1">Also assigned outside selected districts: {{ implode(', ', $user['outside_site_codes']) }}</div>
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
                                <tr><td colspan="7" class="text-center text-muted py-4">No MHE Transaction users found for the selected districts.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @error('import_users')
                    <div class="card-body pt-0 text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="card">
                <div class="card-body row g-3">
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
                        <button type="submit" class="btn btn-danger" data-import-submit>
                            Import <span data-import-count>{{ count(array_intersect($selectedImportUserIds, $defaultImportUserIds)) }}</span> user(s)
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
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
                            <tr class="text-muted">
                                <td>{{ $user['legacy_id'] }}</td>
                                <td>{{ $user['code'] }}</td>
                                <td>{{ $user['name'] }}</td>
                                <td>—</td>
                                <td>
                                    @if(($user['sites'] ?? []) === [])
                                        —
                                    @else
                                        {{ implode(', ', array_column($user['sites'], 'code')) }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-bg-warning">{{ $user['status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No MHE Transaction users found for the selected districts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview['metrics']['users_will_import'] > 0)
        @push('scripts')
            <script>
                (function () {
                    const form = document.querySelector('[data-user-migration-import]');
                    if (!form) {
                        return;
                    }

                    const selectAll = form.querySelector('[data-import-select-all]');
                    const userCheckboxes = () => [...form.querySelectorAll('[data-import-user]')];
                    const countEl = form.querySelector('[data-import-count]');

                    function syncSiteDisabledState(userCheckbox) {
                        const legacyId = userCheckbox.value;
                        form.querySelectorAll('[data-import-site][data-import-user-id="' + legacyId + '"]').forEach((siteBox) => {
                            if (!siteBox.disabled) {
                                siteBox.disabled = !userCheckbox.checked;
                            }
                        });
                    }

                    function updateImportCount() {
                        const count = userCheckboxes().filter((box) => box.checked).length;
                        if (countEl) {
                            countEl.textContent = String(count);
                        }
                        if (selectAll) {
                            const boxes = userCheckboxes();
                            selectAll.checked = boxes.length > 0 && boxes.every((box) => box.checked);
                            selectAll.indeterminate = boxes.some((box) => box.checked) && !selectAll.checked;
                        }
                    }

                    userCheckboxes().forEach((box) => {
                        syncSiteDisabledState(box);
                        box.addEventListener('change', () => {
                            syncSiteDisabledState(box);
                            updateImportCount();
                        });
                    });

                    if (selectAll) {
                        selectAll.addEventListener('change', () => {
                            userCheckboxes().forEach((box) => {
                                box.checked = selectAll.checked;
                                syncSiteDisabledState(box);
                            });
                            updateImportCount();
                        });
                    }

                    form.addEventListener('submit', (event) => {
                        userCheckboxes().forEach((box) => {
                            if (!box.checked) {
                                box.disabled = true;
                            }
                        });
                        form.querySelectorAll('[data-import-site]').forEach((siteBox) => {
                            const userBox = form.querySelector('[data-import-user][value="' + siteBox.dataset.importUserId + '"]');
                            if (userBox && !userBox.checked) {
                                siteBox.disabled = true;
                            }
                        });
                    });

                    updateImportCount();
                })();
            </script>
        @endpush
    @endif
@endif
@endsection
