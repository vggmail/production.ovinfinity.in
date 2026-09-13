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

        function createRow(data = {}) {
            const index = rowIndex++;
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.setAttribute('data-index', index);
            tr.style.borderBottom = '1px solid var(--border-color, #e2e8f0)';

            const selectedLoom = data.LoomNumber || '';
            const selectedDept = data.Department || '';
            const selectedItem = data.ItemMaster || '';
            const quantityVal = data.Quantity !== undefined && data.Quantity !== '' ? data.Quantity : (selectedItem && stockMap.hasOwnProperty(selectedItem) ? stockMap[selectedItem] : '');

            // Loom Options
            let loomOptionsHtml = '<option value="">-- Select Machine --</option>';
            looms.forEach(loom => {
                const isSel = String(selectedLoom) === String(loom.ID) ? 'selected' : '';
                const loomLabel = loom.MachineName ? `${loom.MachineName} - ${loom.LoomNumber}` : loom.LoomNumber;
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
            let itemOptionsHtml = '<option value="">-- Select Item --</option>';
            items.forEach(item => {
                const isSel = String(selectedItem) === String(item.ID) ? 'selected' : '';
                const partNo = item.PartNo ? ` (${item.PartNo})` : '';
                itemOptionsHtml += `<option value="${item.ID}" ${isSel}>${item.ItemName}${partNo}</option>`;
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
                        ${itemOptionsHtml}
                    </select>
                </td>
                <td style="padding: 0.5rem 0.4rem;">
                    <input type="number" step="0.01" min="0.01" name="items[${index}][Quantity]" class="form-control qty-input" value="${quantityVal}" required placeholder="2" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
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

            // Initialize Select2 on selects
            if (typeof $.fn.select2 !== 'undefined') {
                $(loomSelect).select2({ placeholder: '-- Select Machine --', width: '100%', allowClear: true });
                $(deptSelect).select2({ placeholder: '-- Select To Department --', width: '100%', allowClear: true });
                $(itemSelect).select2({ placeholder: '-- Select Item --', width: '100%', allowClear: true });

                // Fill Available Stock in Quantity input box when Item is selected
                $(itemSelect).on('change select2:select', function() {
                    const itemId = $(this).val();
                    if (itemId && stockMap.hasOwnProperty(itemId)) {
                        qtyInput.value = stockMap[itemId];
                    } else if (!itemId) {
                        qtyInput.value = '';
                    }
                });
            } else {
                itemSelect.addEventListener('change', function() {
                    const itemId = this.value;
                    if (itemId && stockMap.hasOwnProperty(itemId)) {
                        qtyInput.value = stockMap[itemId];
                    } else if (!itemId) {
                        qtyInput.value = '';
                    }
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
                } else {
                    alert('At least one item row is required and cannot be deleted.');
                }
            });
        }

        if (addBtnBottom) {
            addBtnBottom.addEventListener('click', () => {
                createRow({ Quantity: '' });
            });
        }

        // Initialize default or existing rows
        if (existingRows && existingRows.length > 0) {
            existingRows.forEach(row => createRow(row));
        } else {
            // Default 1 row as requested
            createRow({ Quantity: '' });
        }
    });
</script>
@endsection
