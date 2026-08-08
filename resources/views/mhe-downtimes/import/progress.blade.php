@extends('layouts.app')
@section('title', 'FSC Web Import Progress')
@section('content')
<x-page-header title="FSC Web Import" :breadcrumbs="['Transactions'=>null,'MHE Downtimes'=>route('mhe-downtimes.index'),'Import'=>route('mhe-downtimes.import.index'),'Progress'=>null]" />

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="fw-semibold" id="phase-label">Preparing import…</div>
                <div class="text-muted small" id="status-message">Waiting for queue worker to start.</div>
            </div>
            <div class="text-end">
                <span class="badge bg-secondary" id="status-badge">{{ $batch->status?->label() ?? 'Pending' }}</span>
                @if($batch->dry_run)
                    <span class="badge bg-info text-dark">Dry run</span>
                @endif
            </div>
        </div>

        <div class="progress mb-2" style="height: 1.5rem;">
            <div
                id="progress-bar"
                class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar"
                style="width: 0%"
                aria-valuenow="0"
                aria-valuemin="0"
                aria-valuemax="100"
            >0%</div>
        </div>

        <div class="text-muted small" id="progress-counts"></div>

        <div class="alert alert-warning mt-3 mb-0" id="running-notice">
            Import is running in the background. Keep this page open until it completes.
            Ensure a queue worker is running: <code>php artisan queue:work</code>
        </div>
    </div>
</div>

<div id="result-panel" class="d-none">
    <div class="alert alert-info">
        <div class="fw-semibold mb-2" id="result-title">Import finished</div>
        <ul class="mb-2" id="result-list"></ul>
        <a href="#" id="download-log-link" class="btn btn-sm btn-outline-primary d-none">Download import log</a>
    </div>
</div>

<div id="error-panel" class="d-none">
    <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Import failed</div>
        <p class="mb-2" id="error-message"></p>
        <a href="{{ route('mhe-downtimes.import.create') }}" class="btn btn-sm btn-secondary">Back to import form</a>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('mhe-downtimes.import.index') }}" class="btn btn-secondary btn-sm">Import history</a>
    <a href="{{ route('mhe-downtimes.import.create') }}" class="btn btn-outline-secondary btn-sm">Run another import</a>
</div>

<script>
    const batchId = @json($batch->batch_id);
    const statusUrl = @json(route('mhe-downtimes.import.status', $batch->batch_id));
    const logUrlTemplate = @json(route('mhe-downtimes.import.log', ['batchId' => '__BATCH__']));
    const progressBar = document.getElementById('progress-bar');
    const phaseLabel = document.getElementById('phase-label');
    const statusMessage = document.getElementById('status-message');
    const statusBadge = document.getElementById('status-badge');
    const progressCounts = document.getElementById('progress-counts');
    const runningNotice = document.getElementById('running-notice');
    const resultPanel = document.getElementById('result-panel');
    const resultList = document.getElementById('result-list');
    const resultTitle = document.getElementById('result-title');
    const downloadLogLink = document.getElementById('download-log-link');
    const errorPanel = document.getElementById('error-panel');
    const errorMessage = document.getElementById('error-message');

    let pollTimer = null;

    function statusLabel(value) {
        return ({
            pending: 'Pending',
            running: 'Running',
            completed: 'Completed',
            failed: 'Failed',
        })[value] ?? value;
    }

    function badgeClass(value) {
        return ({
            pending: 'bg-secondary',
            running: 'bg-primary',
            completed: 'bg-success',
            failed: 'bg-danger',
        })[value] ?? 'bg-secondary';
    }

    function renderResult(result, dryRun) {
        resultTitle.textContent = dryRun ? 'Dry run complete' : 'Import complete';
        const items = [
            ['Users purged', result.users_purged ?? 0],
            ['Default users created', result.default_users_created ?? 0],
            ['Users imported', result.users ?? 0],
            ['Users skipped', result.users_skipped ?? 0],
            ['Downtimes purged', result.downtimes_purged ?? 0],
            ['Action plans purged', result.action_plans_purged ?? 0],
            ['Downtimes imported', result.downtimes ?? 0],
            ['Action plans imported', result.action_plans ?? 0],
            ['Downtime attachments', result.downtime_attachments ?? 0],
            ['Action plan attachments', result.action_plan_attachments ?? 0],
            ['Warnings', result.warnings ?? 0],
            ['Errors', result.errors ?? 0],
        ];

        resultList.innerHTML = items
            .map(([label, value]) => `<li>${label}: ${value}</li>`)
            .join('');

        if (! dryRun && batchId) {
            downloadLogLink.href = logUrlTemplate.replace('__BATCH__', batchId);
            downloadLogLink.classList.remove('d-none');
        }
    }

    function updateUi(payload) {
        const percent = payload.progress_percent ?? 0;
        progressBar.style.width = `${percent}%`;
        progressBar.textContent = `${percent}%`;
        progressBar.setAttribute('aria-valuenow', String(percent));

        phaseLabel.textContent = payload.phase_label ?? 'Working…';
        statusMessage.textContent = payload.status_message ?? '';
        statusBadge.textContent = statusLabel(payload.status);
        statusBadge.className = `badge ${badgeClass(payload.status)}`;

        if (payload.total_count > 0) {
            progressCounts.textContent = `${payload.processed_count ?? 0} / ${payload.total_count}`;
        } else {
            progressCounts.textContent = '';
        }

        if (payload.status === 'running') {
            progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
        } else {
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        }

        if (payload.status === 'completed') {
            runningNotice.classList.add('d-none');
            resultPanel.classList.remove('d-none');
            renderResult(payload.result ?? {}, payload.dry_run);
            clearInterval(pollTimer);
        }

        if (payload.status === 'failed') {
            runningNotice.classList.add('d-none');
            errorPanel.classList.remove('d-none');
            errorMessage.textContent = payload.error_message ?? 'An unknown error occurred.';
            clearInterval(pollTimer);
        }
    }

    async function pollStatus() {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
            });

            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            updateUi(payload);
        } catch (error) {
            statusMessage.textContent = 'Unable to fetch import status. Retrying…';
        }
    }

    pollStatus();
    pollTimer = setInterval(pollStatus, 2000);
</script>
@endsection
