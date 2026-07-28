@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<x-page-header title="My Profile" :breadcrumbs="['System' => null, 'My Profile' => null]" />

<div class="row g-3">
    <div class="col-lg-6 col-xl-5">
        <div class="card">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label d-block">Profile Picture</label>
                        <div class="d-flex align-items-center gap-3">
                            @if ($user->profilePictureUrl())
                                <img src="{{ $user->profilePictureUrl() }}" alt="Profile picture" class="rounded-circle" width="64" height="64" style="object-fit: cover;">
                            @else
                                <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
                            @endif
                            <div class="flex-grow-1">
                                <input type="file" name="profile_picture" class="form-control @error('profile_picture') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text">JPG, PNG, or WEBP. Max 2 MB.</div>
                                @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if ($user->profile_picture)
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="remove_profile_picture" value="1" class="form-check-input" id="remove_profile_picture" @checked(old('remove_profile_picture'))>
                                        <label class="form-check-label" for="remove_profile_picture">Remove current photo</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control readonly-field" value="{{ $user->username }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control readonly-field" value="{{ $user->role?->name }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assigned Suppliers</label>
                            <input type="text" class="form-control readonly-field" value="{{ $user->suppliers->pluck('supplier_name')->join(', ') ?: '—' }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full Name <span class="required-mark">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email <span class="required-mark">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $user->contact_number) }}">
                            @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">About Me</label>
                            <textarea name="about_me" class="form-control @error('about_me') is-invalid @enderror" rows="3" maxlength="1000">{{ old('about_me', $user->about_me) }}</textarea>
                            <div class="form-text">Optional. Up to 1,000 characters.</div>
                            @error('about_me')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
