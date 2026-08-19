@extends('layouts.app')

@section('title', $quotation->exists ? 'Edit Vendor Quotation' : 'Create Vendor Quotation')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $quotation->exists ? 'Edit Vendor Quotation' : 'Create Vendor Quotation' }}</h1>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        @if($quotation->exists)
            <a href="{{ route('store.quotation.print', $quotation->ID) }}" target="_blank" class="btn-print-blue">
                <span>📄</span> <span>Print / Download PDF</span>
            </a>
        @endif
        <a href="{{ route('store.quotation.index') }}" class="btn-action-secondary btn-close-circle" title="Close">
            ✕
        </a>
    </div>
</div>

<div class="card" style="margin-top: 1rem;">

    <form action="{{ $quotation->exists ? route('store.quotation.update', $quotation->ID) : route('store.quotation.store') }}" method="POST" id="quotation-form">
        @csrf
        @if($quotation->exists)
            @method('PUT')
        @endif

        <!-- Row 1: Quotation Number, Date, Supplier -->
        <div class="form-grid-3">
            <div class="form-group">
                <label for="QuotationNumber" class="form-label-custom">Quotation No. <span class="form-label-muted">(Auto-Generated)</span></label>
                <input type="text" id="QuotationNumber" name="QuotationNumber" value="{{ old('QuotationNumber', $quotation->QuotationNumber) }}" readonly class="form-control-readonly">
                @error('QuotationNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="QuotationDate" class="form-label-custom">Quotation Date <span class="required-star">*</span></label>
                <input type="date" id="QuotationDate" name="QuotationDate" value="{{ old('QuotationDate', $quotation->QuotationDate ? (is_string($quotation->QuotationDate) ? substr($quotation->QuotationDate, 0, 10) : $quotation->QuotationDate->format('Y-m-d')) : date('Y-m-d')) }}" required class="form-control-custom">
                @error('QuotationDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="Supplier" class="form-label-custom">Select Vendor / Supplier <span class="required-star">*</span></label>
                <select id="Supplier" name="Supplier" required class="form-control-custom">
                    <option value="">-- Choose Supplier --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->ID }}" {{ old('Supplier', $quotation->Supplier) == $sup->ID ? 'selected' : '' }}>
                            {{ $sup->SupplierName }} @if($sup->GSTIN) ({{ $sup->GSTIN }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('Supplier')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- MRL Date Range Filter Box -->
        <div class="mrl-filter-box">
            <div class="mrl-filter-header">
                <span>📅</span> <span>Fetch MRL Entries by Date Range</span>
            </div>

            <div class="mrl-filter-row">
                <div class="form-group mrl-date-group">
                    <label for="FromDate" class="form-label-custom">From Date</label>
                    <input type="date" id="FromDate" name="FromDate" value="{{ old('FromDate', $quotation->FromDate ? (is_string($quotation->FromDate) ? substr($quotation->FromDate, 0, 10) : $quotation->FromDate->format('Y-m-d')) : date('Y-m-d')) }}" class="form-control-custom">
                </div>

                <div class="form-group mrl-date-group">
                    <label for="ToDate" class="form-label-custom">To Date</label>
                    <input type="date" id="ToDate" name="ToDate" value="{{ old('ToDate', $quotation->ToDate ? (is_string($quotation->ToDate) ? substr($quotation->ToDate, 0, 10) : $quotation->ToDate->format('Y-m-d')) : date('Y-m-d')) }}" class="form-control-custom">
                </div>

                <div>
                    <button type="button" id="btn-fetch-mrl" class="btn-fetch-mrl">
                        🔍 Fetch MRL Items
                    </button>
                </div>
            </div>
            <div id="fetch-status-msg" class="fetch-status-msg"></div>
        </div>

        @if($errors->has('items'))
            <div class="alert alert-error">
                {{ $errors->first('items') }}
            </div>
        @endif

        <!-- Dynamic Items Table -->
        <div class="store-table-container">
            <table class="store-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Included</th>
                        <th style="width: 70px;">Sr No</th>
                        <th style="width: 120px;">MRL Date</th>
                        <th>Item Name</th>
                        <th style="width: 140px;">Part No.</th>
                        <th style="width: 140px;">Catalogue No.</th>
                        <th style="width: 180px;">Editable Quantity</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="quotation-items-body">
                    <!-- Rows inserted dynamically -->
                </tbody>
                <tfoot class="store-table-tfoot">
                    <tr>
                        <td colspan="8">
                            <button type="button" id="btn-add-manual-item" class="btn-add-manual-item">
                                + Add Custom Item Row
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Summary Row & Remarks -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="TotalItems" class="form-label-custom">Total Items Selected</label>
                <input type="number" id="TotalItems" name="TotalItems" value="{{ old('TotalItems', $quotation->TotalItems ?? 0) }}" readonly class="form-control-readonly">
            </div>

            <div class="form-group">
                <label for="TotalQuantity" class="form-label-custom">Total Quantity Selected</label>
                <input type="number" step="0.01" id="TotalQuantity" name="TotalQuantity" value="{{ old('TotalQuantity', $quotation->TotalQuantity ?? 0) }}" readonly class="form-control-readonly">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="Remarks" class="form-label-custom">Remarks / Terms & Conditions</label>
            <textarea id="Remarks" name="Remarks" rows="3" class="form-control-custom" placeholder="Enter quotation terms, delivery notes, or special instructions...">{{ old('Remarks', $quotation->Remarks) }}</textarea>
        </div>

        <!-- Save Button (Green) & Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-save-green">
                Save Quotation
            </button>

            @if($quotation->exists)
                <a href="{{ route('store.quotation.print', $quotation->ID) }}" target="_blank" class="btn-print-blue">
                    <span>📄</span> <span>Print / Download PDF</span>
                </a>
            @endif

            <a href="{{ route('store.quotation.index') }}" class="btn-action-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableBody = document.getElementById('quotation-items-body');
        const btnFetchMrl = document.getElementById('btn-fetch-mrl');
        const btnAddManual = document.getElementById('btn-add-manual-item');
        const fromDateInput = document.getElementById('FromDate');
        const toDateInput = document.getElementById('ToDate');
        const fetchStatusMsg = document.getElementById('fetch-status-msg');
        const totalItemsInput = document.getElementById('TotalItems');
        const totalQtyInput = document.getElementById('TotalQuantity');

        const itemListOptions = @json($itemList ?? []);
        const existingChildren = @json(old('items', $quotation->children ?? []));

        function calculateTotals() {
            const rows = tableBody.querySelectorAll('tr');
            let totalItems = 0;
            let totalQty = 0;

            rows.forEach((row, index) => {
                const srCell = row.querySelector('.sr-no');
                if (srCell) srCell.textContent = index + 1;

                const isCheckedBox = row.querySelector('.item-checkbox');
                const isChecked = isCheckedBox ? isCheckedBox.checked : true;
                const qtyInput = row.querySelector('.qty-input');
                const itemSelect = row.querySelector('.item-select');
                const mrlChildHidden = row.querySelector('.mrl-child-hidden');

                if (isChecked) {
                    if (itemSelect) itemSelect.name = `items[${totalItems}][ItemMaster]`;
                    if (mrlChildHidden) mrlChildHidden.name = `items[${totalItems}][MRLEntryChild]`;
                    if (qtyInput) {
                        qtyInput.name = `items[${totalItems}][Quantity]`;
                        const qtyVal = parseFloat(qtyInput.value) || 0;
                        totalQty += qtyVal;
                    }
                    totalItems++;
                    row.style.opacity = '1';
                } else {
                    if (itemSelect) itemSelect.removeAttribute('name');
                    if (mrlChildHidden) mrlChildHidden.removeAttribute('name');
                    if (qtyInput) qtyInput.removeAttribute('name');
                    row.style.opacity = '0.4';
                }
            });

            totalItemsInput.value = totalItems;
            totalQtyInput.value = totalQty.toFixed(2);
        }

        function createRow(data = {}) {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e2e8f0';

            const selectedItemId = data.ItemMaster || data.item_id || data.ItemMasterID || '';
            const mrlChildId = data.MRLEntryChild || data.mrl_child_id || '';
            const entryDate = data.entry_date || (data.mrlChildRelation ? data.mrlChildRelation.mrlEntryRelation?.EntryDate : '-') || '-';
            const partNo = data.part_no || (data.itemMasterRelation ? data.itemMasterRelation.PartNo : '') || '';
            const catNo = data.catalogue_no || (data.itemMasterRelation ? data.itemMasterRelation.CatalogueNo : '') || '';
            const quantityVal = data.Quantity || data.quantity || '';

            let itemOptionsHtml = '<option value="">Select Item</option>';
            itemListOptions.forEach(item => {
                const isSel = String(selectedItemId) === String(item.ID) ? 'selected' : '';
                itemOptionsHtml += `<option value="${item.ID}" data-part="${item.PartNo || ''}" data-cat="${item.CatalogueNo || ''}" ${isSel}>${item.ItemName}</option>`;
            });

            tr.innerHTML = `
                <td style="text-align: center; padding: 10px;">
                    <input type="checkbox" class="item-checkbox" checked style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                    <input type="hidden" class="mrl-child-hidden" value="${mrlChildId}">
                </td>
                <td class="sr-no" style="text-align: center; padding: 10px; font-weight: 600; color: #475569;">1</td>
                <td style="text-align: center; padding: 10px; font-size: 0.85rem; color: #64748b;">${entryDate}</td>
                <td style="padding: 8px 10px;">
                    <select class="item-select" required style="width: 100%; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0.4rem 0.6rem; font-size: 0.9rem;">
                        ${itemOptionsHtml}
                    </select>
                </td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="part-no-input" value="${partNo}" readonly style="width: 100%; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.4rem 0.6rem; font-size: 0.85rem; color: #64748b;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="cat-no-input" value="${catNo}" readonly style="width: 100%; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.4rem 0.6rem; font-size: 0.85rem; color: #64748b;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="number" step="0.01" min="0.01" class="qty-input" value="${quantityVal}" required placeholder="0.00" style="width: 100%; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0.4rem 0.6rem; font-weight: 600; font-size: 0.9rem;">
                </td>
                <td style="text-align: center; padding: 8px;">
                    <button type="button" class="btn-remove-row" style="background: transparent; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Remove Row">🗑️</button>
                </td>
            `;

            const checkbox = tr.querySelector('.item-checkbox');
            const qtyInput = tr.querySelector('.qty-input');
            const itemSelect = tr.querySelector('.item-select');
            const partNoInput = tr.querySelector('.part-no-input');
            const catNoInput = tr.querySelector('.cat-no-input');
            const btnRemove = tr.querySelector('.btn-remove-row');

            checkbox.addEventListener('change', calculateTotals);
            qtyInput.addEventListener('input', calculateTotals);

            itemSelect.addEventListener('change', (e) => {
                const opt = e.target.options[e.target.selectedIndex];
                if (opt) {
                    partNoInput.value = opt.getAttribute('data-part') || '';
                    catNoInput.value = opt.getAttribute('data-cat') || '';
                }
                calculateTotals();
            });

            btnRemove.addEventListener('click', () => {
                tr.remove();
                calculateTotals();
            });

            tableBody.appendChild(tr);
            calculateTotals();
        }

        // Fetch MRL Items AJAX
        btnFetchMrl.addEventListener('click', async () => {
            const fromDate = fromDateInput.value;
            const toDate = toDateInput.value;

            if (!fromDate || !toDate) {
                alert('Please select both From Date and To Date to fetch MRL entries.');
                return;
            }

            fetchStatusMsg.style.color = '#1e40af';
            fetchStatusMsg.textContent = 'Fetching MRL items...';

            try {
                const url = "{{ route('store.quotation.fetchMrl') }}?from_date=" + encodeURIComponent(fromDate) + "&to_date=" + encodeURIComponent(toDate);
                const response = await fetch(url);
                const res = await response.json();

                if (res.success) {
                    tableBody.innerHTML = '';
                    if (res.data.length === 0) {
                        fetchStatusMsg.style.color = '#d97706';
                        fetchStatusMsg.textContent = 'No MRL entries found in the selected date range.';
                    } else {
                        fetchStatusMsg.style.color = '#059669';
                        fetchStatusMsg.textContent = `Found ${res.count} item(s) from MRL entries between ${fromDate} and ${toDate}.`;
                        res.data.forEach(item => createRow(item));
                    }
                } else {
                    fetchStatusMsg.style.color = '#dc2626';
                    fetchStatusMsg.textContent = res.message || 'Error fetching MRL items.';
                }
            } catch (err) {
                console.error('Fetch MRL error:', err);
                fetchStatusMsg.style.color = '#dc2626';
                fetchStatusMsg.textContent = 'Failed to fetch MRL entries.';
            }
        });

        btnAddManual.addEventListener('click', () => {
            createRow();
        });

        // Load existing children if editing or validation fail
        if (existingChildren && existingChildren.length > 0) {
            existingChildren.forEach(child => createRow(child));
        }
    });
</script>
@endsection
