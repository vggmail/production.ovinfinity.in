@extends('layouts.app')

@section('title', $loomnumber->exists ? 'Edit Loom Number' : 'Add New Loom Number')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $loomnumber->exists ? 'Edit Loom Number' : 'Add New Loom Number' }}</h1>
        <p>{{ $loomnumber->exists ? 'Modify loom parameters' : 'Register a new loom machine' }}</p>
    </div>
    <a href="{{ route('masters.loomnumber.index') }}" class="btn-action-secondary">
        &larr; Back to List
    </a>
</div>

<div class="card">
    <form action="{{ $loomnumber->exists ? route('masters.loomnumber.update', $loomnumber->ID) : route('masters.loomnumber.store') }}" method="POST">
        @csrf
        @if($loomnumber->exists)
            @method('PUT')
        @endif

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $loomnumber->exists ? $loomnumber->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(0,0,0,0.03); color: var(--text-secondary);">
            </div>

            <!-- Loom Number Input -->
            <div class="form-group">
                <label for="LoomNumber">Loom Number</label>
                <input type="text" id="LoomNumber" name="LoomNumber" value="{{ old('LoomNumber', $loomnumber->LoomNumber) }}" placeholder="Enter loom number (e.g. L-101)" required autofocus>
                @error('LoomNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Yarn Type (LoomType in DB) Select Dropdown -->
            <div class="form-group">
                <label for="LoomType">Yarn Type</label>
                <select id="LoomType" name="LoomType" required>
                    <option value="">- Select -</option>
                    @foreach($yarnTypes as $id => $name)
                        <option value="{{ $id }}" {{ old('LoomType', $loomnumber->LoomType) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('LoomType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Checkbox -->
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 1rem; grid-column: 1 / -1;">
                <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $loomnumber->exists ? $loomnumber->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
                <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this loom as Active</label>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                Save Loom Number
            </button>
            <a href="{{ route('masters.loomnumber.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
