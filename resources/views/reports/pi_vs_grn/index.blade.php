@extends('layouts.app')

@section('title', 'Proforma Invoice Vs Goods Received')

@section('content')
<div class="content-header report-header">
    <div class="content-title">
        <h1>Proforma Invoice Vs Goods Received</h1>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<!-- Filter Section -->
<div class="card report-filter-card" style="width: 100%; margin-bottom:0.25em !important;">
    <form method="GET" action="{{ route('reports.pi_vs_grn.index') }}" class="report-filter-form" style="display: flex; flex-wrap: wrap; gap: 0.25rem; align-items: flex-end;">
        <!-- 1 From Date -->
        <div class="form-group" style="flex: 1; min-width: 130px;">
            <label for="from_date" style="font-weight: 600; font-size: 0.25rem; margin-bottom: 4px; display: block;">From Date</label>
            <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <!-- 2 To Date -->
        <div class="form-group" style="flex: 1; min-width: 130px;">
            <label for="to_date" style="font-weight: 600; font-size: 0.825rem; margin-bottom: 4px; display: block;">To Date</label>
            <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <!-- 3 Supplier Name -->
        <div class="form-group" style="flex: 1.2; min-width: 160px;">
            <label for="supplier_id" style="font-weight: 700; font-size: 0.825rem; margin-bottom: 4px; display: block;">Supplier Name</label>
            <select name="supplier_id" id="supplier_id" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->ID }}" {{ $supplierId == $supplier->ID ? 'selected' : '' }}>
                        {{ $supplier->SupplierName }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 4 PI No -->
        <div class="form-group" style="flex: 1.1; min-width: 140px;">
            <label for="pi_id" style="font-weight: 600; font-size: 0.825rem; margin-bottom: 4px; display: block;">PI No</label>
            <select name="pi_id" id="pi_id" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">All PIs</option>
                @foreach($pis as $pi)
                    <option value="{{ $pi->ID }}" {{ $piId == $pi->ID ? 'selected' : '' }}>
                        {{ $pi->PINumber }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 5 Item Name -->
        <div class="form-group" style="flex: 1.2; min-width: 160px;">
            <label for="item_id" style="font-weight: 600; font-size: 0.825rem; margin-bottom: 4px; display: block;">Item Name</label>
            <select name="item_id" id="item_id" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">All Items</option>
                @foreach($items as $item)
                    <option value="{{ $item->ID }}" {{ $itemId == $item->ID ? 'selected' : '' }}>
                        {{ $item->ItemName }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 6 Status Dropdown -->
        <div class="form-group" style="flex: 1.1; min-width: 160px;">
            <label for="status" style="font-weight: 700; font-size: 0.825rem; margin-bottom: 4px; display: block; color: #2e7d32;">Status</label>
            <select name="status" id="status" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 2px solid #7cb342; background-color: #f1f8e9; font-weight: 600; color: #1b5e20; border-radius: 4px;">
                <option value="all" {{ $status == 'all' || $status == '1' ? 'selected' : '' }}>All</option>
                <option value="completed" {{ $status == 'completed' || $status == '2' ? 'selected' : '' }}>Completed (Closed)</option>
                <option value="partly" {{ $status == 'partly' || $status == '3' ? 'selected' : '' }}>Partly Received (Active)</option>
                <option value="pending" {{ $status == 'pending' || $status == '4' ? 'selected' : '' }}>Pending for Received (Active)</option>
            </select>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.4rem; align-items: center; flex-shrink: 0; padding-bottom: 2px;">
            <button type="submit" class="btn-action" style="padding: 0.45rem 1rem;">
                Filter
            </button>
            <a href="{{ route('reports.pi_vs_grn.index') }}" class="btn-action-secondary" style="text-decoration: none; padding: 0.45rem 1rem;">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Main Data Table -->
<div class="card report-table-card" style="overflow-x: auto; padding: 0;">
    <div style="text-align: center; padding: 0.25rem; font-weight: 800; font-size: 1.15rem; color: #0f172a; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
        Proforma Invoice Vs Goods Received
    </div>
    
    <table class="report-table" style="width: 100%; border-collapse: collapse; text-align: center; font-size: 0.85rem;">
        <thead>
            <!-- Level 1 Header -->
            <tr style="background-color: #f1f5f9; color: #0f172a; font-weight: 700; border-bottom: 1px solid #cbd5e1;">
                <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 8px; width: 45px;">Sr.</th>
                <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 8px; width: 110px;">PI No</th>
                <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 8px; width: 120px;">GRN No</th>
                <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 8px; text-align: left;">Item Name</th>
                <th colspan="2" style="border: 1px solid #cbd5e1; padding: 8px; background-color: #e2e8f0;">PI Details</th>
                <th colspan="2" style="border: 1px solid #cbd5e1; padding: 8px; background-color: #e2e8f0;">GRN Details</th>
                <th colspan="2" style="border: 1px solid #cbd5e1; padding: 8px; background-color: #e2e8f0;">Difference</th>
            </tr>
            <!-- Level 2 Sub-Header -->
            <tr style="background-color: #f8fafc; color: #0f172a; font-weight: 700; border-bottom: 1px solid #cbd5e1;">
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 90px; text-align: right;">PI Qty</th>
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 110px; text-align: right;">PI Value</th>
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 90px; text-align: right;">GRN Qty</th>
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 110px; text-align: right;">GRN Value</th>
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 90px; text-align: right;">Qty</th>
                <th style="border: 1px solid #cbd5e1; padding: 6px 8px; width: 110px; text-align: right;">Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr style="border-bottom: 1px solid #e2e8f0; {{ $index % 2 == 1 ? 'background-color: #f8fafc;' : '' }}">
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; font-weight: 600; color: #1e293b;">{{ $row['pi_no'] }}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; color: #475569;">{{ $row['grn_no'] }}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-weight: 500; color: #0f172a;">{{ $row['item_name'] }}</td>
                    
                    <!-- PI Details -->
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right;">{{ number_format($row['pi_qty'], 2) }}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right;">₹{{ number_format($row['pi_val'], 2) }}</td>
                    
                    <!-- GRN Details -->
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right;">{{ number_format($row['grn_qty'], 2) }}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right;">₹{{ number_format($row['grn_val'], 2) }}</td>
                    
                    <!-- Difference -->
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; font-weight: 600; color: {{ $row['diff_qty'] > 0 ? '#d97706' : ($row['diff_qty'] < 0 ? '#dc2626' : '#16a34a') }};">
                        {{ number_format($row['diff_qty'], 2) }}
                    </td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; font-weight: 600; color: {{ $row['diff_val'] > 0 ? '#d97706' : ($row['diff_val'] < 0 ? '#dc2626' : '#16a34a') }};">
                        ₹{{ number_format($row['diff_val'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 2rem; color: #64748b;">
                        No Proforma Invoice vs GRN records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 700; color: #0f172a; border-top: 2px solid #64748b;">
                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 8px; text-align: right;">Grand Total:</td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right;">{{ number_format($grandTotals['pi_qty'], 2) }}</td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right;">₹{{ number_format($grandTotals['pi_val'], 2) }}</td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right;">{{ number_format($grandTotals['grn_qty'], 2) }}</td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right;">₹{{ number_format($grandTotals['grn_val'], 2) }}</td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right; color: {{ $grandTotals['diff_qty'] > 0 ? '#d97706' : '#16a34a' }};">
                    {{ number_format($grandTotals['diff_qty'], 2) }}
                </td>
                <td style="border: 1px solid #cbd5e1; padding: 8px; text-align: right; color: {{ $grandTotals['diff_val'] > 0 ? '#d97706' : '#16a34a' }};">
                    ₹{{ number_format($grandTotals['diff_val'], 2) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSelect = document.getElementById('supplier_id');
    const piSelect = document.getElementById('pi_id');
    const itemSelect = document.getElementById('item_id');

    async function updateFilters(source) {
        const supplierId = supplierSelect ? supplierSelect.value : '';
        const piId = piSelect ? piSelect.value : '';

        try {
            const url = `{{ route('reports.pi_vs_grn.filterOptions') }}?supplier_id=${encodeURIComponent(supplierId)}&pi_id=${encodeURIComponent(piId)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                if (source === 'supplier' && piSelect) {
                    const currentPi = piSelect.value;
                    piSelect.innerHTML = '<option value="">All PIs</option>';
                    data.pis.forEach(pi => {
                        const opt = document.createElement('option');
                        opt.value = pi.ID;
                        opt.textContent = pi.PINumber;
                        if (pi.ID == currentPi) opt.selected = true;
                        piSelect.appendChild(opt);
                    });
                }

                if (itemSelect) {
                    const currentItem = itemSelect.value;
                    itemSelect.innerHTML = '<option value="">All Items</option>';
                    data.items.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.ID;
                        opt.textContent = item.ItemName;
                        if (item.ID == currentItem) opt.selected = true;
                        itemSelect.appendChild(opt);
                    });
                }
            }
        } catch (err) {
            console.error('Error updating filter options:', err);
        }
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', () => updateFilters('supplier'));
    }
    if (piSelect) {
        piSelect.addEventListener('change', () => updateFilters('pi'));
    }
});
</script>

<style>
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .sidebar, .top-bar, .content-header button, form, .btn-action, .btn-action-secondary, .status-legend-card {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .report-table {
        width: 100% !important;
        border: 1px solid #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .report-table th, .report-table td {
        border: 1px solid #000000 !important;
    }
}
</style>
@endsection
