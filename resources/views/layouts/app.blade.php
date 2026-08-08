<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'MHE - Preventive Maintenance System'))</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script>
        try {
            if (localStorage.getItem('mhe-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
</head>
<body class="app-body">
    <div class="app-wrapper d-flex flex-column">
        @include('partials.topnav')
        <div class="d-flex flex-grow-1">
            @include('partials.sidebar')
            <main class="main-content w-100">
                <x-flash-messages />
                @yield('content')
                <footer class="footer-copy text-center mt-4 pb-3">
                    &copy; {{ date('Y') }} FAST Logistics — MHE - Preventive Maintenance System
                </footer>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
