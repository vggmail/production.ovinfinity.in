@extends('layouts.app')

@section('title', 'Monthly Production Report')

@section('content')
<div class="content-header" style="margin-bottom: 1rem;">
    <div class="content-title">
        <h1>Monthly Production Report</h1>
        <p>Month-wise Actual Meter and Net Weight summary for Production and Purchases</p>
    </div>
    <div>
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('reports.monthly_production.index') }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1.25rem;">
        <!-- Date Range - From Month -->
        <div class="form-group" style="min-width: 160px;">
            <label for="from_month" style="font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">From Month</label>
            <input type="month" name="from_month" id="from_month" value="{{ $fromMonth }}" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600; padding: 6px 10px; border-radius: 6px; width: 100%;">
        </div>

        <!-- Date Range - To Month -->
        <div class="form-group" style="min-width: 160px;">
            <label for="to_month" style="font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">To Month</label>
            <input type="month" name="to_month" id="to_month" value="{{ $toMonth }}" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600; padding: 6px 10px; border-radius: 6px; width: 100%;">
        </div>

        <!-- Select List (Source Type / Inward Filter) -->
        <div class="form-group" style="min-width: 220px;">
            <label for="inward" style="font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">Select List</label>
            <select name="inward" id="inward" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600; padding: 6px 10px; border-radius: 6px; width: 100%;">
                <option value="all" {{ $inward == 'all' ? 'selected' : '' }}>All</option>
                <option value="prod" {{ $inward == 'prod' || $inward == '1' || $inward == 'production' ? 'selected' : '' }}>Production</option>
                <option value="purchase_lam" {{ $inward == 'purchase_lam' || $inward == '3' ? 'selected' : '' }}>Purchase - Laminate</option>
                <option value="purchase" {{ $inward == 'purchase' || $inward == '2' ? 'selected' : '' }}>Purchase - Non Laminate</option>
            </select>
        </div>

        <!-- Submit & Clear Buttons -->
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="submit" class="btn-action" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">
                Filter
            </button>
            <a href="{{ route('reports.monthly_production.index') }}" class="btn-action-secondary" style="padding: 0.55rem 1rem; font-size: 0.85rem; text-decoration: none;">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Report Table matching user mockup styling -->
<div class="card" style="padding: 0; border: 2px solid #2b547e; border-radius: 8px; overflow-x: auto; max-width: 550px;">
    <div style="background-color: #ffffff; padding: 8px 12px; font-weight: 700; font-size: 1.1rem; color: #000000; text-align: center; border-bottom: 2px solid #2b547e;">
        Monthly Production Net Weight & Meter Report
    </div>
    <table style="width: 100%; border-collapse: collapse; font-family: Segoe UI, Tahoma, sans-serif; font-size: 0.95rem; table-layout: auto;">
        <thead>
            <tr style="background-color: #3b6598; color: #ffffff;">
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: left; font-weight: 700; width: 160px;">
                    Production Month
                </th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: right; font-weight: 700; width: 160px;">
                    Actual Meter
                </th>
                <th style="padding: 8px 12px; border: 1px solid #1e3a8a; text-align: right; font-weight: 700; width: 160px;">
                    Net Weight
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr style="border-bottom: 1px solid #cbd5e1; text-align: right;">
                    <td style="padding: 8px 12px; border: 1px solid #cbd5e1; text-align: left; background-color: #ffffff; color: #0f172a; font-weight: 600;">
                        {{ $row['month_label'] }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a;">
                        {{ number_format($row['actual_meter'], 0) }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a;">
                        {{ number_format($row['net_weight'], 1) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        No production or purchase records found for the selected criteria.
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
                    {{ number_format($grandTotals['actual_meter'], 0) }}
                </td>
                <td style="padding: 8px 12px; border: 1px solid #000000; font-size: 1.05rem; background-color: #f1f5f9;">
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
        .sidebar, .top-bar, .content-header button, form, .btn-action-secondary {
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
