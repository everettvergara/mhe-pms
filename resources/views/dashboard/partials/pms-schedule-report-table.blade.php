<div class="table-responsive">
    <table class="table table-sm table-hover mb-0" id="pms-schedule-report">
        <thead>
            <tr>
                <th>Unit</th>
                <th>MHE Type</th>
                <th>Serviced</th>
                <th>PMS Date</th>
                <th>Last Serviced</th>
                <th>Next Service</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $districtIndex => $district)
                @php
                    $districtUnits = collect($district['sites'])->sum(
                        fn (array $site): int => collect($site['suppliers'])->sum(
                            fn (array $supplier): int => count($supplier['units'])
                        )
                    );
                    $districtKey = 'district-'.$districtIndex;
                @endphp
                <tr class="table-light">
                    <td colspan="6" class="fw-semibold">
                        <button
                            type="button"
                            class="btn btn-sm p-0 border-0 bg-transparent text-body fw-semibold text-start w-100 pms-schedule-toggle"
                            data-target="{{ $districtKey }}"
                            aria-expanded="false"
                        >
                            <i class="bi bi-chevron-right me-1 pms-schedule-chevron"></i>
                            District: {{ $district['name'] }} ({{ $districtUnits }})
                        </button>
                    </td>
                </tr>
                @foreach($district['sites'] as $siteIndex => $site)
                    @php
                        $siteUnits = collect($site['suppliers'])->sum(
                            fn (array $supplier): int => count($supplier['units'])
                        );
                        $siteKey = $districtKey.'-site-'.$siteIndex;
                    @endphp
                    <tr class="d-none" data-parent="{{ $districtKey }}">
                        <td colspan="6" class="fw-semibold ps-3">
                            <button
                                type="button"
                                class="btn btn-sm p-0 border-0 bg-transparent text-body fw-semibold text-start w-100 pms-schedule-toggle"
                                data-target="{{ $siteKey }}"
                                aria-expanded="false"
                            >
                                <i class="bi bi-chevron-right me-1 pms-schedule-chevron"></i>
                                Site: {{ $site['name'] }} ({{ $siteUnits }})
                            </button>
                        </td>
                    </tr>
                    @foreach($site['suppliers'] as $supplier)
                        <tr class="d-none" data-parent="{{ $siteKey }}">
                            <td colspan="6" class="ps-4">
                                Supplier: {{ $supplier['name'] }} ({{ count($supplier['units']) }})
                            </td>
                        </tr>
                        @foreach($supplier['units'] as $unit)
                            <tr class="d-none" data-parent="{{ $siteKey }}">
                                <td class="ps-5">{{ $unit['unit_no'] }}</td>
                                <td>{{ $unit['mhe_type'] }}</td>
                                <td>
                                    @if($unit['serviced'])
                                        <span class="badge rounded-pill text-bg-success">Yes</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-danger">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($unit['serviced'])
                                        <div>{{ $unit['pms_date'] }}</div>
                                        @if($unit['pms_id'])
                                            <a href="{{ route('pms.show', $unit['pms_id']) }}">{{ $unit['pms_no'] }}</a>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $unit['serviced'] ? '—' : ($unit['last_serviced'] ?? '—') }}</td>
                                <td>{{ $unit['serviced'] ? '—' : ($unit['next_service'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">No units for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const report = document.getElementById('pms-schedule-report');

        if (!report) {
            return;
        }

        const setExpanded = (button, expanded) => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            const chevron = button.querySelector('.pms-schedule-chevron');

            if (!chevron) {
                return;
            }

            chevron.classList.toggle('bi-chevron-right', !expanded);
            chevron.classList.toggle('bi-chevron-down', expanded);
        };

        const hideGroup = (key) => {
            report.querySelectorAll(`[data-parent="${key}"]`).forEach((row) => {
                row.classList.add('d-none');
                row.querySelectorAll('.pms-schedule-toggle').forEach((child) => {
                    setExpanded(child, false);

                    if (child.dataset.target) {
                        hideGroup(child.dataset.target);
                    }
                });
            });
        };

        report.querySelectorAll('.pms-schedule-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.target;

                if (!key) {
                    return;
                }

                if (button.getAttribute('aria-expanded') === 'true') {
                    setExpanded(button, false);
                    hideGroup(key);
                    return;
                }

                setExpanded(button, true);
                report.querySelectorAll(`[data-parent="${key}"]`).forEach((row) => {
                    row.classList.remove('d-none');
                });
            });
        });
    });
</script>
@endpush
