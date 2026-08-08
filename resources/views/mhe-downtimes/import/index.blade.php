@extends('layouts.app')
@section('title', 'FSC Web Import')
@section('content')
<x-page-header title="FSC Web Import" :breadcrumbs="['Transactions'=>null,'MHE Downtimes'=>route('mhe-downtimes.index'),'Import'=>null]" />

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('mhe-downtimes.import.create') }}" class="btn btn-primary btn-sm">Run import</a>
    <a href="{{ route('mhe-downtimes.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
</div>

<div class="card">
    <div class="card-header">Import history</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Summary</th>
                    <th>Users</th>
                    <th>Downtimes</th>
                    <th>Action plans</th>
                    <th>Warnings</th>
                    <th>Errors</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ match($batch->status?->value) { 'completed' => 'success', 'failed' => 'danger', 'running' => 'primary', default => 'secondary' } }}">
                                {{ $batch->status?->label() ?? 'Pending' }}
                            </span>
                            @if($batch->dry_run)
                                <span class="badge bg-info text-dark">Dry run</span>
                            @endif
                        </td>
                        <td>{{ $batch->source->label() }}</td>
                        <td>{{ $batch->source_summary }}</td>
                        <td>{{ $batch->users }}</td>
                        <td>{{ $batch->downtimes }}</td>
                        <td>{{ $batch->action_plans }}</td>
                        <td>{{ $batch->warnings }}</td>
                        <td>{{ $batch->errors }}</td>
                        <td>{{ $batch->creator?->name ?? '—' }}</td>
                        <td class="text-nowrap">
                            @if(in_array($batch->status?->value, ['pending', 'running'], true))
                                <a href="{{ route('mhe-downtimes.import.progress', $batch->batch_id) }}" class="btn btn-outline-primary btn-sm">Progress</a>
                            @endif
                            <a href="{{ route('mhe-downtimes.import.log', $batch->batch_id) }}" class="btn btn-outline-secondary btn-sm">Log</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No imports yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
        <div class="card-footer">{{ $batches->links() }}</div>
    @endif
</div>
@endsection
