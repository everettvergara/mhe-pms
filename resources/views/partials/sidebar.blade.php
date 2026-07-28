@php
    $user = auth()->user();
    $can = fn (string $permission) => $user->hasPermission($permission);
    $active = fn (string ...$patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p)) ? 'active' : '';
@endphp
<aside class="sidebar collapse d-lg-block" id="sidebarMenu">
    <nav class="p-3">
        @if($can('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="nav-link {{ $active('dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            <a href="{{ route('dashboard.pms-schedule') }}" class="nav-link {{ $active('dashboard.pms-schedule') }}">
                <i class="bi bi-calendar-event me-2"></i>PMS Schedule
            </a>
        @endif

        @if($can('pms.view') || $can('action-plans.view') || $can('action-plans.confirm'))
            <div class="menu-group">Transactions</div>
            @if($can('pms.view'))
                <a href="{{ route('pms.index') }}" class="nav-link {{ $active('pms.*') }}">
                    <i class="bi bi-clipboard-check me-2"></i>Preventive Maintenance
                </a>
            @endif
            @if($can('action-plans.view'))
                <a href="{{ route('action-plans.index') }}" class="nav-link {{ $active('action-plans.*') }}">
                    <i class="bi bi-list-task me-2"></i>Action Plans
                </a>
            @endif
            @if($can('action-plans.confirm'))
                <a href="{{ route('action-plan-confirmations.index') }}" class="nav-link {{ $active('action-plan-confirmations.*') }}">
                    <i class="bi bi-check2-square me-2"></i>Action Plan Confirmation
                </a>
            @endif
        @endif

        @if($can('suppliers.view') || $can('districts.view') || $can('sites.view') || $can('mhe-types.view') || $can('checklist-groups.view') || $can('checklist-items.view'))
            <div class="menu-group">Masters</div>
            @if($can('suppliers.view'))
                <a href="{{ route('suppliers.index') }}" class="nav-link {{ $active('suppliers.*') }}">
                    <i class="bi bi-building me-2"></i>Suppliers
                </a>
            @endif
            @if($can('districts.view'))
                <a href="{{ route('districts.index') }}" class="nav-link {{ $active('districts.*') }}">
                    <i class="bi bi-map me-2"></i>Districts
                </a>
            @endif
            @if($can('sites.view'))
                <a href="{{ route('sites.index') }}" class="nav-link {{ $active('sites.*') }}">
                    <i class="bi bi-geo-alt me-2"></i>Sites
                </a>
            @endif
            @if($can('mhe-types.view'))
                <a href="{{ route('mhe-types.index') }}" class="nav-link {{ $active('mhe-types.*') }}">
                    <i class="bi bi-truck me-2"></i>MHE Types
                </a>
            @endif
            @if($can('checklist-groups.view'))
                <a href="{{ route('checklist-groups.index') }}" class="nav-link {{ $active('checklist-groups.*') }}">
                    <i class="bi bi-folder me-2"></i>Checklist Groups
                </a>
            @endif
            @if($can('checklist-items.view'))
                <a href="{{ route('checklist-items.index') }}" class="nav-link {{ $active('checklist-items.*') }}">
                    <i class="bi bi-card-checklist me-2"></i>Checklist Items
                </a>
            @endif
        @endif

        @if($can('users.view') || $can('roles.view'))
            <div class="menu-group">Administration</div>
            @if($can('users.view'))
                <a href="{{ route('users.index') }}" class="nav-link {{ $active('users.*') }}">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            @endif
            @if($can('roles.view'))
                <a href="{{ route('roles.index') }}" class="nav-link {{ $active('roles.*') }}">
                    <i class="bi bi-shield-lock me-2"></i>User Access / Roles
                </a>
            @endif
        @endif

        @if($can('reports.view'))
            <div class="menu-group">Reports</div>
            @foreach($reportTypes ?? [] as $slug => $reportTitle)
                <a href="{{ route('reports.show', $slug) }}" class="nav-link {{ $active('reports.show') && request()->route('type') === $slug ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>{{ $reportTitle }}
                </a>
            @endforeach
        @endif

        <div class="menu-group">System</div>
        @if($can('activity-logs.view'))
            <a href="{{ route('activity-logs.index') }}" class="nav-link {{ $active('activity-logs.*') }}">
                <i class="bi bi-clock-history me-2"></i>Activity Logs
            </a>
        @endif
        @if($can('profile.manage'))
            <a href="{{ route('profile.edit') }}" class="nav-link {{ $active('profile.*') }}">
                <i class="bi bi-person me-2"></i>My Profile
            </a>
            <a href="{{ route('password.edit') }}" class="nav-link {{ $active('password.*') }}">
                <i class="bi bi-key me-2"></i>Change Password
            </a>
        @endif
    </nav>
</aside>
