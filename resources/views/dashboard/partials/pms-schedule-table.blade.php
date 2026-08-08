@props(['records', 'showSupplier' => true])

<div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead>
            <tr>
                <th>Next Schedule</th>
                <th>Unit</th>
                <th>Last PMS</th>
                @if($showSupplier)
                    <th>Supplier</th>
                @endif
                <th>Site</th>
                <th>Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $inventory)
                @php
                    $daysUntil = now()->startOfDay()->diffInDays($inventory->next_pms_date, false);
                    $urgencyClass = $daysUntil < 0 ? 'text-danger' : ($daysUntil <= 7 ? 'text-warning' : '');
                @endphp
                <tr data-href="{{ route('mhe-inventories.show', $inventory) }}">
                    <td class="{{ $urgencyClass }} fw-medium">{{ $inventory->next_pms_date?->format('Y-m-d') }}</td>
                    <td>
                        <div>{{ $inventory->unit_no ?? '—' }}</div>
                        <div class="text-muted small">{{ $inventory->mheType?->code ?? '—' }}</div>
                    </td>
                    <td>
                        @if($inventory->lastPmsHeader)
                            <a href="{{ route('pms.show', $inventory->lastPmsHeader) }}" onclick="event.stopPropagation()">
                                {{ $inventory->lastPmsHeader->pms_no }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    @if($showSupplier)
                        <td><x-supplier-cell :supplier="$inventory->supplier" /></td>
                    @endif
                    <td>{{ $inventory->siteRelation?->site_name ?? $inventory->site ?? '—' }}</td>
                    <td class="{{ $urgencyClass }}">
                        @if($daysUntil < 0)
                            {{ abs($daysUntil) }} day(s) overdue
                        @elseif($daysUntil === 0)
                            Due today
                        @else
                            In {{ $daysUntil }} day(s)
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showSupplier ? 6 : 5 }}" class="text-center text-muted py-3">No scheduled PMS records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
