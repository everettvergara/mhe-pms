<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
        <strong>MHE Action Plans</strong>
        <span class="text-muted small">{{ $filters['date_from'] }} to {{ $filters['date_to'] }}</span>
    </div>
    @if($groups->isEmpty())
        <p class="text-center text-muted py-3 mb-0">No records found.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td class="p-0">
                                <button
                                    type="button"
                                    class="action-plan-toggle btn btn-link text-decoration-none text-body w-100 text-start px-3 py-2 d-flex align-items-center gap-2 collapsed bg-light"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $group['category_key'] }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $group['category_key'] }}"
                                >
                                    <i class="bi bi-chevron-right"></i>
                                    <span class="fw-semibold">{{ $group['mhe_category'] }}</span>
                                    <span class="badge text-bg-secondary">{{ $group['count'] }}</span>
                                </button>
                                <div class="collapse" id="{{ $group['category_key'] }}">
                                    @foreach($group['statuses'] as $statusGroup)
                                        @php
                                            $statusId = $group['category_key'].'-'.$statusGroup['status_key'];
                                            $badgeClass = match ($statusGroup['status']) {
                                                'No Action Plan', 'Rejected' => 'text-bg-danger',
                                                'Pending' => 'text-bg-warning',
                                                'Waiting for FAST Confirmation' => 'text-bg-info',
                                                'Confirmed' => 'text-bg-success',
                                                default => 'text-bg-secondary',
                                            };
                                        @endphp
                                        <div class="border-top">
                                            <button
                                                type="button"
                                                class="action-plan-toggle btn btn-link text-decoration-none text-body w-100 text-start ps-4 pe-3 py-2 d-flex align-items-center gap-2 collapsed"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $statusId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $statusId }}"
                                            >
                                                <i class="bi bi-chevron-right"></i>
                                                <span>{{ $statusGroup['status'] }}</span>
                                                <span class="badge {{ $badgeClass }}">{{ $statusGroup['count'] }}</span>
                                            </button>
                                            <div class="collapse" id="{{ $statusId }}">
                                                <div class="table-responsive border-top">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Site</th>
                                                                <th>Unit</th>
                                                                <th>Supplier</th>
                                                                <th>MHE Type</th>
                                                                <th>MHE Downtime No</th>
                                                                <th>Date</th>
                                                                <th>What</th>
                                                                <th>Root Cause</th>
                                                                <th>Description</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($statusGroup['rows'] as $row)
                                                                <tr>
                                                                    <td>{{ $row['site'] ?: '—' }}</td>
                                                                    <td>{{ $row['unit'] ?: '—' }}</td>
                                                                    <td>{{ $row['supplier'] ?: '—' }}</td>
                                                                    <td>{{ $row['mhe_type'] ?: '—' }}</td>
                                                                    <td>
                                                                        <a href="{{ route('mhe-downtimes.show', $row['downtime_id']) }}">{{ $row['downtime_id'] }}</a>
                                                                    </td>
                                                                    <td>{{ $row['date'] }}</td>
                                                                    <td class="action-plan-wrap">{{ $row['what'] ?: '—' }}</td>
                                                                    <td class="action-plan-wrap">{{ $row['root_cause'] ?: '—' }}</td>
                                                                    <td class="action-plan-wrap">{{ $row['description'] ?: '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<style>
    .action-plan-toggle .bi-chevron-right {
        display: inline-block;
        transition: transform .15s ease;
    }

    .action-plan-toggle:not(.collapsed) .bi-chevron-right {
        transform: rotate(90deg);
    }

    .action-plan-wrap {
        max-width: 16rem;
        white-space: normal;
        word-break: break-word;
    }
</style>
