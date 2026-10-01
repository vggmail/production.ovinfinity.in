@extends('layouts.app')

@section('title', 'Production Net Weight & Meter Report')

@section('content')
<div class="content-header report-header">
    <div class="content-title">
        <h1>Production Net Weight & Meter Report</h1>
        <p>Month-wise and day-wise Actual Meter and Net Weight summary for Production and Purchases</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<!-- Navigation Tabs (Monthly vs Daily) -->
<div class="report-tabs">
    <a href="{{ route('reports.monthly_production.index', array_filter(['inward' => $inward, 'from_month' => $fromMonth, 'to_month' => $toMonth])) }}" 
       class="tab-item active">
        <span>📅</span> Monthly
    </a>
    <a href="{{ route('reports.daily_production.index', array_filter(['inward' => $inward])) }}" 
       class="tab-item">
        <span>🗓️</span> Daily
    </a>
</div>

<div class="card report-filter-card">
    <h3 class="filter-header-title">Filter Header Section</h3>
    <form method="GET" action="{{ route('reports.monthly_production.index') }}" class="report-filter-form">
        <!-- Date Range - From Month -->
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label for="from_month">From Month</label>
            <input type="month" name="from_month" id="from_month" value="{{ $fromMonth }}">
        </div>

        <!-- Date Range - To Month -->
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label for="to_month">To Month</label>
            <input type="month" name="to_month" id="to_month" value="{{ $toMonth }}">
        </div>

        <!-- Select List (Source Type / Inward Filter) -->
        <div class="form-group" style="flex: 1.2; min-width: 150px;">
            <label for="inward">Select List</label>
            <select name="inward" id="inward">
                <option value="all" {{ $inward == 'all' ? 'selected' : '' }}>All</option>
                <option value="prod" {{ $inward == 'prod' || $inward == '1' || $inward == 'production' ? 'selected' : '' }}>Production</option>
                <option value="purchase_lam" {{ $inward == 'purchase_lam' || $inward == '3' ? 'selected' : '' }}>Purchase - Laminate</option>
                <option value="purchase" {{ $inward == 'purchase' || $inward == '2' ? 'selected' : '' }}>Purchase - Non Laminate</option>
            </select>
        </div>

        <!-- Submit & Clear Buttons -->
        <div style="display: flex; gap: 0.4rem; align-items: center; flex-shrink: 0;">
            <button type="submit" class="btn-action">
                Filter
            </button>
            <a href="{{ route('reports.monthly_production.index') }}" class="btn-action-secondary" style="text-decoration: none;">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Report Table -->
<div class="card report-table-card" style="max-width: 550px;">
    <div class="report-table-title">
        Monthly Production Net Weight & Meter Report
    </div>
    <table class="report-compact-table">
        <thead>
            <tr style="background-color: #3b6598; color: #ffffff;">
                <th style="text-align: left; width: 160px;">
                    Production Month
                </th>
                <th style="text-align: right; width: 160px;">
                    Actual Meter
                </th>
                <th style="text-align: right; width: 160px;">
                    Net Weight
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr style="text-align: right;">
                    <td style="text-align: left; background-color: #ffffff; color: #0f172a; font-weight: 600;">
                        <a href="{{ route('reports.daily_production.index', ['inward' => $inward, 'from_date' => $row['ym'].'-01', 'to_date' => date('Y-m-t', strtotime($row['ym'].'-01'))]) }}" style="color: #2563eb; text-decoration: none; font-weight: 600;" title="View daily breakdown for {{ $row['month_label'] }}">
                            {{ $row['month_label'] }} 🔍
                        </a>
                    </td>
                    <td style="background-color: #ffffff; color: #0f172a;">
                        {{ number_format($row['actual_meter'], 0) }}
                    </td>
                    <td style="background-color: #ffffff; color: #0f172a;">
                        {{ number_format($row['net_weight'], 1) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 1.5rem !important; color: var(--text-secondary);">
                        No production or purchase records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr style="background-color: #ffffff; color: #000000; text-align: right;">
                <td style="text-align: left;">
                    Grand Total
                </td>
                <td>
                    {{ number_format($grandTotals['actual_meter'], 0) }}
                </td>
                <td style="background-color: #f1f5f9;">
                    {{ number_format($grandTotals['net_weight'], 1) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<style>
    @media print {
        @page {
            size: auto;
            margin: 8mm;
        }
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .sidebar, .top-bar, .content-header button, form, .btn-action-secondary, .report-tabs {
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
            max-width: 100% !important;
        }
        table {
            width: 100% !important;
            border: 1px solid #000000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
@endsection
