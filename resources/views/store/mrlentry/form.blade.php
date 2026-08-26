@extends('layouts.app')

@section('title', $mrl->exists ? 'Edit MRL Entry' : 'Add New MRL Entry')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $mrl->exists ? 'Edit MRL Entry' : 'Add New MRL Entry' }}</h1>
    </div>
    <a href="{{ route('store.mrlentry.index') }}" class="btn-action-secondary btn-close-circle" title="Close">
        ✕
    </a>
</div>

@if(!empty($isQuoted))
    <div class="alert alert-error" style="margin-top: 1rem;">
        <span>🔒</span>
        <span>This MRL Entry has already been included in a Vendor Quotation and cannot be modified or deleted.</span>
    </div>
@endif

<div class="card" style="margin-top: 1rem;">

    <form action="{{ $mrl->exists ? route('store.mrlentry.update', $mrl->ID) : route('store.mrlentry.store') }}" method="POST" id="mrl-form">
        @csrf
        @if($mrl->exists)
            @method('PUT')
        @endif

        <!-- Id & Entry Date -->
        <div class="form-grid-2">
            <div class="form-group" style="max-width: 150px;">
                <label for="ID" class="form-label-custom">Id</label>
                <input type="text" id="ID" name="ID" value="{{ $mrl->exists ? $mrl->ID : '' }}" disabled class="form-control-readonly">
            </div>

            <div class="form-group" style="max-width: 250px;">
                <label for="EntryDate" class="form-label-custom">
                    Entry Date <span class="required-star">*</span>
                    @if($mrl->exists) <span class="form-label-muted">(Non-Editable)</span> @endif
                </label>
                <input type="date" id="EntryDate" name="EntryDate" value="{{ old('EntryDate', $mrl->EntryDate ? (is_string($mrl->EntryDate) ? substr($mrl->EntryDate, 0, 10) : $mrl->EntryDate->format('Y-m-d')) : date('Y-m-d')) }}" required {{ $mrl->exists ? 'readonly class=form-control-readonly' : 'class=form-control-custom' }}>
                @error('EntryDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if($errors->has('items'))
            <div class="alert alert-error">
                {{ $errors->first('items') }}
            </div>
        @endif

        <!-- Dynamic Table matching Mockup -->
        <div class="store-table-container">
            <table class="store-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Sr No</th>
                        <th>Item Name</th>
                        <th style="width: 250px;">Quantity</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    <!-- Dynamic rows inserted here -->
                </tbody>
                <tfoot class="store-table-tfoot">
                    <tr>
                        <td colspan="4">
                            @if(empty($isQuoted))
                                <button type="button" id="btn-add-row" class="btn-add-manual-item">
                                    Add
                                </button>
                            @else
                                <span class="form-label-muted">Editing disabled for quoted MRL entry</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Summary Row: Total Items & Total Quantity -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="TotalItems" class="form-label-custom">Total Items</label>
                <input type="number" id="TotalItems" name="TotalItems" value="{{ old('TotalItems', $mrl->TotalItems ?? 0) }}" readonly class="form-control-readonly">
            </div>

            <div class="form-group">
                <label for="TotalQuantity" class="form-label-custom">Total Quantity</label>
                <input type="number" step="0.01" id="TotalQuantity" name="TotalQuantity" value="{{ old('TotalQuantity', $mrl->TotalQuantity ?? 0) }}" readonly class="form-control-readonly">
            </div>
        </div>

        <!-- Save Button (Green) -->
        <div class="form-actions">
            @if(empty($isQuoted))
                <button type="submit" class="btn-save-green">
                    Save
                </button>
            @else
                <a href="{{ route('store.mrlentry.index') }}" class="btn-action-secondary">
                    Back to List
                </a>
            @endif
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableBody = document.getElementById('items-body');
        const btnAddRow = document.getElementById('btn-add-row');
        const totalItemsInput = document.getElementById('TotalItems');
        const totalQtyInput = document.getElementById('TotalQuantity');
        const isQuoted = @json(!empty($isQuoted));

        const itemListOptions = @json($itemList ?? []);
        const existingChildren = @json(old('items', $mrl->children ?? []));

        function calculateTotals() {
            const rows = tableBody.querySelectorAll('tr');
            let totalItems = 0;
            let totalQty = 0;

            rows.forEach((row, index) => {
                const srCell = row.querySelector('.sr-no');
                if (srCell) srCell.textContent = index + 1;

                const itemSelect = row.querySelector('.item-select');
                const qtyInput = row.querySelector('.qty-input');

                if (itemSelect) itemSelect.name = `items[${index}][ItemMaster]`;
                if (qtyInput) {
                    qtyInput.name = `items[${index}][Quantity]`;
                    const qtyVal = parseFloat(qtyInput.value) || 0;
                    totalQty += qtyVal;
                }

                totalItems++;
            });

            totalItemsInput.value = totalItems;
            totalQtyInput.value = totalQty.toFixed(2);
        }

        function updateDisabledOptions() {
            const selects = Array.from(tableBody.querySelectorAll('.item-select'));
            const selectedValues = selects
                .map(s => s.value)
                .filter(val => val !== '' && val !== null && val !== undefined);

            selects.forEach(select => {
                const currentValue = select.value;
                const options = select.querySelectorAll('option');

                options.forEach(option => {
                    if (!option.value) return; // Do not disable placeholder
                    if (selectedValues.includes(option.value) && option.value !== currentValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });

                // Re-initialize/refresh Select2 to reflect disabled options
                if (typeof $.fn.select2 !== 'undefined' && $(select).hasClass("select2-hidden-accessible")) {
                    $(select).select2({
                        placeholder: '-- Select Item (Search by Name, Part No, or Cat No) --',
                        width: '100%',
                        allowClear: true
                    });
                }
            });
        }

        function createRow(data = {}) {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #bbf7d0';

            const selectedItemId = data.ItemMaster || data.item_master || '';
            const quantityVal = data.Quantity || data.quantity || '';

            let itemOptionsHtml = '<option value="">-- Select Item (Search by Name, Part No, or Cat No) --</option>';
            itemListOptions.forEach(item => {
                const isSel = String(selectedItemId) === String(item.ID) ? 'selected' : '';
                const partText = item.PartNo ? ` | Part No: ${item.PartNo}` : '';
                const catText = item.CatalogueNo ? ` | Cat No: ${item.CatalogueNo}` : '';
                const labelText = `${item.ItemName}${partText}${catText}`;
                itemOptionsHtml += `<option value="${item.ID}" ${isSel}>${labelText}</option>`;
            });

            const disabledAttr = isQuoted ? 'disabled' : '';

            tr.innerHTML = `
                <td class="sr-no" style="text-align: center; padding: 10px; font-weight: 600; color: #166534;">1</td>
                <td style="padding: 8px 12px;">
                    <select class="item-select" required ${disabledAttr} style="width: 100%;">
                        ${itemOptionsHtml}
                    </select>
                </td>
                <td style="padding: 8px 12px;">
                    <input type="number" step="0.01" min="0.01" class="qty-input" value="${quantityVal}" required placeholder="0.00" ${disabledAttr} style="width: 100%; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0.45rem 0.75rem; background-color: #fff;">
                </td>
                <td style="text-align: center; padding: 8px;">
                    ${!isQuoted ? '<button type="button" class="btn-remove-row" style="background: transparent; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Remove Row">🗑️</button>' : ''}
                </td>
            `;

            const itemSelect = tr.querySelector('.item-select');
            const qtyInput = tr.querySelector('.qty-input');
            const btnRemove = tr.querySelector('.btn-remove-row');

            tableBody.appendChild(tr);

            // Initialize Select2 on the item select dropdown
            if (typeof $.fn.select2 !== 'undefined' && !isQuoted) {
                $(itemSelect).select2({
                    placeholder: '-- Select Item (Search by Name, Part No, or Cat No) --',
                    width: '100%',
                    allowClear: true
                });

                $(itemSelect).on('change select2:select select2:clear', () => {
                    updateDisabledOptions();
                    calculateTotals();
                });
            } else if (!isQuoted) {
                itemSelect.addEventListener('change', () => {
                    updateDisabledOptions();
                    calculateTotals();
                });
            }

            qtyInput.addEventListener('input', calculateTotals);

            if (btnRemove) {
                btnRemove.addEventListener('click', () => {
                    if (tableBody.children.length > 1) {
                        if (typeof $.fn.select2 !== 'undefined' && $(itemSelect).hasClass("select2-hidden-accessible")) {
                            $(itemSelect).select2('destroy');
                        }
                        tr.remove();
                        updateDisabledOptions();
                        calculateTotals();
                    } else {
                        alert('At least one item row is required.');
                    }
                });
            }

            calculateTotals();
        }

        if (btnAddRow) {
            btnAddRow.addEventListener('click', () => {
                createRow();
                updateDisabledOptions();
            });
        }

        // Initialize rows
        if (existingChildren && existingChildren.length > 0) {
            existingChildren.forEach(child => createRow(child));
        } else {
            createRow(); // Default 1 empty row
        }

        updateDisabledOptions();
    });
</script>
@endsection

