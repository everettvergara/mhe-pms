@props(['records', 'showSupplier' => true])

<div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead>
            <tr>
                <th>Next Schedule</th>
                <th>PMS No.</th>
                @if($showSupplier)
                    <th>Supplier</th>
                @endif
                <th>Site</th>
                <th>Status</th>
                <th>Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $pms)
                @php
                    $daysUntil = now()->startOfDay()->diffInDays($pms->next_schedule_date, false);
                    $urgencyClass = $daysUntil < 0 ? 'text-danger' : ($daysUntil <= 7 ? 'text-warning' : '');
                @endphp
                <tr data-href="{{ route('pms.show', $pms) }}">
                    <td class="{{ $urgencyClass }} fw-medium">{{ $pms->next_schedule_date?->format('Y-m-d') }}</td>
                    <td><x-pms-no-cell :pms="$pms" /></td>
                    @if($showSupplier)
                        <td><x-supplier-cell :supplier="$pms->supplier" /></td>
                    @endif
                    <td>{{ $pms->site?->site_name }}</td>
                    <td><x-status-badge :status="$pms->status" /></td>
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
