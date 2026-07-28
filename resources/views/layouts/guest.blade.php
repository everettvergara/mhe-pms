<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — {{ config('app.name', 'MHE - Preventive Maintenance System') }}</title>
    @if (config('recaptcha.site_key'))
        <meta name="recaptcha-site-key" content="{{ config('recaptcha.site_key') }}">
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}" async defer></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-page">
    <div class="guest-scene min-vh-100 d-flex flex-column align-items-center justify-content-center py-5">
        <div class="guest-brand-above">
            <img src="{{ asset('images/fast-logo-color.png') }}" alt="FAST" class="fast-logo fast-logo--guest">
            <p class="mb-0 site-title">MHE - Preventive Maintenance System</p>
        </div>
        <div class="guest-card">
            <div class="guest-card__body">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
