@extends('layouts.app')
@section('title', 'Run FSC Web Import')
@section('content')
<x-page-header title="Run FSC Web Import" :breadcrumbs="['Transactions'=>null,'MHE Downtimes'=>route('mhe-downtimes.index'),'Import'=>route('mhe-downtimes.import.index'),'Run'=>null]" />

<div class="alert alert-info mb-3">
    Imports run in the background. After submitting, you will be redirected to a progress page.
    Ensure a queue worker is running: <code>php artisan queue:work</code>
</div>

<div class="alert alert-danger">
    <div class="fw-semibold mb-2">Destructive import</div>
    <p class="mb-2">A live import (without dry run) will permanently delete:</p>
    <ul class="mb-2">
        <li>All users in mhe-pms (including your current session user)</li>
        <li>All MHE downtime records</li>
        <li>All MHE downtime action plans and their attachments</li>
    </ul>
    <p class="mb-0">
        Default users are recreated first (<code>admin</code> / <code>admin</code>, supplier users / <code>password</code>),
        then MHE-access users are imported from fsc_web with their existing password hashes and Eagle Eye site assignments (when assigned).
    </p>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="text-muted mb-0">
            Import MHE-access users, downtimes, and action plans from the live Eagle Eye MySQL database (fsc_web).
            Passwords are copied as bcrypt hashes. Site assignments are copied from Eagle Eye when present;
            users with no Eagle Eye site rows are still imported without site assignment.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('mhe-downtimes.import.store') }}" class="row g-3" id="fsc-import-form">
            @csrf

            <div class="col-12">
                <label class="form-label">Import targets</label>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input type="checkbox" name="import_users" value="1" class="form-check-input" id="import-users" @checked(old('import_users', true))>
                        <label class="form-check-label" for="import-users">Users (MHE access)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="import_downtimes" value="1" class="form-check-input" id="import-downtimes" @checked(old('import_downtimes', true))>
                        <label class="form-check-label" for="import-downtimes">MHE downtimes and action plans</label>
                    </div>
                </div>
                @error('import_users')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Host</label>
                        <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host', $mysqlDefaults['host'] ?? '') }}">
                        @error('host')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', $mysqlDefaults['port'] ?? 3306) }}">
                        @error('port')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Database</label>
                        <input type="text" name="database" class="form-control @error('database') is-invalid @enderror" value="{{ old('database', $mysqlDefaults['database'] ?? '') }}">
                        @error('database')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $mysqlDefaults['username'] ?? '') }}">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="dry_run" value="1" class="form-check-input" id="dry-run" @checked(old('dry_run'))>
                    <label class="form-check-label" for="dry-run">Dry run (validate only, rollback all changes)</label>
                </div>
            </div>

            <div class="col-12" id="confirmation-panel">
                <label class="form-label" for="confirmation">Type <code>{{ $confirmationPhrase }}</code> to confirm a live import</label>
                <input
                    type="text"
                    name="confirmation"
                    id="confirmation"
                    class="form-control @error('confirmation') is-invalid @enderror"
                    value="{{ old('confirmation') }}"
                    autocomplete="off"
                >
                @error('confirmation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-danger" id="submit-import">Run import</button>
                <a href="{{ route('mhe-downtimes.import.index') }}" class="btn btn-secondary">Import history</a>
            </div>
        </form>
    </div>
</div>

<script>
    const dryRunCheckbox = document.getElementById('dry-run');
    const confirmationPanel = document.getElementById('confirmation-panel');
    const confirmationInput = document.getElementById('confirmation');
    const submitButton = document.getElementById('submit-import');

    function syncConfirmationState() {
        const dryRun = dryRunCheckbox.checked;
        confirmationPanel.classList.toggle('d-none', dryRun);
        confirmationInput.required = !dryRun;
        submitButton.textContent = dryRun ? 'Run dry-run' : 'Run destructive import';
        submitButton.classList.toggle('btn-danger', !dryRun);
        submitButton.classList.toggle('btn-primary', dryRun);
    }

    dryRunCheckbox.addEventListener('change', syncConfirmationState);
    syncConfirmationState();
</script>
@endsection
