@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <form method="POST" action="{{ route('register') }}" data-recaptcha-action="register">
        @csrf

        <div class="mb-3">
            <label for="name" class="guest-form-label">Name <span class="required-mark">*</span></label>
            <input id="name" type="text" name="name" class="guest-form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="guest-form-label">Email <span class="required-mark">*</span></label>
            <input id="email" type="email" name="email" class="guest-form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="guest-form-label">Password <span class="required-mark">*</span></label>
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

        <div class="guest-actions mb-3">
            <a href="{{ route('login') }}" class="guest-link small">Already registered?</a>
            <button type="submit" class="guest-btn-primary" style="width: auto; padding-left: 1.25rem; padding-right: 1.25rem;">
                Register
            </button>
        </div>
    </form>
@endsection
