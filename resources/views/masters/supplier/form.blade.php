@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'Add New Supplier')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $supplier->exists ? 'Edit Supplier' : 'Add New Supplier' }}</h1>
        <p>{{ $supplier->exists ? 'Modify supplier details' : 'Register a new supplier' }}</p>
    </div>
    <a href="{{ route('masters.supplier.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $supplier->exists ? route('masters.supplier.update', $supplier->ID) : route('masters.supplier.store') }}" method="POST">
        @csrf
        @if($supplier->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $supplier->exists ? $supplier->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4);">
            </div>
            
            <div class="form-group" style="grid-column: span 2; display: none;"></div>

            <!-- Row 1: Supplier Name, GSTIN, Contact Number -->
            <div class="form-group">
                <label for="SupplierName">Supplier Name</label>
                <input type="text" id="SupplierName" name="SupplierName" value="{{ old('SupplierName', $supplier->SupplierName) }}" placeholder="Enter supplier name" required>
                @error('SupplierName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="GSTIN">GSTIN</label>
                <input type="text" id="GSTIN" name="GSTIN" value="{{ old('GSTIN', $supplier->GSTIN) }}" placeholder="Enter GSTIN" required>
                @error('GSTIN')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="ContactNo">Contact Number</label>
                <input type="text" id="ContactNo" name="ContactNo" value="{{ old('ContactNo', $supplier->exists ? $supplier->ContactNo : '+91') }}" placeholder="Enter contact number" required>
                @error('ContactNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Row 2: Address, Street, City -->
            <div class="form-group">
                <label for="Address">Address</label>
                <input type="text" id="Address" name="Address" value="{{ old('Address', $supplier->Address) }}" placeholder="Enter address" required>
                @error('Address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="Street">Street</label>
                <input type="text" id="Street" name="Street" value="{{ old('Street', $supplier->Street) }}" placeholder="Enter street" required>
                @error('Street')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="City">City</label>
                <input type="text" id="City" name="City" value="{{ old('City', $supplier->City) }}" placeholder="Enter city" required>
                @error('City')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Row 3: District, State, Pin Code -->
            <div class="form-group">
                <label for="District">District</label>
                <input type="text" id="District" name="District" value="{{ old('District', $supplier->District) }}" placeholder="Enter district" required>
                @error('District')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="State">State</label>
                <input type="text" id="State" name="State" value="{{ old('State', $supplier->State) }}" placeholder="Enter state" required>
                @error('State')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="PinCode">Pin Code</label>
                <input type="text" id="PinCode" name="PinCode" value="{{ old('PinCode', $supplier->PinCode) }}" placeholder="Enter pin code" required>
                @error('PinCode')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: span 3;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $supplier->exists ? $supplier->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this supplier as Active</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                Save Supplier
            </button>
            <a href="{{ route('masters.supplier.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
