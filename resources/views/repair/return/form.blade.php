@extends('layouts.app')

@section('title', $repairReturn->exists ? 'Edit Repair Material Return' : 'Add Repair Material Return')

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

    .return-table th {
        background: var(--bg-surface-hover, #f8fafc);
        padding: 0.65rem 0.75rem;
        font-weight: 600;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        text-align: left;
    }
    .return-table td {
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
        vertical-align: middle;
    }
    .input-readonly {
        background-color: #f8fafc !important;
        color: #475569 !important;
        cursor: not-allowed;
    }
    .badge-pending {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-received {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $repairReturn->exists ? 'Edit Repair Material Return' : 'Add Repair Material Return' }}</h1>
        <p>{{ $repairReturn->exists ? 'Update repair material return entry' : 'Receive repaired materials back from vendor against repair issue' }}</p>
    </div>
    <a href="{{ route('repair.return.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; text-decoration: none;" title="Close">
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

<form action="{{ $repairReturn->exists ? route('repair.return.update', $repairReturn->ID) : route('repair.return.store') }}" method="POST" id="repair-return-form">
    @csrf
    @if($repairReturn->exists)
        @method('PUT')
    @endif

    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Header Fields -->
        <div class="form-grid-header">
            <div class="form-group">
                <label for="ReturnNo" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Return No.</label>
                <input type="text" id="ReturnNo" name="ReturnNo" class="form-control" value="{{ old('ReturnNo', $repairReturn->ReturnNo) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group">
                <label for="ReturnDate" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Return Date</label>
                <input type="date" id="ReturnDate" name="ReturnDate" class="form-control" value="{{ old('ReturnDate', $repairReturn->ReturnDate ? (is_string($repairReturn->ReturnDate) ? $repairReturn->ReturnDate : $repairReturn->ReturnDate->format('Y-m-d')) : date('Y-m-d')) }}" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
            </div>

            <div class="form-group">
                <label for="RepairIssue" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Select Repair No.</label>
                <select id="RepairIssue" name="RepairIssue" class="form-control select2-field" style="width: 100%;" required {{ $repairReturn->exists ? 'disabled' : '' }}>
                    <option value="">-- Select Repair Issue No. --</option>
                    @foreach($repairs as $repair)
                        <option value="{{ $repair->ID }}" 
                            data-supplier-id="{{ $repair->Supplier }}"
                            data-supplier-name="{{ $repair->supplierRelation->SupplierName ?? '' }}"
                            {{ old('RepairIssue', $repairReturn->RepairIssue) == $repair->ID ? 'selected' : '' }}>
                            {{ $repair->RepairNo }} ({{ $repair->supplierRelation->SupplierName ?? 'N/A' }} - {{ date('d/m/Y', strtotime($repair->RepairDate)) }})
                        </option>
                    @endforeach
                </select>
                @if($repairReturn->exists)
                    <input type="hidden" name="RepairIssue" value="{{ $repairReturn->RepairIssue }}">
                @endif
            </div>

            <div class="form-group">
                <label for="SupplierNameDisplay" class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Supplier Name</label>
                <input type="text" id="SupplierNameDisplay" class="form-control input-readonly" value="{{ $repairReturn->supplierRelation->SupplierName ?? '' }}" placeholder="Auto loaded from Repair Issue" readonly style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                <input type="hidden" id="Supplier" name="Supplier" value="{{ old('Supplier', $repairReturn->Supplier) }}">
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color, #e5e7eb); margin: 1.5rem 0;">

        <!-- Items Table -->
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Return Item Details</h3>
                <span style="font-size: 0.85rem; color: #64748b;">(Jo item abhi pending hai uska Return Qty <b>0</b> set karein)</span>
            </div>

            <div id="no-repair-selected-notice" style="display: none; padding: 2rem; text-align: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b;">
                Please select a Repair Issue No. from above to load items.
            </div>

            <div id="items-table-wrapper" style="overflow-x: auto; width: 100%;">
                <table class="return-table" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Item Name</th>
                            <th style="width: 15%; text-align: center;">Issued Qty</th>
                            <th style="width: 15%;">Return / Received Qty</th>
                            <th style="width: 15%;">Rate</th>
                            <th style="width: 15%;">Value</th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <!-- Loaded dynamically via AJAX or populated on edit -->
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--bg-surface-hover, #f8fafc); font-weight: 600; border-top: 2px solid var(--border-color, #cbd5e1);">
                            <td style="text-align: right; padding: 0.75rem;">Total:</td>
                            <td id="grand-issued-qty" style="text-align: center; padding: 0.75rem;">0</td>
                            <td id="grand-returned-qty" style="padding: 0.75rem;">0</td>
                            <td></td>
                            <td id="grand-total-val" style="padding: 0.75rem;">₹ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 0.9rem; font-weight: 600;">Calculated Return Status:</span>
                <span id="status-badge" class="badge-pending">Pending</span>
            </div>
        </div>

        <!-- Actions Footer -->
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center; margin-top: 1.5rem;">
            <button type="submit" class="btn-save-green" style="background: #10b981; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Save
            </button>
            <a href="{{ route('repair.return.index') }}" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center; background: #e2e8f0; color: #334155; border-radius: 6px;">
                Cancel
            </a>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const repairSelect = document.getElementById('RepairIssue');
        const supplierDisplay = document.getElementById('SupplierNameDisplay');
        const supplierHidden = document.getElementById('Supplier');
        const container = document.getElementById('items-container');
        const tableWrapper = document.getElementById('items-table-wrapper');
        const noticeWrapper = document.getElementById('no-repair-selected-notice');
        const statusBadge = document.getElementById('status-badge');

        const isEditMode = {{ $repairReturn->exists ? 'true' : 'false' }};
        const existingChildren = @json(old('items', $repairReturn->exists ? $repairReturn->children->toArray() : []));

        function updateStatusBadge() {
            let allReceived = true;
            let rows = container.querySelectorAll('tr');
            if (rows.length === 0) {
                statusBadge.textContent = 'Pending';
                statusBadge.className = 'badge-pending';
                return;
            }

            rows.forEach(tr => {
                const issued = parseFloat(tr.querySelector('.row-issued-qty')?.value) || 0;
                const ret = parseFloat(tr.querySelector('.row-return-qty')?.value) || 0;
                if (ret < issued) {
                    allReceived = false;
                }
            });

            if (allReceived) {
                statusBadge.textContent = 'Received (Complete)';
                statusBadge.className = 'badge-received';
            } else {
                statusBadge.textContent = 'Pending (Partial Items)';
                statusBadge.className = 'badge-pending';
            }
        }

        function calculateGrandTotals() {
            let totalIssued = 0;
            let totalReturned = 0;
            let totalVal = 0;

            container.querySelectorAll('tr').forEach(tr => {
                const issued = parseFloat(tr.querySelector('.row-issued-qty')?.value) || 0;
                const ret = parseFloat(tr.querySelector('.row-return-qty')?.value) || 0;
                const rate = parseFloat(tr.querySelector('.row-rate')?.value) || 0;

                totalIssued += issued;
                totalReturned += ret;
                totalVal += (ret * rate);
            });

            document.getElementById('grand-issued-qty').textContent = totalIssued.toLocaleString('en-IN', {maximumFractionDigits: 2});
            document.getElementById('grand-returned-qty').textContent = totalReturned.toLocaleString('en-IN', {maximumFractionDigits: 2});
            document.getElementById('grand-total-val').textContent = '₹ ' + totalVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            updateStatusBadge();
        }

        function renderRow(item, index) {
            const tr = document.createElement('tr');

            const issuedQty = item.IssuedQuantity !== undefined ? parseFloat(item.IssuedQuantity) : (item.issued_qty !== undefined ? parseFloat(item.issued_qty) : 0);
            const returnQty = item.Quantity !== undefined ? parseFloat(item.Quantity) : (item.return_qty !== undefined ? parseFloat(item.return_qty) : issuedQty);
            const rate = item.Rate !== undefined ? parseFloat(item.Rate) : (item.rate !== undefined ? parseFloat(item.rate) : 0);
            const val = (returnQty * rate).toFixed(2);

            const itemName = item.item_name || (item.item_master_relation ? item.item_master_relation.ItemName : ('Item #' + (item.ItemMaster || item.item_id)));
            const itemId = item.ItemMaster || item.item_id;
            const repairChildId = item.RepairIssueChild || item.repair_child_id || '';

            tr.innerHTML = `
                <td>
                    <span style="font-weight: 500;">${itemName}</span>
                    <input type="hidden" name="items[${index}][ItemMaster]" value="${itemId}">
                    <input type="hidden" name="items[${index}][RepairIssueChild]" value="${repairChildId}">
                </td>
                <td style="text-align: center;">
                    <span style="font-weight: 600; color: #475569;">${issuedQty}</span>
                    <input type="hidden" class="row-issued-qty" name="items[${index}][IssuedQuantity]" value="${issuedQty}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" max="${issuedQty}" name="items[${index}][Quantity]" class="form-control row-return-qty" value="${returnQty}" placeholder="0 if pending" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${index}][Rate]" class="form-control row-rate input-readonly" value="${rate}" readonly style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
                <td>
                    <!-- User instruction: value readonly hoga -->
                    <input type="text" class="form-control row-value input-readonly" value="${val}" readonly style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;">
                </td>
            `;

            container.appendChild(tr);

            const returnQtyInput = tr.querySelector('.row-return-qty');
            const rateInput = tr.querySelector('.row-rate');
            const valInput = tr.querySelector('.row-value');

            returnQtyInput.addEventListener('input', () => {
                let q = parseFloat(returnQtyInput.value) || 0;
                if (q > issuedQty) {
                    alert(`Return quantity cannot exceed issued quantity of ${issuedQty}. Setting to ${issuedQty}.`);
                    returnQtyInput.value = issuedQty;
                    q = issuedQty;
                }
                if (q < 0) {
                    returnQtyInput.value = 0;
                    q = 0;
                }
                const r = parseFloat(rateInput.value) || 0;
                valInput.value = (q * r).toFixed(2);
                calculateGrandTotals();
            });
        }

        function loadRepairDetails(repairId) {
            if (!repairId) {
                container.innerHTML = '';
                tableWrapper.style.display = 'none';
                noticeWrapper.style.display = 'block';
                supplierDisplay.value = '';
                supplierHidden.value = '';
                calculateGrandTotals();
                return;
            }

            // Fetch via AJAX
            fetch(`{{ route('repair.return.fetchRepairDetails') }}?repair_id=${repairId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        supplierDisplay.value = data.repair.supplier_name;
                        supplierHidden.value = data.repair.supplier_id;

                        container.innerHTML = '';
                        if (data.items && data.items.length > 0) {
                            tableWrapper.style.display = 'block';
                            noticeWrapper.style.display = 'none';
                            data.items.forEach((item, idx) => {
                                renderRow(item, idx);
                            });
                            calculateGrandTotals();
                        } else {
                            tableWrapper.style.display = 'none';
                            noticeWrapper.style.display = 'block';
                            noticeWrapper.textContent = 'No items found for the selected Repair Issue.';
                        }
                    } else {
                        alert(data.message || 'Error loading repair details.');
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('An error occurred while loading repair details.');
                });
        }

        // Initialize on change of RepairIssue
        repairSelect.addEventListener('change', (e) => {
            loadRepairDetails(e.target.value);
        });

        // Initialize on page load
        if (isEditMode && existingChildren && existingChildren.length > 0) {
            tableWrapper.style.display = 'block';
            noticeWrapper.style.display = 'none';
            existingChildren.forEach((child, idx) => {
                renderRow(child, idx);
            });
            calculateGrandTotals();
        } else if (repairSelect.value) {
            loadRepairDetails(repairSelect.value);
        } else {
            tableWrapper.style.display = 'none';
            noticeWrapper.style.display = 'block';
        }

        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#RepairIssue').select2().on('select2:select', function(e) {
                loadRepairDetails(e.target.value);
            });
        }
    });
</script>
@endsection
