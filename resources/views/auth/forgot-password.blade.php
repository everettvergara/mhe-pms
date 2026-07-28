@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <p class="guest-intro">
        Enter your email address and we will send you a link to reset your password.
    </p>

    @if(session('status'))
        <div class="guest-alert guest-alert--success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" data-recaptcha-action="password_reset_request">
        @csrf

        <div class="mb-3">
            <label for="email" class="guest-form-label">Email <span class="required-mark">*</span></label>
            <input id="email" type="email" name="email" class="guest-form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="guest-btn-primary">
                <i class="bi bi-envelope me-1"></i>Email Password Reset Link
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="guest-link small">Back to login</a>
        </div>
    </form>
@endsection
