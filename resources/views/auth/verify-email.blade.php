@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')
    <p class="guest-intro">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent you.
        If you did not receive the email, we will gladly send you another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="guest-alert guest-alert--success">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="guest-actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="guest-btn-primary">
                <i class="bi bi-envelope me-1"></i>Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="guest-btn-secondary">Log Out</button>
        </form>
    </div>
@endsection
