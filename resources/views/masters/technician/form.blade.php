@extends('layouts.app')

@section('title', $technician->exists ? 'Edit Technician' : 'Add New Technician')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $technician->exists ? 'Edit Technician' : 'Add New Technician' }}</h1>
        <p>{{ $technician->exists ? 'Modify technician parameters' : 'Register a new technician / person for store issues' }}</p>
    </div>
    <a href="{{ route('masters.technician.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $technician->exists ? route('masters.technician.update', $technician->ID) : route('masters.technician.store') }}" method="POST">
        @csrf
        @if($technician->exists)
            @method('PUT')
        @endif

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <!-- ID -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $technician->exists ? $technician->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(0,0,0,0.03); color: var(--text-secondary);">
            </div>

            <!-- Technician Name Input -->
            <div class="form-group">
                <label for="Name">Technician Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="Name" name="Name" value="{{ old('Name', $technician->Name) }}" placeholder="Enter technician name (e.g. Rajesh Pal)" required autofocus>
                @error('Name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Code Input (Optional) -->
            <div class="form-group">
                <label for="Code">Code</label>
                <input type="text" id="Code" name="Code" value="{{ old('Code', $technician->Code) }}" placeholder="Enter technician code (optional)">
                @error('Code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Phone Input (Optional) -->
            <div class="form-group">
                <label for="Phone">Phone</label>
                <input type="text" id="Phone" name="Phone" value="{{ old('Phone', $technician->Phone) }}" placeholder="Enter phone number (optional)">
                @error('Phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: 1 / -1;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $technician->exists ? $technician->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this technician as Active</label>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn-save-green">
                Save Technician
            </button>
            <a href="{{ route('masters.technician.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
