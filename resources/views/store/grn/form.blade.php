@extends('layouts.app')

@section('title', $grn->exists ? 'Edit GRN' : 'Add New GRN')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $grn->exists ? 'Edit GRN' : 'Add New GRN' }}</h1>
    </div>
    <a href="{{ route('store.grn.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;" title="Close">
        ✕
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
        <strong style="font-weight: 700;">Please fix the following errors:</strong>
        <ul style="margin-top: 0.5rem; margin-bottom: 0; padding-left: 1.25rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="grnForm" action="{{ $grn->exists ? route('store.grn.update', $grn->ID) : route('store.grn.store') }}" method="POST">
    @csrf
    @if($grn->exists)
        @method('PUT')
    @endif

    <input type="hidden" id="current_grn_id" value="{{ $grn->ID ?? '' }}">

    <!-- Top Fields Card matching PI form layout -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; align-items: end;">
            
            <!-- GRN No. (col 2) -->
            <div style="grid-column: span 2;">
                <label for="GRNNumber" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    GRN No.
                </label>
                <input type="text" id="GRNNumber" name="GRNNumber" value="{{ old('GRNNumber', $grn->GRNNumber) }}" class="form-control" readonly style="background-color: #f8fafc; font-weight: 600; color: #64748b; font-size: 0.85rem;" placeholder="Auto Issued">
            </div>

            <!-- GRN Entry Date (col 2) -->
            <div style="grid-column: span 2;">
                <label for="GRNDate" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    GRN Entry Date <span style="color: #ef4444;">*</span>
                </label>
                <input type="date" id="GRNDate" name="GRNDate" value="{{ old('GRNDate', $grn->GRNDate) }}" class="form-control @error('GRNDate') is-invalid @enderror" required style="font-size: 0.85rem;">
            </div>

            <!-- Invoice Date (col 2) -->
            <div style="grid-column: span 2;">
                <label for="InvoiceDate" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Invoice Date
                </label>
                <input type="date" id="InvoiceDate" name="InvoiceDate" value="{{ old('InvoiceDate', $grn->InvoiceDate) }}" class="form-control @error('InvoiceDate') is-invalid @enderror" style="font-size: 0.85rem;">
            </div>

            <!-- Invoice No. (col 2) -->
            <div style="grid-column: span 2;">
                <label for="InvoiceNo" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Invoice No.
                </label>
                <input type="text" id="InvoiceNo" name="InvoiceNo" value="{{ old('InvoiceNo', $grn->InvoiceNo) }}" class="form-control @error('InvoiceNo') is-invalid @enderror" placeholder="Enter Invoice No" style="font-size: 0.85rem;">
            </div>

            <!-- Supplier (col 4) -->
            <div style="grid-column: span 4;">
                <label for="Supplier" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Supplier <span style="color: #ef4444;">*</span>
                </label>
                <select id="Supplier" name="Supplier" class="form-control @error('Supplier') is-invalid @enderror" required style="font-size: 0.85rem; width: 100%;">
                    <option value="">-- Select Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->ID }}" {{ old('Supplier', $grn->Supplier) == $supplier->ID ? 'selected' : '' }}>
                            {{ $supplier->SupplierName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- PI No. (col 6) -->
            <div style="grid-column: span 6;">
                <label for="PINumbers" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    PI No. <span style="color: #ef4444;">*</span>
                </label>
                <select id="PINumbers" name="PINumbers[]" class="form-control @error('PINumbers') is-invalid @enderror" multiple="multiple" required style="font-size: 0.85rem; width: 100%;">
                    <!-- Dynamic options loaded via AJAX -->
                </select>
            </div>

            <!-- Remarks (col 6) -->
            <div style="grid-column: span 6;">
                <label for="Remarks" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Remarks
                </label>
                <textarea name="Remarks" id="Remarks" class="form-control" rows="2" placeholder="e.g. Bearing 6205 - 20 Nos Received" style="font-size: 0.85rem;">{{ old('Remarks', $grn->Remarks) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Item Details Card matching PI table layout -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0;">Item Details</h3>
        </div>

        <div style="overflow-x: auto; width: 100%;">
            <table class="table" id="itemsTable" style="width: 100%; border-collapse: collapse; min-width: 850px; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; text-align: left; font-size: 0.8rem;">
                        <th style="padding: 0.5rem 0.4rem; width: 26%;">Item Name</th>
                        <th style="padding: 0.5rem 0.4rem; width: 12%;">Qty Received</th>
                        <th style="padding: 0.5rem 0.4rem; width: 12%;">Rate (₹)</th>
                        <th style="padding: 0.5rem 0.4rem; width: 14%;">Amount (₹)</th>
                        <th style="padding: 0.5rem 0.4rem; width: 10%;">GST (%)</th>
                        <th style="padding: 0.5rem 0.4rem; width: 14%;">GST Amount (₹)</th>
                        <th style="padding: 0.5rem 0.4rem; width: 8%;">Rack / Bin</th>
                        <th style="padding: 0.5rem 0.4rem; width: 4%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <tr id="emptyRow">
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                            Select a Supplier and PI No. to load items.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total Amount Card matching summary -->
        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 1.5rem; margin-top: 1.25rem; margin-bottom: 1.25rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #334155; margin: 0;">Total Amount (₹):</label>
                <div style="width: 130px;">
                    <input type="text" id="TotalAmount" class="form-control" readonly style="background: #f8fafc; font-weight: 700; color: #1e293b; border-color: #cbd5e1; font-size: 0.9rem; text-align: right; padding: 0.4rem 0.6rem;" value="{{ number_format($grn->TotalAmount ?? 0, 2) }}">
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #334155; margin: 0;">GST Amount (₹):</label>
                <div style="width: 130px;">
                    <input type="text" id="TotalGSTAmount" class="form-control" readonly style="background: #f8fafc; font-weight: 700; color: #d97706; border-color: #fcd34d; font-size: 0.9rem; text-align: right; padding: 0.4rem 0.6rem;" value="{{ number_format($grn->TotalGSTAmount ?? 0, 2) }}">
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #334155; margin: 0;">Grand Total (₹):</label>
                <div style="width: 150px;">
                    <input type="text" id="GrandTotal" class="form-control" readonly style="background: #eef2ff; font-weight: 800; color: #3730a3; border-color: #c7d2fe; font-size: 0.95rem; text-align: right; padding: 0.4rem 0.6rem;" value="{{ number_format($grn->GrandTotal ?? 0, 2) }}">
                </div>
            </div>
        </div>

        <!-- Form Actions Bar matching PI form buttons -->
        <div style="display: flex; gap: 0.75rem; align-items: center; padding-top: 0.5rem;">
            <button type="submit" name="action_type" value="save" class="btn-save-green">
                Save
            </button>
            <button type="button" id="btnClear" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.88rem;">
                Clear
            </button>
            <a href="{{ route('store.grn.index') }}" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center;">
                Cancel
            </a>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        const currentGrnId = $('#current_grn_id').val();
        const preselectedPiIds = @json($selectedPiIds ?? []);

        // Initialize Select2 matching PI form
        $('#Supplier').select2({
            placeholder: '-- Select Supplier --',
            allowClear: true,
            width: '100%'
        });

        $('#PINumbers').select2({
            placeholder: '-- Select PI No --',
            allowClear: true,
            width: '100%'
        });

        // Supplier Change -> Fetch Pending PIs
        $('#Supplier').on('change', function () {
            const supplierId = $(this).val();
            const piSelect = $('#PINumbers');
            piSelect.empty();
            $('#itemsTableBody').html(`
                <tr id="emptyRow">
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                        Select a Supplier and PI No. to load items.
                    </td>
                </tr>
            `);
            calculateTotals();

            if (!supplierId) {
                return;
            }

            $.ajax({
                url: "{{ route('store.grn.fetchPIs') }}",
                type: "GET",
                data: {
                    supplier_id: supplierId,
                    current_grn_id: currentGrnId
                },
                success: function (res) {
                    if (res.success && res.data) {
                        piSelect.empty();
                        res.data.forEach(function (pi) {
                            const option = new Option(pi.pi_number, pi.id, false, false);
                            piSelect.append(option);
                        });

                        // If editing, set preselected PIs
                        if (preselectedPiIds && preselectedPiIds.length > 0) {
                            piSelect.val(preselectedPiIds).trigger('change');
                        } else {
                            piSelect.trigger('change.select2');
                        }
                    }
                },
                error: function (err) {
                    console.error('Error fetching PIs:', err);
                }
            });
        });

        // PI Numbers Change -> Fetch Items
        $('#PINumbers').on('change', function () {
            const selectedPiIds = $(this).val();
            const tableBody = $('#itemsTableBody');

            if (!selectedPiIds || selectedPiIds.length === 0) {
                tableBody.html(`
                    <tr id="emptyRow">
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                            Select a Supplier and PI No. to load items.
                        </td>
                    </tr>
                `);
                calculateTotals();
                return;
            }

            $.ajax({
                url: "{{ route('store.grn.fetchPIItems') }}",
                type: "GET",
                data: {
                    pi_ids: selectedPiIds,
                    current_grn_id: currentGrnId
                },
                success: function (res) {
                    if (res.success && res.data) {
                        tableBody.empty();
                        if (res.data.length === 0) {
                            tableBody.html(`
                                <tr id="emptyRow">
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                                        No pending items found for the selected PI(s).
                                    </td>
                                </tr>
                            `);
                            calculateTotals();
                            return;
                        }

                        res.data.forEach(function (item, index) {
                            const rateVal = parseFloat(item.rate || 0).toFixed(2);
                            const gstRateVal = parseFloat(item.gst_rate || 0).toFixed(2);

                            const rowHtml = `
                                <tr class="item-row" style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 0.4rem;">
                                        <input type="hidden" name="items[${index}][PI]" value="${item.pi_id}">
                                        <input type="hidden" name="items[${index}][PIChild]" value="${item.pi_child_id}">
                                        <input type="hidden" name="items[${index}][ItemMaster]" value="${item.item_id}">
                                        <input type="text" class="form-control" value="${escapeHtml(item.item_name)}" readonly style="background-color: #f8fafc; font-weight: 600; color: #1e293b; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="number" step="0.01" min="0" name="items[${index}][Quantity]" class="form-control qty-input" value="${item.qty_received}" required style="font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="hidden" name="items[${index}][Rate]" value="${rateVal}">
                                        <input type="text" class="form-control rate-input" value="${rateVal}" readonly style="background-color: #f8fafc; color: #64748b; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="text" class="form-control amount-input" value="0.00" readonly style="background-color: #f8fafc; font-weight: 600; color: #1e293b; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="hidden" name="items[${index}][GSTRate]" value="${gstRateVal}">
                                        <input type="text" class="form-control gst-rate-input" value="${gstRateVal}" readonly style="background-color: #f8fafc; color: #64748b; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="text" class="form-control gst-amount-input" value="0.00" readonly style="background-color: #f8fafc; font-weight: 600; color: #d97706; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem;">
                                        <input type="text" class="form-control" value="${escapeHtml(item.rack_no || 'N/A')}" readonly style="background-color: #f8fafc; color: #64748b; font-size: 0.85rem;">
                                    </td>
                                    <td style="padding: 0.4rem; text-align: center; vertical-align: middle;">
                                        <button type="button" class="btn-delete-row" style="background:none; border:none; color:#ef4444; font-size:0.95rem; cursor:pointer;" title="Remove row">🗑️</button>
                                    </td>
                                </tr>
                            `;
                            tableBody.append(rowHtml);
                        });

                        calculateTotals();
                    }
                },
                error: function (err) {
                    console.error('Error fetching PI items:', err);
                }
            });
        });

        // Delegate Quantity and GST Rate input changes
        $(document).on('input', '.qty-input, .gst-rate-input', function () {
            calculateTotals();
        });

        // Delegate Row Delete
        $(document).on('click', '.btn-delete-row', function () {
            $(this).closest('tr').remove();
            if ($('#itemsTableBody tr.item-row').length === 0) {
                $('#itemsTableBody').html(`
                    <tr id="emptyRow">
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                            No items in entry.
                        </td>
                    </tr>
                `);
            }
            calculateTotals();
        });

        // Clear Form Button
        $('#btnClear').on('click', function () {
            if (confirm('Are you sure you want to clear the form?')) {
                $('#grnForm')[0].reset();
                $('#Supplier').val('').trigger('change.select2');
                $('#PINumbers').empty().trigger('change.select2');
                $('#itemsTableBody').html(`
                    <tr id="emptyRow">
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">
                            Select a Supplier and PI No. to load items.
                        </td>
                    </tr>
                `);
                calculateTotals();
            }
        });

        // Calculate Totals function
        function calculateTotals() {
            let totalAmount = 0;
            let totalGSTAmount = 0;
            let grandTotal = 0;

            $('.item-row').each(function () {
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                const rate = parseFloat($(this).find('.rate-input').val()) || 0;
                const gstRate = parseFloat($(this).find('.gst-rate-input').val()) || 0;

                const amount = qty * rate;
                const gstAmount = amount * (gstRate / 100);

                $(this).find('.amount-input').val(amount.toFixed(2));
                $(this).find('.gst-amount-input').val(gstAmount.toFixed(2));

                totalAmount += amount;
                totalGSTAmount += gstAmount;
            });

            grandTotal = totalAmount + totalGSTAmount;

            $('#TotalAmount').val(totalAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#TotalGSTAmount').val(totalGSTAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#GrandTotal').val(grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Trigger Supplier load if editing
        if ($('#Supplier').val()) {
            $('#Supplier').trigger('change');
        }
    });
</script>
@endsection
