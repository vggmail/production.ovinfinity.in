@extends('layouts.app')

@section('title', $materialIssue->exists ? 'Edit Material Issue' : 'Create Material Issue')

@section('styles')
<style>
    .form-grid-header {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .select2-container--default .select2-selection--single {
        height: 38px !important;
        padding: 4px 8px !important;
        border: 1px solid var(--border-color, #d1d5db) !important;
        border-radius: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $materialIssue->exists ? 'Edit Material Issue' : 'Create Material Issue' }}</h1>
        <p>{{ $materialIssue->exists ? 'Update material issue details and items' : 'Issue materials from store to departments and loom machines' }}</p>
    </div>
    <a href="{{ route('store.materialissue.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; text-decoration: none;" title="Close">
        ✕
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <ul style="margin: 0; padding-left: 1.25rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $materialIssue->exists ? route('store.materialissue.update', $materialIssue->ID) : route('store.materialissue.store') }}" method="POST" id="material-issue-form">
    @csrf
    @if($materialIssue->exists)
        @method('PUT')
    @endif

    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Header Fields -->
        <div class="form-grid-header">
            <div class="form-group">
                <label for="IssueNo" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Issue No.</label>
                <input type="text" id="IssueNo" name="IssueNo" class="form-control" value="{{ old('IssueNo', $materialIssue->IssueNo) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group">
                <label for="IssueDate" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Issue Date</label>
                <input type="date" id="IssueDate" name="IssueDate" class="form-control" value="{{ old('IssueDate', $materialIssue->IssueDate ? (is_string($materialIssue->IssueDate) ? $materialIssue->IssueDate : $materialIssue->IssueDate->format('Y-m-d')) : date('Y-m-d')) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group">
                <label for="Technician" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Technician / Person - list coming from master</label>
                <select id="Technician" name="Technician" class="form-control select2-field" style="width: 100%;">
                    <option value="">-- Select Technician / Person --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->ID }}" {{ old('Technician', $materialIssue->Technician) == $tech->ID ? 'selected' : '' }}>
                            {{ $tech->Name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color, #e5e7eb); margin: 1.5rem 0;">

        <!-- Items Rows Repeater Table -->
        <div style="margin-bottom: 1rem;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600;">Issue Item Details</h3>
            
            <div style="overflow-x: auto; width: 100%;">
                <table class="store-table" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="background: var(--bg-surface-hover, #f8fafc); border-bottom: 1px solid var(--border-color, #e2e8f0); text-align: left;">
                            <th style="padding: 0.6rem 0.75rem; font-weight: 600; font-size: 0.85rem; width: 25%;">Loom No. / Machine Name</th>
                            <th style="padding: 0.6rem 0.75rem; font-weight: 600; font-size: 0.85rem; width: 25%;">To Department</th>
                            <th style="padding: 0.6rem 0.75rem; font-weight: 600; font-size: 0.85rem; width: 30%;">Item</th>
                            <th style="padding: 0.6rem 0.75rem; font-weight: 600; font-size: 0.85rem; width: 16%;">Quantity (Show Available Stock Here)</th>
                            <th style="padding: 0.6rem 0.4rem; font-weight: 600; font-size: 0.85rem; width: 4%; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="item-rows-container">
                        <!-- Dynamic rows inserted here without per-row labels -->
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 0.75rem; margin-bottom: 1rem;">
            <button type="button" class="btn-add-manual-item" id="add-row-btn-bottom">
                + Add New Item
            </button>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color, #e5e7eb); margin: 1.5rem 0;">

        <!-- Remarks Footer -->
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="Remarks" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Remarks</label>
            <textarea id="Remarks" name="Remarks" class="form-control" rows="3" placeholder="e.g. Loom No. 12 Breakdown" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">{{ old('Remarks', $materialIssue->Remarks) }}</textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center; margin-top: 1.5rem;">
            <button type="submit" class="btn-save-green">
                Save
            </button>
            <a href="{{ route('store.materialissue.index') }}" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center;">
                Cancel
            </a>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const stockMap = @json($stockMap);
        const looms = @json($looms);
        const departments = @json($departments);
        const items = @json($items);
        const existingRows = @json(old('items', $materialIssue->children->toArray()));

        const container = document.getElementById('item-rows-container');
        const addBtnBottom = document.getElementById('add-row-btn-bottom');
        let rowIndex = 0;

        // Initialize Select2 on Technician
        if (typeof $.fn.select2 !== 'undefined') {
            $('#Technician').select2({
                placeholder: '-- Select Technician / Person --',
                width: '100%',
                allowClear: true
            });
        }

        function updateDisabledOptions() {
            const selects = Array.from(container.querySelectorAll('.item-select'));
            const selectedValues = selects
                .map(s => s.value || $(s).val())
                .filter(val => val !== '' && val !== null && val !== undefined);

            selects.forEach(select => {
                const currentValue = select.value || $(select).val();
                const options = select.querySelectorAll('option');

                options.forEach(option => {
                    if (!option.value) return; // Do not disable placeholder
                    if (selectedValues.includes(option.value) && String(option.value) !== String(currentValue)) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });

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
            const index = rowIndex++;
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.setAttribute('data-index', index);
            tr.style.borderBottom = '1px solid var(--border-color, #e2e8f0)';

            const selectedLoom = data.LoomNumber || '';
            const selectedDept = data.Department || '';
            const selectedItem = data.ItemMaster || '';
            const selectedQuantity = data.Quantity !== undefined && data.Quantity !== '' ? parseInt(data.Quantity) : (selectedItem && stockMap.hasOwnProperty(selectedItem) ? Math.floor(stockMap[selectedItem]) : '');

            // Loom Options
            let loomOptionsHtml = '<option value="">-- Select Machine --</option>';
            looms.forEach(loom => {
                const isSel = String(selectedLoom) === String(loom.ID) ? 'selected' : '';

                let nameStr = '';
                if (loom.LoomNumber && loom.MachineName) {
                    nameStr = `${loom.LoomNumber} / ${loom.MachineName}`;
                } else if (loom.LoomNumber) {
                    nameStr = loom.LoomNumber;
                } else if (loom.MachineName) {
                    nameStr = loom.MachineName;
                } else {
                    nameStr = `Loom #${loom.ID}`;
                }

                const typeStr = loom.LoomTypeName ? ` - ( ${loom.LoomTypeName} )` : '';
                const loomLabel = `${nameStr}${typeStr}`;

                loomOptionsHtml += `<option value="${loom.ID}" ${isSel}>${loomLabel}</option>`;
            });

            // Department Options
            let deptOptionsHtml = '<option value="">-- Select To Department --</option>';
            departments.forEach(dept => {
                const isSel = String(selectedDept) === String(dept.ID) ? 'selected' : '';
                const code = dept.Code ? ` (${dept.Code})` : '';
                deptOptionsHtml += `<option value="${dept.ID}" ${isSel}>${dept.DepartmentName}${code}</option>`;
            });

            // Item Options
            let itemOptionsHtml = '<option value="">-- Select Item (Search by Name, Part No, or Cat No) --</option>';
            items.forEach(item => {
                const isSel = String(selectedItem) === String(item.ID) ? 'selected' : '';
                const partText = item.PartNo ? ` | Part No: ${item.PartNo}` : '';
                const catText = item.CatalogueNo ? ` | Cat No: ${item.CatalogueNo}` : '';
                const labelText = `${item.ItemName}${partText}${catText}`;
                itemOptionsHtml += `<option value="${item.ID}" ${isSel}>${labelText}</option>`;
            });

            tr.innerHTML = `
                <td style="padding: 0.5rem 0.4rem;">
                    <select name="items[${index}][LoomNumber]" class="form-control loom-select" style="width: 100%;">
                        ${loomOptionsHtml}
                    </select>
                </td>
                <td style="padding: 0.5rem 0.4rem;">
                    <select name="items[${index}][Department]" class="form-control dept-select" style="width: 100%;">
                        ${deptOptionsHtml}
                    </select>
                </td>
                <td style="padding: 0.5rem 0.4rem;">
                    <select name="items[${index}][ItemMaster]" class="form-control item-select" required style="width: 100%;">
                        <option value="">-- Select Item (Search by Name, Part No, or Cat No) --</option>
                    </select>
                </td>
                <td style="padding: 0.5rem 0.4rem;">
                    <input type="number" step="1" min="1" name="items[${index}][Quantity]" class="form-control qty-input" value="${selectedQuantity}" required placeholder="0" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td style="padding: 0.5rem 0.4rem; text-align: center; vertical-align: middle;">
                    <button type="button" class="btn-remove-row" style="background: transparent; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Remove Row">🗑️</button>
                </td>
            `;

            container.appendChild(tr);

            const loomSelect = tr.querySelector('.loom-select');
            const deptSelect = tr.querySelector('.dept-select');
            const itemSelect = tr.querySelector('.item-select');
            const qtyInput = tr.querySelector('.qty-input');
            const removeBtn = tr.querySelector('.btn-remove-row');

            // Function to update stock limits and quantity on item change
            function updateStockLimit(itemId, autoFillQuantity = false) {
                if (itemId && stockMap.hasOwnProperty(itemId)) {
                    const availableStock = Math.floor(parseFloat(stockMap[itemId])) || 0;
                    qtyInput.max = availableStock;
                    qtyInput.setAttribute('data-max', availableStock);
                    qtyInput.placeholder = `${availableStock}`;
                    if (autoFillQuantity) {
                        qtyInput.value = availableStock;
                    }
                } else {
                    qtyInput.removeAttribute('max');
                    qtyInput.removeAttribute('data-max');
                    qtyInput.placeholder = "0";
                    if (autoFillQuantity) {
                        qtyInput.value = '';
                    }
                }
            }

            // Populate items dropdown based on department selection
            function populateItems(deptId, initialItemId = '') {
                const currentItemId = initialItemId !== '' ? initialItemId : ($(itemSelect).val() || '');
                let filteredItems = items;
                if (deptId) {
                    const deptItems = items.filter(item => String(item.Department) === String(deptId));
                    if (deptItems.length > 0) {
                        filteredItems = deptItems;
                    }
                }

                let itemOptionsHtml = '<option value="">-- Select Item (Search by Name, Part No, or Cat No) --</option>';
                let hasCurrent = false;

                filteredItems.forEach(item => {
                    const isSel = String(currentItemId) === String(item.ID) ? 'selected' : '';
                    if (isSel) hasCurrent = true;
                    const partText = item.PartNo ? ` | Part No: ${item.PartNo}` : '';
                    const catText = item.CatalogueNo ? ` | Cat No: ${item.CatalogueNo}` : '';
                    const labelText = `${item.ItemName}${partText}${catText}`;
                    itemOptionsHtml += `<option value="${item.ID}" ${isSel}>${labelText}</option>`;
                });

                if (typeof $.fn.select2 !== 'undefined' && $(itemSelect).data('select2')) {
                    $(itemSelect).html(itemOptionsHtml);
                    if (hasCurrent && currentItemId) {
                        $(itemSelect).val(currentItemId).trigger('change.select2');
                        updateStockLimit(currentItemId, false);
                    } else {
                        $(itemSelect).val('').trigger('change.select2');
                        updateStockLimit('', true);
                    }
                } else {
                    itemSelect.innerHTML = itemOptionsHtml;
                    if (hasCurrent && currentItemId) {
                        itemSelect.value = currentItemId;
                        updateStockLimit(currentItemId, false);
                    } else {
                        itemSelect.value = '';
                        updateStockLimit('', true);
                    }
                }
                updateDisabledOptions();
            }

            // Enforce integer quantity without decimals and max stock limit
            function validateQuantityInput() {
                if (qtyInput.value && qtyInput.value.includes('.')) {
                    qtyInput.value = Math.floor(parseFloat(qtyInput.value)) || 1;
                }
                const maxVal = parseInt(qtyInput.getAttribute('data-max'));
                const currentVal = parseInt(qtyInput.value);
                if (!isNaN(maxVal) && !isNaN(currentVal) && currentVal > maxVal) {
                    alert(`Quantity cannot be increased beyond the available GRN stock (${maxVal}). You can only decrease it.`);
                    qtyInput.value = maxVal;
                }
            }

            qtyInput.addEventListener('keydown', function(e) {
                if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '-') {
                    e.preventDefault();
                }
            });

            qtyInput.addEventListener('input', validateQuantityInput);
            qtyInput.addEventListener('change', validateQuantityInput);

            // Initialize Select2 on selects
            if (typeof $.fn.select2 !== 'undefined') {
                $(loomSelect).select2({ placeholder: '-- Select Machine --', width: '100%', allowClear: true });
                $(deptSelect).select2({ placeholder: '-- Select To Department --', width: '100%', allowClear: true });
                $(itemSelect).select2({ placeholder: '-- Select Item (Search by Name, Part No, or Cat No) --', width: '100%', allowClear: true });

                // Initial populate for items
                populateItems(selectedDept, selectedItem);
                if (selectedItem) {
                    updateStockLimit(selectedItem, quantityVal === '');
                }

                // Re-filter items when department changes
                $(deptSelect).on('change select2:select select2:clear', function() {
                    const deptId = $(this).val();
                    populateItems(deptId, '');
                    updateDisabledOptions();
                });

                // Fill Available Stock in Quantity input box when Item is selected
                $(itemSelect).on('change select2:select select2:clear', function() {
                    const itemId = $(this).val();
                    updateStockLimit(itemId, true);
                    updateDisabledOptions();
                });
            } else {
                populateItems(selectedDept, selectedItem);
                if (selectedItem) {
                    updateStockLimit(selectedItem, quantityVal === '');
                }

                deptSelect.addEventListener('change', function() {
                    populateItems(this.value, '');
                    updateDisabledOptions();
                });

                itemSelect.addEventListener('change', function() {
                    const itemId = this.value;
                    updateStockLimit(itemId, true);
                    updateDisabledOptions();
                });
            }

            removeBtn.addEventListener('click', () => {
                if (container.children.length > 1) {
                    if (typeof $.fn.select2 !== 'undefined') {
                        $(loomSelect).select2('destroy');
                        $(deptSelect).select2('destroy');
                        $(itemSelect).select2('destroy');
                    }
                    tr.remove();
                    updateDisabledOptions();
                } else {
                    alert('At least one item row is required and cannot be deleted.');
                }
            });

            updateDisabledOptions();
        }

        if (addBtnBottom) {
            addBtnBottom.addEventListener('click', () => {
                createRow({ Quantity: '' });
                updateDisabledOptions();
            });
        }

        // Initialize default or existing rows
        if (existingRows && existingRows.length > 0) {
            existingRows.forEach(row => createRow(row));
        } else {
            // Default 1 row as requested
            createRow({ Quantity: '' });
        }
        updateDisabledOptions();

        // Form submission overall stock validation across all rows
        const form = document.getElementById('material-issue-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const itemTotals = {};
                const rows = container.querySelectorAll('.item-row');
                
                rows.forEach(r => {
                    const sel = r.querySelector('.item-select');
                    const qInput = r.querySelector('.qty-input');
                    const itemId = sel ? sel.value : null;
                    const qty = qInput ? (parseFloat(qInput.value) || 0) : 0;
                    if (itemId) {
                        itemTotals[itemId] = (itemTotals[itemId] || 0) + qty;
                    }
                });

                for (const itemId in itemTotals) {
                    const requested = itemTotals[itemId];
                    const available = stockMap.hasOwnProperty(itemId) ? parseFloat(stockMap[itemId]) : 0;
                    if (requested > available) {
                        const itemObj = items.find(i => String(i.ID) === String(itemId));
                        const itemName = itemObj ? itemObj.ItemName : 'Selected item';
                        alert(`Total requested quantity for "${itemName}" (${requested}) exceeds available GRN stock (${available}). Please decrease the quantity.`);
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
    });
</script>
@endsection
