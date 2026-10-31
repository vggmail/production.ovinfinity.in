@extends('layouts.app')

@section('title', $repairIssue->exists ? 'Edit Repair Material Issue' : 'Add Repair Material Issue')

@section('styles')
<style>
    .form-grid-header {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

    .repair-table th {
        background: var(--bg-surface-hover, #f8fafc);
        padding: 0.65rem 0.75rem;
        font-weight: 600;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        text-align: left;
    }
    .repair-table td {
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
        vertical-align: middle;
    }
    .input-readonly {
        background-color: #f8fafc !important;
        color: #475569 !important;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $repairIssue->exists ? 'Edit Repair Material Issue' : 'Add Repair Material Issue' }}</h1>
        <p>{{ $repairIssue->exists ? 'Update repair issue details and items' : 'Issue materials out for vendor repair and track status' }}</p>
    </div>
    <a href="{{ route('repair.issue.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; text-decoration: none;" title="Close">
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

<form action="{{ $repairIssue->exists ? route('repair.issue.update', $repairIssue->ID) : route('repair.issue.store') }}" method="POST" id="repair-issue-form">
    @csrf
    @if($repairIssue->exists)
        @method('PUT')
    @endif

    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Header Fields -->
        <div class="form-grid-header">
            <div class="form-group">
                <label for="RepairNo" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Repair Issue No.</label>
                <input type="text" id="RepairNo" name="RepairNo" class="form-control" value="{{ old('RepairNo', $repairIssue->RepairNo) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group">
                <label for="RepairDate" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Date</label>
                <input type="date" id="RepairDate" name="RepairDate" class="form-control" value="{{ old('RepairDate', $repairIssue->RepairDate ? (is_string($repairIssue->RepairDate) ? $repairIssue->RepairDate : $repairIssue->RepairDate->format('Y-m-d')) : date('Y-m-d')) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="Supplier" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Supplier Name</label>
                <select id="Supplier" name="Supplier" class="form-control select2-field" style="width: 100%;" required>
                    <option value="">-- Select Supplier Name --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->ID }}" {{ old('Supplier', $repairIssue->Supplier) == $supplier->ID ? 'selected' : '' }}>
                            {{ $supplier->SupplierName }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color, #e5e7eb); margin: 1.5rem 0;">

        <!-- Items Repeater Table -->
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Repair Item Details</h3>
                <span style="font-size: 0.85rem; color: #64748b;">(Value is automatically calculated from Quantity × Rate)</span>
            </div>
            
            <div style="overflow-x: auto; width: 100%;">
                <table class="repair-table" style="width: 100%; border-collapse: collapse; min-width: 750px;">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Item Name</th>
                            <th style="width: 18%;">Quantity</th>
                            <th style="width: 18%;">Rate</th>
                            <th style="width: 15%;">Value</th>
                            <th style="width: 4%; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <!-- Dynamic item rows injected here -->
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--bg-surface-hover, #f8fafc); font-weight: 600; border-top: 2px solid var(--border-color, #cbd5e1);">
                            <td style="text-align: right; padding: 0.75rem;">Total:</td>
                            <td id="grand-total-qty" style="padding: 0.75rem;">0</td>
                            <td></td>
                            <td id="grand-total-val" style="padding: 0.75rem;">₹ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div style="margin-top: 0.75rem; margin-bottom: 1rem;">
            <button type="button" class="btn-add-manual-item" id="add-row-btn" style="background: #f1f5f9; border: 1px dashed #94a3b8; color: #334155; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer;">
                + Add Item
            </button>
        </div>

        <!-- Actions Footer -->
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center; margin-top: 1.5rem;">
            <button type="submit" class="btn-save-green" style="background: #10b981; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Save
            </button>
            <a href="{{ route('repair.issue.index') }}" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center; background: #e2e8f0; color: #334155; border-radius: 6px;">
                Cancel
            </a>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const itemsList = @json($items);
        const existingRows = @json(old('items', $repairIssue->children->toArray()));
        const container = document.getElementById('items-container');
        const addBtn = document.getElementById('add-row-btn');

        let rowIndex = 0;

        function renderRow(data = {}) {
            const tr = document.createElement('tr');
            tr.dataset.index = rowIndex;

            let itemOptionsHtml = '<option value="">-- Select Item Name --</option>';
            itemsList.forEach(item => {
                const selected = (data.ItemMaster && data.ItemMaster == item.ID) ? 'selected' : '';
                const partInfo = item.PartNo ? ` (Part: ${item.PartNo})` : '';
                itemOptionsHtml += `<option value="${item.ID}" ${selected}>${item.ItemName}${partInfo}</option>`;
            });

            const qtyVal = data.Quantity !== undefined ? data.Quantity : '';
            const rateVal = data.Rate !== undefined ? data.Rate : '';
            const amountVal = (qtyVal && rateVal) ? (parseFloat(qtyVal) * parseFloat(rateVal)).toFixed(2) : '0.00';

            tr.innerHTML = `
                <td>
                    <select name="items[${rowIndex}][ItemMaster]" class="form-control select2-row-item" style="width: 100%;" required>
                        ${itemOptionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][Quantity]" class="form-control row-qty" value="${qtyVal}" placeholder="Quantity" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][Rate]" class="form-control row-rate" value="${rateVal}" placeholder="Rate" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td>
                    <!-- User instruction: value readonly hoga -->
                    <input type="text" class="form-control row-value input-readonly" value="${amountVal}" placeholder="Value" readonly style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-remove-row" style="background: none; border: none; color: #ef4444; font-size: 1.2rem; cursor: pointer;" title="Remove Row">✕</button>
                </td>
            `;

            container.appendChild(tr);

            // Initialize select2 on the new row if available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(tr).find('.select2-row-item').select2();
            }

            // Event listeners for recalculating value
            const qtyInput = tr.querySelector('.row-qty');
            const rateInput = tr.querySelector('.row-rate');
            const valInput = tr.querySelector('.row-value');
            const removeBtn = tr.querySelector('.btn-remove-row');

            function updateRowValue() {
                const q = parseFloat(qtyInput.value) || 0;
                const r = parseFloat(rateInput.value) || 0;
                valInput.value = (q * r).toFixed(2);
                calculateGrandTotals();
            }

            qtyInput.addEventListener('input', updateRowValue);
            rateInput.addEventListener('input', updateRowValue);

            removeBtn.addEventListener('click', () => {
                const totalRows = container.querySelectorAll('tr').length;
                if (totalRows <= 1) {
                    alert('At least one item row is required and cannot be deleted.');
                    return;
                }
                tr.remove();
                updateRemoveButtons();
                calculateGrandTotals();
            });

            rowIndex++;
            updateRemoveButtons();
            calculateGrandTotals();
        }

        function updateRemoveButtons() {
            const rows = container.querySelectorAll('tr');
            rows.forEach(r => {
                const btn = r.querySelector('.btn-remove-row');
                if (btn) {
                    if (rows.length <= 1) {
                        btn.style.opacity = '0.35';
                        btn.style.cursor = 'not-allowed';
                        btn.title = 'At least one item row is required';
                    } else {
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                        btn.title = 'Remove Row';
                    }
                }
            });
        }

        function calculateGrandTotals() {
            let totalQty = 0;
            let totalVal = 0;

            container.querySelectorAll('tr').forEach(tr => {
                const q = parseFloat(tr.querySelector('.row-qty')?.value) || 0;
                const r = parseFloat(tr.querySelector('.row-rate')?.value) || 0;
                totalQty += q;
                totalVal += (q * r);
            });

            document.getElementById('grand-total-qty').textContent = totalQty.toLocaleString('en-IN', {maximumFractionDigits: 2});
            document.getElementById('grand-total-val').textContent = '₹ ' + totalVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Initialize rows: by default 1 row
        if (existingRows && existingRows.length > 0) {
            existingRows.forEach(row => renderRow(row));
        } else {
            renderRow();
        }

        addBtn.addEventListener('click', () => {
            renderRow();
        });

        // Initialize header select2
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#Supplier').select2();
        }
    });
</script>
@endsection
