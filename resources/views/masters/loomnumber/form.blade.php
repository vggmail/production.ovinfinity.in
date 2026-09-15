@extends('layouts.app')

@section('title', $loomnumber->exists ? 'Edit Loom Number' : 'Add New Loom Number')

@section('styles')
<style>
    .form-grid-loom {
        display: grid;
        grid-template-columns: 140px 1.2fr 1.2fr 1fr;
        gap: 1.5rem;
        width: 100%;
        margin-bottom: 1.5rem;
    }

    .sub-label-text {
        font-size: 0.8rem;
        color: var(--text-secondary, #64748b);
        font-weight: 400;
        margin-left: 0.2rem;
    }

    @media (max-width: 1024px) {
        .form-grid-loom {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 640px) {
        .form-grid-loom {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

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

        <div class="form-grid-loom">
            <!-- ID (Read-only/Disabled) -->
            <div class="form-group">
                <label for="ID">ID</label>
                <input type="text" id="ID" name="ID" value="{{ $loomnumber->exists ? $loomnumber->ID : 'Auto-Increment' }}" disabled style="background-color: rgba(0,0,0,0.03); color: var(--text-secondary);">
            </div>

            <!-- Loom Number Input -->
            <div class="form-group">
                <label for="LoomNumber">Loom Number <span class="sub-label-text">(Optional if Machine Name filled)</span></label>
                <input type="text" id="LoomNumber" name="LoomNumber" value="{{ old('LoomNumber', $loomnumber->LoomNumber) }}" placeholder="Enter loom number (e.g. L-101)" autofocus>
                @error('LoomNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Machine Name Input -->
            <div class="form-group">
                <label for="MachineName">Machine Name <span class="sub-label-text">(Optional if Loom Number filled)</span></label>
                <input type="text" id="MachineName" name="MachineName" value="{{ old('MachineName', $loomnumber->MachineName) }}" placeholder="Enter machine name">
                @error('MachineName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Loom Type Select Dropdown -->
            <div class="form-group">
                <label for="LoomType">Loom Type <span class="required-star">*</span></label>
                <select id="LoomType" name="LoomType" required>
                    <option value="">- Select -</option>
                    @foreach($loomTypes as $id => $name)
                        <option value="{{ $id }}" {{ old('LoomType', $loomnumber->LoomType) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('LoomType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Status Checkbox -->
        <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">
            <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $loomnumber->exists ? $loomnumber->IsActive : 1) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent-primary);">
            <label for="IsActive" style="cursor: pointer; user-select: none;">Mark this loom as Active</label>
        </div>

        <div class="form-actions">
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
