<div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead>
            <tr>
                <th>Site</th>
                <th>Supplier</th>
                <th>Unit</th>
                <th>PMS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($units as $unit)
                <tr>
                    <td>{{ $unit->siteRelation?->site_name ?? $unit->site ?? '—' }}</td>
                    <td><x-supplier-cell :supplier="$unit->supplier" /></td>
                    <td>{{ $unit->unit_no ?? '—' }}</td>
                    <td>
                        @if($unit->pms_this_month)
                            <span class="badge rounded-pill admin-pms-yes">Yes</span>
                        @else
                            <span class="badge rounded-pill admin-pms-no">No</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">No units.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
