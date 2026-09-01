@extends('layouts.app')

@section('title', 'Daily Dispatch/Transfer Net Weight Report')

@section('content')
<div class="content-header" style="margin-bottom: 1rem;">
    <div class="content-title">
        <h1>Daily Dispatch/Transfer Net Weight Total</h1>
        <p>Day-wise net weight breakdown for Dispatches and Transfers ({{ $monthTitle }})</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <!-- <a href="{{ route('reports.monthly_dispatch_transfer.index', ['inward' => $inward]) }}" class="btn-action-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;" title="Back to Monthly Report">
            ⬅️ Monthly Report
        </a> -->
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('reports.daily_dispatch_transfer.index') }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1.5rem;">
        <!-- 1 Inward Filter -->
        <div class="form-group" style="min-width: 220px;">
            <label for="inward" style="font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">Inward Type</label>
            <select name="inward" id="inward" onchange="this.form.submit()" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600; padding: 6px 10px; border-radius: 6px; width: 100%;">
                <option value="all" {{ $inward == 'all' ? 'selected' : '' }}>All</option>
                <option value="prod" {{ $inward == 'prod' || $inward == '1' || $inward == 'production' ? 'selected' : '' }}>Production</option>
                <option value="purchase_lam" {{ $inward == 'purchase_lam' || $inward == '3' ? 'selected' : '' }}>Purchase - Laminate</option>
                <option value="purchase" {{ $inward == 'purchase' || $inward == '2' ? 'selected' : '' }}>Purchase - Non Laminate</option>
            </select>
        </div>

        <!-- 2 DM Filter (Dispatch Month) -->
        <div class="form-group" style="min-width: 180px;">
            <label for="dm" style="font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">2 DM (Dispatch Month)</label>
            <select name="dm" id="dm" onchange="this.form.submit()" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600; padding: 6px 10px; border-radius: 6px; width: 100%;">
                @foreach($allYmOptions as $ym)
                    @php
                        $mNum = date('n', strtotime($ym . '-01'));
                        $mLabel = date('F Y', strtotime($ym . '-01'));
                    @endphp
                    <option value="{{ $ym }}" {{ $dispatchMonth == $ym ? 'selected' : '' }}>
                        {{ $mNum }} ({{ $mLabel }})
                    </option>
                @endforeach
            </select>
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
        .sidebar, .top-bar, .content-header button, form, .btn-action-secondary, a {
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
