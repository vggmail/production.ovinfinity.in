@extends('layouts.app')

@section('title', $party->exists ? 'Edit Party' : 'Add New Party')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $party->exists ? 'Edit Party' : 'Add New Party' }}</h1>
        <p>{{ $party->exists ? 'Modify party details' : 'Register a new customer account' }}</p>
    </div>
    <a href="{{ route('masters.party.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $party->exists ? route('masters.party.update', $party->ID) : route('masters.party.store') }}" method="POST">
        @csrf
        @if($party->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $party->exists ? $party->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4);">
            </div>
            
            <div class="form-group" style="grid-column: span 2; display: none; /* Spacer to push items to next line to match screenshot */"></div>

            <!-- Row 1: Party Name, GSTIN, Contact Number -->
            <div class="form-group">
                <label for="PartyName">Party Name</label>
                <input type="text" id="PartyName" name="PartyName" value="{{ old('PartyName', $party->PartyName) }}" placeholder="Enter party name" required>
                @error('PartyName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="GSTIN">GSTIN</label>
                <input type="text" id="GSTIN" name="GSTIN" value="{{ old('GSTIN', $party->GSTIN) }}" placeholder="Enter GSTIN" required>
                @error('GSTIN')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="ContactNo">Contact Number</label>
                <input type="text" id="ContactNo" name="ContactNo" value="{{ old('ContactNo', $party->exists ? $party->ContactNo : '+91') }}" placeholder="Enter contact number" required>
                @error('ContactNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Row 2: Address, Street, City -->
            <div class="form-group">
                <label for="Address">Address</label>
                <input type="text" id="Address" name="Address" value="{{ old('Address', $party->Address) }}" placeholder="Enter address" required>
                @error('Address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="Street">Street</label>
                <input type="text" id="Street" name="Street" value="{{ old('Street', $party->Street) }}" placeholder="Enter street" required>
                @error('Street')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="City">City</label>
                <input type="text" id="City" name="City" value="{{ old('City', $party->City) }}" placeholder="Enter city" required>
                @error('City')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Row 3: District, State, Pin Code -->
            <div class="form-group">
                <label for="District">District</label>
                <input type="text" id="District" name="District" value="{{ old('District', $party->District) }}" placeholder="Enter district" required>
                @error('District')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="State">State</label>
                <input type="text" id="State" name="State" value="{{ old('State', $party->State) }}" placeholder="Enter state" required>
                @error('State')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="PinCode">Pin Code</label>
                <input type="text" id="PinCode" name="PinCode" value="{{ old('PinCode', $party->PinCode) }}" placeholder="Enter pin code" required>
                @error('PinCode')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: span 3;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $party->exists ? $party->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this party as Active</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                Save Party
            </button>
            <a href="{{ route('masters.party.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
