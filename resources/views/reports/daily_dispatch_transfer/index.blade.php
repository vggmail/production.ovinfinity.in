@extends('layouts.app')

@section('title', 'Dispatch/Transfer Net Weight Report')

@section('content')
<div class="content-header" style="margin-bottom: 0.75rem;">
    <div class="content-title">
        <h1>Dispatch/Transfer Net Weight Report</h1>
        <p>Day-wise net weight breakdown for Dispatches and Transfers ({{ $monthTitle }})</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<!-- Navigation Tabs (Monthly vs Daily) -->
<div class="report-tabs">
    <a href="{{ route('reports.monthly_dispatch_transfer.index', array_filter(['inward' => $inward])) }}" 
       class="tab-item">
        <span>📅</span> Monthly
    </a>
    <a href="{{ route('reports.daily_dispatch_transfer.index', array_filter(['inward' => $inward, 'from_date' => $fromDate, 'to_date' => $toDate])) }}" 
       class="tab-item active">
        <span>🗓️</span> Daily
    </a>
</div>

<div class="card report-filter-card">
    <h3 class="filter-header-title">Filter Header Section</h3>
    <form method="GET" action="{{ route('reports.daily_dispatch_transfer.index') }}" class="report-filter-form">
        <!-- 1 From Date -->
        <div class="form-group" style="min-width: 150px;">
            <label for="from_date">From Date</label>
            <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}">
        </div>

        <!-- 2 To Date -->
        <div class="form-group" style="min-width: 150px;">
            <label for="to_date">To Date</label>
            <input type="date" name="to_date" id="to_date" value="{{ $toDate }}">
        </div>

        <!-- 3 Inward Type -->
        <div class="form-group" style="min-width: 200px;">
            <label for="inward">Inward Type</label>
            <select name="inward" id="inward">
                <option value="all" {{ $inward == 'all' ? 'selected' : '' }}>All</option>
                <option value="prod" {{ $inward == 'prod' || $inward == '1' || $inward == 'production' ? 'selected' : '' }}>Production</option>
                <option value="purchase_lam" {{ $inward == 'purchase_lam' || $inward == '3' ? 'selected' : '' }}>Purchase - Laminate</option>
                <option value="purchase" {{ $inward == 'purchase' || $inward == '2' ? 'selected' : '' }}>Purchase - Non Laminate</option>
            </select>
        </div>

        <!-- Submit & Clear Buttons -->
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="submit" class="btn-action">
                Filter
            </button>
            <a href="{{ route('reports.daily_dispatch_transfer.index') }}" class="btn-action-secondary" style="text-decoration: none;">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Pivot Daily Summary Report Table matching user mockup styling -->
<div class="card" style="padding: 0; border: 2px solid #2b547e; border-radius: 8px; overflow-x: auto; max-width: 650px;">
    <div style="background-color: #ffffff; padding: 8px 12px; font-weight: 700; font-size: 1.1rem; color: #000000; text-align: center; border-bottom: 2px solid #2b547e;">
        Daily Dispatch/Transfer Net Weight Total
    </div>
    <table style="width: 100%; border-collapse: collapse; font-family: Segoe UI, Tahoma, sans-serif; font-size: 0.95rem; table-layout: auto;">
        <thead>
            <!-- Pivot Header Row 1 -->
            <tr style="background-color: #3b6598; color: #ffffff;">
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: left; font-weight: 700;" colspan="1">
                    Sum of NW
                </th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: right; font-weight: 700;" colspan="3">
                    Col Lab 🔻
                </th>
            </tr>
            <!-- Pivot Header Row 2 -->
            <tr style="background-color: #3b6598; color: #ffffff; text-align: right; font-size: 0.9rem;">
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: left; width: 160px;">
                    Dispatch Entry Date 🔻
                </th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; width: 130px;">Dispatch</th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; width: 130px;">Transfer</th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; width: 140px;">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr style="border-bottom: 1px solid #cbd5e1; text-align: right;">
                    <td style="padding: 6px 12px; border: 1px solid #cbd5e1; text-align: left; background-color: #ffffff; color: #0f172a; font-weight: 500;">
                        {{ $row['date_label'] }}
                    </td>
                    <td style="padding: 6px 12px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a;">
                        {{ $row['dispatch_nw'] > 0 ? number_format($row['dispatch_nw'], 0) : '' }}
                    </td>
                    <td style="padding: 6px 12px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a;">
                        {{ $row['transfer_nw'] > 0 ? number_format($row['transfer_nw'], 0) : '' }}
                    </td>
                    <td style="padding: 6px 12px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-weight: 600;">
                        {{ number_format($row['grand_total'], 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        No daily dispatch or transfer records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr style="background-color: #ffffff; color: #000000; font-weight: 700; border-top: 2px solid #000000; text-align: right;">
                <td style="padding: 8px 12px; border: 1px solid #000000; text-align: left; font-size: 1rem;">
                    Grand Total
                </td>
                <td style="padding: 8px 12px; border: 1px solid #000000; font-size: 1rem;">
                    {{ number_format($grandTotals['dispatch_nw'], 0) }}
                </td>
                <td style="padding: 8px 12px; border: 1px solid #000000; font-size: 1rem;">
                    {{ number_format($grandTotals['transfer_nw'], 0) }}
                </td>
                <td style="padding: 8px 12px; border: 1px solid #000000; font-size: 1.05rem; background-color: #f1f5f9;">
                    {{ number_format($grandTotals['grand_total'], 0) }}
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
