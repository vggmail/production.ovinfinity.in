@extends('layouts.app')

@section('title', $fabriccolor->exists ? 'Edit Fabric Color' : 'Add New Fabric Color')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $fabriccolor->exists ? 'Edit Fabric Color' : 'Add New Fabric Color' }}</h1>
        <p>{{ $fabriccolor->exists ? 'Modify fabric color specifications' : 'Register a new color code' }}</p>
    </div>
    <a href="{{ route('masters.fabriccolor.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $fabriccolor->exists ? route('masters.fabriccolor.update', $fabriccolor->ID) : route('masters.fabriccolor.store') }}" method="POST">
        @csrf
        @if($fabriccolor->exists)
            @method('PUT')
        @endif

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $fabriccolor->exists ? $fabriccolor->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4);">
            </div>

            <!-- Fabric Color Input -->
            <div class="form-group" style="grid-column: span 2;">
                <label for="FabricColor">Fabric Color</label>
                <input type="text" id="FabricColor" name="FabricColor" value="{{ old('FabricColor', $fabriccolor->FabricColor) }}" placeholder="Enter color name or code (e.g. Royal Blue, Indigo)" required autofocus>
                @error('FabricColor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: 1 / -1;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $fabriccolor->exists ? $fabriccolor->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this color as Active</label>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                Save Fabric Color
            </button>
            <a href="{{ route('masters.fabriccolor.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
