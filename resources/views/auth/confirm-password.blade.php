@extends('layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <p class="guest-intro">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="guest-form-label">Password <span class="required-mark">*</span></label>
            <input id="password" type="password" name="password" class="guest-form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button type="submit" class="guest-btn-primary">
                <i class="bi bi-shield-lock me-1"></i>Confirm
            </button>
        </div>
    </form>
@endsection
