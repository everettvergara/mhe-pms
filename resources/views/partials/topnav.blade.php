<nav class="top-navbar d-flex align-items-center">
    <button class="btn btn-link sidebar-toggle me-2" type="button"
            id="sidebarToggle" aria-controls="sidebarMenu" aria-expanded="true"
            aria-label="Hide admin panel" title="Hide admin panel">
        <i class="bi bi-layout-sidebar-inset sidebar-toggle__icon sidebar-toggle__icon--open" aria-hidden="true"></i>
        <i class="bi bi-list sidebar-toggle__icon sidebar-toggle__icon--closed d-none" aria-hidden="true"></i>
    </button>
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
        <img src="{{ asset('images/fast-logo-color.png') }}" alt="FAST" class="fast-logo">
        <span class="site-title d-none d-sm-inline">MHE - Preventive Maintenance System</span>
    </a>
    <div class="ms-auto d-flex align-items-center">
        <div class="dropdown">
            <button class="btn btn-link text-decoration-none dropdown-toggle d-flex align-items-center gap-1 p-0 border-0"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                @if (auth()->user()->profilePictureUrl())
                    <img src="{{ auth()->user()->profilePictureUrl() }}" alt="" class="rounded-circle" style="object-fit: cover;">
                @else
                    <i class="bi bi-person-circle"></i>
                @endif
                <span class="site-title d-none d-md-inline">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end topnav-user-menu">
                @if (auth()->user()->hasPermission('profile.manage'))
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">User Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
