@php
    $user = auth()->user();
    $can = fn (string $permission) => $user->hasPermission($permission);

    $shortcuts = [];

    if ($can('mhe-downtimes.view')) {
        $shortcuts[] = [
            'href' => route('mhe-downtimes.index'),
            'icon' => 'bi-exclamation-triangle-fill',
            'label' => 'MHE Downtimes',
            'tone' => 'rose',
        ];
    }

    if ($can('pms.view')) {
        $shortcuts[] = [
            'href' => route('pms.index'),
            'icon' => 'bi-clipboard2-check-fill',
            'label' => 'Preventive Maintenance',
            'tone' => 'blue',
        ];
    }

    if ($can('dashboard.view')) {
        $shortcuts[] = [
            'href' => route('dashboard.pms-schedule'),
            'icon' => 'bi-calendar-event-fill',
            'label' => 'PMS Schedule',
            'tone' => 'amber',
        ];
    }

    if ($can('mhe-downtimes.view')) {
        $shortcuts[] = [
            'href' => route('mhes.summary'),
            'icon' => 'bi-bar-chart-fill',
            'label' => 'MHE Downtime Summary',
            'tone' => 'wine',
        ];
    }

    if ($can('mhe-utilization.view')) {
        $shortcuts[] = [
            'href' => route('mhes.utilization'),
            'icon' => 'bi-grid-3x3-gap-fill',
            'label' => 'MHE + PMS Site Utilization',
            'tone' => 'violet',
        ];
    }

    if ($can('mhe-downtimes.view')) {
        $shortcuts[] = [
            'href' => route('dashboard.mhe-uptime'),
            'icon' => 'bi-graph-up-arrow',
            'label' => 'MHE Uptime Summary',
            'tone' => 'green',
        ];
    }
@endphp

@if($shortcuts !== [])
    <nav class="dash-jumps" aria-label="Quick shortcuts">
        <div class="dash-jumps__kicker">
            <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
            Quick shortcuts
        </div>
        <div class="dash-jumps__row">
            @foreach($shortcuts as $shortcut)
                <a href="{{ $shortcut['href'] }}" class="dash-jump dash-jump--{{ $shortcut['tone'] }}">
                    <span class="dash-jump__mark" aria-hidden="true">
                        <i class="bi {{ $shortcut['icon'] }}"></i>
                    </span>
                    <span class="dash-jump__label">{{ $shortcut['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
