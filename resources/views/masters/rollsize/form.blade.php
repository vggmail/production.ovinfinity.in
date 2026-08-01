@extends('layouts.app')

@section('title', $rollsize->exists ? 'Edit Roll Size' : 'Add New Roll Size')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $rollsize->exists ? 'Edit Roll Size' : 'Add New Roll Size' }}</h1>
        <p>{{ $rollsize->exists ? 'Modify roll size parameters' : 'Register a new roll size definition' }}</p>
    </div>
    <a href="{{ route('masters.rollsize.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $rollsize->exists ? route('masters.rollsize.update', $rollsize->ID) : route('masters.rollsize.store') }}" method="POST">
        @csrf
        @if($rollsize->exists)
            @method('PUT')
        @endif

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $rollsize->exists ? $rollsize->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4);">
            </div>

            <!-- Roll Size Input -->
            <div class="form-group" style="grid-column: span 2;">
                <label for="RollSize">Roll Size</label>
                <input type="text" id="RollSize" name="RollSize" value="{{ old('RollSize', $rollsize->RollSize) }}" placeholder="Enter roll size (e.g. 100 meters, 50-inch)" required autofocus>
                @error('RollSize')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: 1 / -1;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $rollsize->exists ? $rollsize->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this roll size as Active</label>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                Save Roll Size
            </button>
            <a href="{{ route('masters.rollsize.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
