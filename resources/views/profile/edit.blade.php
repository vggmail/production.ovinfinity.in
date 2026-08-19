@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Profile Settings</h1>
        <p>View and update your administrator details</p>
    </div>
</div>

<div class="card" style="margin-top: 1rem;">
    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Personal Information</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">Update your contact details and locations</p>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="UserCode">Employee Code</label>
                <input type="text" id="UserCode" value="{{ $user->UserCode }}" disabled style="background: rgba(0, 0, 0, 0.03);">
            </div>

            <div class="form-group">
                <label for="UserName">Username</label>
                <input type="text" id="UserName" value="{{ $user->UserName }}" disabled style="background: rgba(0, 0, 0, 0.03);">
            </div>

            <div class="form-group">
                <label for="FullName">Full Name</label>
                <input type="text" name="FullName" id="FullName" value="{{ old('FullName', $user->FullName) }}" class="@error('FullName') is-invalid @enderror" required>
                @error('FullName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="EmailId">Email ID</label>
                <input type="email" name="EmailId" id="EmailId" value="{{ old('EmailId', $user->EmailId) }}" class="@error('EmailId') is-invalid @enderror" required>
                @error('EmailId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="ContactNo">Contact Number</label>
                <input type="text" name="ContactNo" id="ContactNo" value="{{ old('ContactNo', $user->ContactNo) }}" class="@error('ContactNo') is-invalid @enderror" required>
                @error('ContactNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="City">City</label>
                <input type="text" name="City" id="City" value="{{ old('City', $user->City) }}" class="@error('City') is-invalid @enderror" required>
                @error('City')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label for="Address">Address</label>
                <textarea name="Address" id="Address" rows="3" class="@error('Address') is-invalid @enderror" style="resize: vertical;">{{ old('Address', $user->Address) }}</textarea>
                @error('Address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 1rem; margin-top: 2rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Security Settings</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">Leave fields blank if you do not want to change your password</p>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="Password">New Password</label>
                <input type="password" name="Password" id="Password" class="@error('Password') is-invalid @enderror" placeholder="Min 6 characters">
                @error('Password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="Password_confirmation">Confirm New Password</label>
                <input type="password" name="Password_confirmation" id="Password_confirmation" placeholder="Confirm your new password">
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn-action">
                💾 Save Changes
            </button>
            <a href="{{ route('dashboard') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
