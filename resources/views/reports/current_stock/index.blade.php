@extends('layouts.app')

@section('title', 'Current Stock & Aging Report')

@section('styles')
<style>
    .stock-filter-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .stock-filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .filter-item label {
        font-size: 0.825rem;
        font-weight: 700;
        color: #1e293b;
    }
    .filter-item input,
    .filter-item select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.875rem;
        color: #0f172a;
        background: #fff;
        height: 38px;
        box-sizing: border-box;
    }
    .filter-item input:focus,
    .filter-item select:focus {
        border-color: #4f46e5;
        outline: none;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
    }
    /* Select2 — multi-select (department) */
    .select2-container { width: 100% !important; }
    .select2-container .select2-selection--multiple {
        min-height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background-color: #ffffff !important;
        padding: 2px 6px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #4f46e5 !important;
        box-shadow: 0 0 0 2px rgba(79,70,229,0.15) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #4f46e5 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 4px !important;
        font-size: 0.78rem !important;
        padding: 2px 6px !important;
        margin: 2px 3px 2px 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #c7d2fe !important;
        margin-right: 4px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #fff !important;
    }
    /* Select2 — single-select (item / status) */
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        color: #0f172a !important;
        font-size: 0.875rem !important;
        padding-left: 0.75rem !important;
        padding-right: 2rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        font-size: 0.875rem !important;
        z-index: 9999 !important;
    }
    .select2-search--dropdown { padding: 6px 8px !important; }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        padding: 6px 10px !important;
        font-size: 0.875rem !important;
        outline: none !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .select2-results__option { padding: 6px 10px !important; font-size: 0.85rem !important; }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #2b547e !important;
        color: #ffffff !important;
    }

    /* Report Table */
    .current-stock-table-card {
        background: #ffffff;
        border: 2px solid #2b547e;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .current-stock-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        font-family: inherit;
    }
    .current-stock-table thead tr {
        background-color: #2b547e;
        color: #ffffff;
        font-weight: 700;
        text-align: center;
    }
    .current-stock-table th {
        padding: 10px 12px;
        border: 1px solid #1e3a8a;
        white-space: nowrap;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .current-stock-table td {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        vertical-align: middle;
        background-color: #ffffff;
    }
    .current-stock-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    .current-stock-table tbody tr:hover td { background-color: #f1f5f9; }

    /* Badges */
    .stock-badge {
        display: inline-block;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.775rem;
        font-weight: 700;
        text-align: center;
        letter-spacing: 0.3px;
    }
    .badge-in-stock  { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .badge-low-stock { background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
    .badge-out-of-stock { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

    @media print {
        .top-bar, .sidebar, .report-header .btn-action, .report-header .btn-action-secondary, .stock-filter-card, .stock-kpi-container { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .current-stock-table-card { border: 1px solid #000 !important; box-shadow: none !important; }
        .current-stock-table th { color: #000 !important; background: #e2e8f0 !important; border: 1px solid #666 !important; }
        .current-stock-table td { border: 1px solid #999 !important; color: #000 !important; }
    }
</style>
@endsection

@section('content')
<div class="content-header report-header" style="margin-bottom: 1rem;">
    <div class="content-title">
        <h1>Current Stock & Aging Report</h1>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<!-- Filter Section -->
<div class="stock-filter-card">
    <form method="GET" action="{{ route('reports.current_stock.index') }}" class="stock-filter-row" id="stock-filter-form">

        <!-- 1. Department Multi-Select -->
        <div class="filter-item" style="flex: 1.4; min-width: 200px;">
            <label for="department_id">Department</label>
            @php
                $selectedDepts = is_array($departmentId)
                    ? $departmentId
                    : ((!empty($departmentId) && $departmentId !== 'all') ? [$departmentId] : []);
            @endphp
            <select name="department_id[]" id="department_id" multiple="multiple" style="width:100%;">
                @foreach($departments as $dept)
                    <option value="{{ $dept->ID }}" {{ in_array($dept->ID, $selectedDepts) ? 'selected' : '' }}>
                        {{ $dept->DepartmentName }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 2. Item Name type-to-search -->
        <div class="filter-item" style="flex: 1.8; min-width: 260px;">
            <label for="item_id">Item Name - PART No - Catalogue No</label>
            <select name="item_id" id="item_id" style="width:100%;">
                <option value="all">-- All Items --</option>
            </select>
        </div>

        <!-- 3. Status Filter (visible) -->
        <div class="filter-item" style="flex: 1; min-width: 150px;">
            <label for="status">Stock Status</label>
            <select name="status" id="status" style="width:100%;">
                <option value="all"      {{ $statusFilter == 'all'       ? 'selected' : '' }}>All Status</option>
                <option value="in_stock" {{ $statusFilter == 'in_stock'  ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock"{{ $statusFilter == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
            </select>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 0.5rem; align-items: flex-end; padding-bottom: 1px;">
            <button type="submit" class="btn-action" style="height: 38px;">🔍 Filter</button>
            <a href="{{ route('reports.current_stock.index') }}" class="btn-action-secondary" style="height: 38px; text-decoration: none;">Clear</a>
        </div>
    </form>
</div>

<!-- Main Data Table -->
<div class="current-stock-table-card">
    <div style="padding: 0.65rem 1rem; background: #ffffff; border-bottom: 2px solid #2b547e; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 700; color: #1e293b; font-size: 1rem;">
            Showing {{ count($rows) }} items
        </span>
        <span style="font-size: 0.85rem; color: #64748b;">
            📅 As of: <strong>{{ now()->format('d/m/y') }}</strong>
        </span>
    </div>

    <div style="overflow-x: auto;">
        <table class="current-stock-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Sr.</th>
                    <th style="text-align: left; min-width: 280px;">Item Name - PART No - Catalogue No</th>
                    <th style="width: 120px; text-align: right;">Minimum Qty</th>
                    <th style="width: 130px; text-align: right;">Current Stock Qty</th>
                    <th style="width: 140px; text-align: right;">Current Stock Val</th>
                    <th style="width: 100px; text-align: center;">Age</th>
                    <th style="width: 120px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                        <td style="text-align: left; color: #0f172a;">
                            <span style="font-weight: 600;">{{ $row['item_name'] }}</span>
                            @if(!empty($row['part_no']))
                                <span style="color: #64748b;"> - </span><span style="color: #334155;">{{ $row['part_no'] }}</span>
                            @endif
                            @if(!empty($row['catalogue_no']))
                                <span style="color: #64748b;"> - </span><span style="color: #334155;">{{ $row['catalogue_no'] }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #475569;">
                            {{ number_format($row['min_qty'], 2) }}
                            @if(!empty($row['unit']) && strtolower(trim($row['unit'])) !== 'not required')
                                <span style="font-size: 0.75rem; color: #94a3b8;">{{ $row['unit'] }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 0.95rem; color: {{ $row['current_stock_qty'] <= 0 ? '#dc2626' : ($row['current_stock_qty'] < $row['min_qty'] ? '#d97706' : '#15803d') }};">
                            {{ number_format($row['current_stock_qty'], 2) }}
                            @if(!empty($row['unit']) && strtolower(trim($row['unit'])) !== 'not required')
                                <span style="font-size: 0.75rem; color: #94a3b8;">{{ $row['unit'] }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #0f172a;">
                            ₹{{ number_format($row['current_stock_val'], 2) }}
                            @if($row['rate'] > 0)
                                <div style="font-size: 0.725rem; color: #94a3b8; font-weight: normal;">@ ₹{{ number_format($row['rate'], 2) }}</div>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #475569;">{{ $row['age'] }}</td>
                        <td style="text-align: center;">
                            <span class="stock-badge {{ $row['status_class'] }}">{{ $row['status_label'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
                            <div style="font-weight: 600; font-size: 1rem; color: #334155;">No matching stock records found</div>
                            <p style="font-size: 0.85rem; margin-top: 0.25rem;">Try adjusting your department filter or search keywords.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($rows) > 0)
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: 700; color: #0f172a; border-top: 2px solid #2b547e;">
                        <td colspan="2" style="padding: 10px 12px; text-align: right; font-size: 0.95rem; border: 1px solid #cbd5e1;">Grand Total:</td>
                        <td style="padding: 10px 12px; text-align: right; border: 1px solid #cbd5e1; color: #475569;">{{ number_format($grandTotals['min_qty'], 2) }}</td>
                        <td style="padding: 10px 12px; text-align: right; border: 1px solid #cbd5e1; font-size: 1rem; color: #0f172a;">{{ number_format($grandTotals['current_stock_qty'], 2) }}</td>
                        <td style="padding: 10px 12px; text-align: right; border: 1px solid #cbd5e1; font-size: 1rem; color: #0f172a;">₹{{ number_format($grandTotals['current_stock_val'], 2) }}</td>
                        <td colspan="2" style="border: 1px solid #cbd5e1; background-color: #f1f5f9;"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 === 'undefined') return;

    const allItems = @json($allItems);

    @php
        $selectedDeptsJs = is_array($departmentId)
            ? $departmentId
            : ((!empty($departmentId) && $departmentId !== 'all') ? [$departmentId] : []);
    @endphp
    const selectedDeptIds = @json($selectedDeptsJs);
    const selectedItemId  = "{{ $itemId }}";
    const itemSelect      = document.getElementById('item_id');

    // ── Populate item dropdown filtered by selected dept IDs ──────────────
    function populateItems(deptIds, initialItemId) {
        let filtered = allItems;
        if (deptIds && deptIds.length > 0) {
            filtered = allItems.filter(item => deptIds.map(String).includes(String(item.Department)));
        }

        let html = '<option value="all">-- All Items (Type to search) --</option>';
        let hasSelected = false;
        filtered.forEach(item => {
            let label = item.ItemName;
            if (item.PartNo)      label += ' - ' + item.PartNo;
            if (item.CatalogueNo) label += ' - ' + item.CatalogueNo;
            const isSel = String(item.ID) === String(initialItemId);
            if (isSel) hasSelected = true;
            html += `<option value="${item.ID}" ${isSel ? 'selected' : ''}>${label}</option>`;
        });

        if ($(itemSelect).hasClass('select2-hidden-accessible')) $(itemSelect).select2('destroy');
        itemSelect.innerHTML = html;
        itemSelect.value = (hasSelected && initialItemId && initialItemId !== 'all') ? initialItemId : 'all';

        $(itemSelect).select2({ placeholder: '-- All Items (Type to search) --', allowClear: true, width: '100%' });
    }

    // ── Department — multi-select ─────────────────────────────────────────
    $('#department_id').select2({ placeholder: '-- All Departments --', allowClear: true, width: '100%' });
    if (selectedDeptIds.length > 0) {
        $('#department_id').val(selectedDeptIds.map(String)).trigger('change.select2');
    }
    $('#department_id').on('change', function() {
        populateItems($(this).val() || [], '');
    });

    // ── Status — single select, no search ────────────────────────────────
    $('#status').select2({ placeholder: '-- All Status --', minimumResultsForSearch: Infinity, width: '100%' });

    // ── Initial item load ────────────────────────────────────────────────
    populateItems(selectedDeptIds, selectedItemId);
});
</script>
@endsection
