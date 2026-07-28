@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    @if(session('status'))
        <div class="guest-alert guest-alert--success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" data-recaptcha-action="login">
        @csrf

        <div class="mb-3">
            <label for="login" class="guest-form-label">Username or Email <span class="required-mark">*</span></label>
            <input id="login" type="text" name="login" class="guest-form-control @error('login') is-invalid @enderror"
                   value="{{ old('login') }}" required autofocus autocomplete="username">
            @error('login')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-2">
            <label for="password" class="guest-form-label">Password <span class="required-mark">*</span></label>
            <input id="password" type="password" name="password" class="guest-form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('password.request') }}" class="guest-link small">Forgot password?</a>
        </div>

        <div class="mb-3 guest-check">
            <input type="checkbox" class="guest-check-input" id="remember_me" name="remember">
            <label class="guest-check-label" for="remember_me">Remember me</label>
        </div>

        <div class="d-grid">
            <button type="submit" class="guest-btn-primary">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
            </button>
        </div>
    </form>
@endsection
