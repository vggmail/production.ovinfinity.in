@extends('layouts.app')

@section('title', $item->exists ? 'Edit Item Entry' : 'Add New Item Entry')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $item->exists ? 'Edit Item Entry' : 'Add New Item Entry' }}</h1>
    </div>
    <a href="{{ route('store.itemmaster.index') }}" class="btn-action-secondary btn-close-circle" title="Close">
        ✕
    </a>
</div>

<div class="card" style="margin-top: 1rem;">

    <form action="{{ $item->exists ? route('store.itemmaster.update', $item->ID) : route('store.itemmaster.store') }}" method="POST">
        @csrf
        @if($item->exists)
            @method('PUT')
        @endif

        <!-- Id Field -->
        <div style="margin-bottom: 1.5rem;">
            <div class="form-group" style="max-width: 150px;">
                <label for="ID" class="form-label-custom">Id</label>
                <input type="text" id="ID" name="ID" value="{{ $item->exists ? $item->ID : '' }}" disabled class="form-control-readonly">
            </div>
        </div>

        <!-- Row 1: Item Name, Part No., Catlogue No., Minimum Quantity -->
        <div class="form-grid-3">
            <div class="form-group">
                <label for="ItemName" class="form-label-custom">Item Name <span class="required-star">*</span></label>
                <input type="text" id="ItemName" name="ItemName" value="{{ old('ItemName', $item->ItemName) }}" required autofocus class="form-control-custom">
                @error('ItemName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="PartNo" class="form-label-custom">Part No.</label>
                <input type="text" id="PartNo" name="PartNo" value="{{ old('PartNo', $item->PartNo) }}" class="form-control-custom">
                @error('PartNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="CatalogueNo" class="form-label-custom">Catlogue No.</label>
                <input type="text" id="CatalogueNo" name="CatalogueNo" value="{{ old('CatalogueNo', $item->CatalogueNo) }}" class="form-control-custom">
                @error('CatalogueNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="MinQuantity" class="form-label-custom">Minimum Quantity</label>
                <input type="number" step="0.01" id="MinQuantity" name="MinQuantity" value="{{ old('MinQuantity', $item->MinQuantity) }}" class="form-control-custom">
                @error('MinQuantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Row 2: Department, HSN No., GST Percentage -->
        <div class="form-grid-3">
            <div class="form-group">
                <label for="Department" class="form-label-custom">Department</label>
                <select id="Department" name="Department" class="form-control-custom">
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->ID }}" {{ old('Department', $item->Department) == $dept->ID ? 'selected' : '' }}>
                            {{ $dept->DepartmentName }}
                        </option>
                    @endforeach
                </select>
                @error('Department')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="HSNNo" class="form-label-custom">HSN No.</label>
                <input type="text" id="HSNNo" name="HSNNo" value="{{ old('HSNNo', $item->HSNNo) }}" class="form-control-custom">
                @error('HSNNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="GSTPercentage" class="form-label-custom">GST Percentage</label>
                <input type="number" step="0.01" id="GSTPercentage" name="GSTPercentage" value="{{ old('GSTPercentage', $item->GSTPercentage) }}" class="form-control-custom">
                @error('GSTPercentage')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Status Checkbox -->
        <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="IsActive" name="IsActive" value="1" {{ old('IsActive', $item->exists ? $item->IsActive : 1) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
            <label for="IsActive" style="cursor: pointer; user-select: none; font-size: 0.9rem; font-weight: 500; color: #334155;">Mark this item as Active</label>
        </div>

        <!-- Save Button (Green) -->
        <div>
            <button type="submit" class="btn-save-green">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
