<div class="card">
    <div class="card-header bg-white"><strong>MHE Action Plans</strong></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $group['mhe_category'] }}</td>
                        <td>{{ $group['count'] }}</td>
                        <td>
                            @foreach($group['mhes'] as $mhe)
                                <div class="mb-2">
                                    <div class="fw-medium">{{ $mhe['header'] }}</div>
                                    @foreach($mhe['plans'] as $plan)
                                        <div class="small">
                                            <span class="badge {{ $plan['highlight_class'] }}">{{ $plan['name'] }}</span>
                                            {{ $plan['date'] }} — {{ $plan['responsible'] }} — {{ $plan['text'] }}
                                        </div>
                                    @endforeach
                                    @if($mhe['no_action_plan'])
                                        <span class="badge text-bg-danger">No Action Plan</span>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
