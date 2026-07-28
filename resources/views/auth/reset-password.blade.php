@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" data-recaptcha-action="password_reset">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="guest-form-label">Email <span class="required-mark">*</span></label>
            <input id="email" type="email" name="email" class="guest-form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="guest-form-label">New Password <span class="required-mark">*</span></label>
            <input id="password" type="password" name="password" class="guest-form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="guest-form-label">Confirm Password <span class="required-mark">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="guest-form-control"
                   required autocomplete="new-password">
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="guest-btn-primary">
                <i class="bi bi-key me-1"></i>Reset Password
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="guest-link small">Back to login</a>
        </div>
    </form>
@endsection
