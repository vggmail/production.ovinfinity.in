@extends('layouts.app')

@section('title', 'Material Consumption Reports - Daily')

@section('content')
<div class="content-header report-header">
    <div class="content-title">
        <h1>Material Consumption Reports</h1>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="report-tabs">
    <a href="{{ route('reports.monthly_material_consumption.index', array_filter(['loom_id' => $loomId, 'item_id' => $itemId !== 'all' ? $itemId : null])) }}"
       class="tab-item">
        <span>📅</span> Monthly
    </a>
    <a href="{{ route('reports.daily_material_consumption.index', array_filter(['from_date' => $fromDate, 'to_date' => $toDate, 'loom_id' => $loomId, 'item_id' => $itemId !== 'all' ? $itemId : null])) }}"
       class="tab-item active">
        <span>🗓️</span> Daily
    </a>
</div>

<!-- Filter Card -->
<div class="card report-filter-card">
    <h3 class="filter-header-title">Filter Header Section</h3>
    <form method="GET" action="{{ route('reports.daily_material_consumption.index') }}" class="report-filter-form">

        <!-- From Date -->
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label for="from_date">From Date</label>
            <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}">
        </div>

        <!-- To Date -->
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label for="to_date">To Date</label>
            <input type="date" name="to_date" id="to_date" value="{{ $toDate }}">
        </div>

        <!-- Department — multi-select -->
        <div class="form-group" style="flex: 1.4; min-width: 160px;">
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

        <!-- Machine Name -->
        <div class="form-group" style="flex: 1.2; min-width: 140px;">
            <label for="loom_id">Machine Name</label>
            <select name="loom_id" id="loom_id" style="width:100%;">
                <option value="all" {{ $loomId == 'all' || empty($loomId) ? 'selected' : '' }}>All Machine Name</option>
                @foreach($looms as $loom)
                    <option value="{{ $loom->ID }}" {{ $loomId == $loom->ID ? 'selected' : '' }}>
                        {{ $loom->MachineName ? $loom->MachineName . ' (' . $loom->LoomNumber . ')' : 'Loom ' . $loom->LoomNumber }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Item Name — type-to-search -->
        <div class="form-group" style="flex: 1.6; min-width: 180px;">
            <label for="item_id">Item Name</label>
            <select name="item_id" id="item_id" style="width:100%;">
                <option value="all" {{ $itemId == 'all' || empty($itemId) ? 'selected' : '' }}>-- All Items --</option>
                @foreach($items as $item)
                    <option value="{{ $item->ID }}" {{ $itemId == $item->ID ? 'selected' : '' }}>
                        {{ $item->ItemName }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 0.4rem; align-items: center; flex-shrink: 0;">
            <button type="submit" class="btn-action">Filter</button>
            <a href="{{ route('reports.daily_material_consumption.index') }}" class="btn-action-secondary" style="text-decoration: none;">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card report-table-card" style="max-width: 550px;">
    <table class="report-compact-table">
        <thead>
            <tr style="background-color: #3b6598; color: #ffffff;">
                <th style="text-align: left; width: 40%;">Month /Day 🔻</th>
                <th style="text-align: right; width: 30%;">Qty</th>
                <th style="text-align: right; width: 30%;">Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr style="text-align: right;">
                    <td style="text-align: left; background-color: #ffffff; color: #0f172a; font-weight: 500;">{{ $row['date_label'] }}</td>
                    <td style="background-color: #ffffff; color: #0f172a;">{{ number_format($row['qty'], 0) }}</td>
                    <td style="background-color: #ffffff; color: #0f172a;">{{ number_format($row['value'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 1.5rem !important; color: var(--text-secondary);">
                        No material consumption records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr style="background-color: #ffffff; color: #000000; text-align: right;">
                <td style="text-align: left;">Grand Total</td>
                <td>{{ number_format($grandTotals['qty'], 0) }}</td>
                <td style="background-color: #f1f5f9;">{{ number_format($grandTotals['value'], 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<style>
    @media print {
        @page { size: auto; margin: 8mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .sidebar, .top-bar, .content-header button, form, .btn-action-secondary, .report-tabs { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        table { width: 100% !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
    .select2-container .select2-selection--multiple {
        min-height: 34px !important; border: 1px solid #ccc !important; border-radius: 4px !important;
        background: #fff !important; padding: 2px 4px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #3b6598 !important; color: #fff !important; border: none !important;
        border-radius: 3px !important; font-size: 0.78rem !important; padding: 1px 6px !important; margin: 2px 3px 2px 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #c7d2fe !important; margin-right: 4px !important; }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #3b6598 !important; box-shadow: 0 0 0 2px rgba(59,101,152,0.18) !important;
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 === 'undefined') return;

    @php
        $selectedDeptsJs = is_array($departmentId)
            ? $departmentId
            : ((!empty($departmentId) && $departmentId !== 'all') ? [$departmentId] : []);
    @endphp

    $('#department_id').select2({ placeholder: '-- All Departments --', allowClear: true, width: '100%' });
    const preselected = @json($selectedDeptsJs);
    if (preselected.length > 0) $('#department_id').val(preselected.map(String)).trigger('change.select2');

    $('#loom_id').select2({ placeholder: 'All Machine Name', allowClear: true, width: '100%' });
    $('#item_id').select2({ placeholder: '-- Type to search item --', allowClear: true, width: '100%' });
});
</script>
@endsection
