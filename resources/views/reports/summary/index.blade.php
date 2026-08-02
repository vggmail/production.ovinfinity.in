@extends('layouts.app')

@section('title', 'Summary Report')

@section('content')
<div class="content-header" style="margin-bottom: 1rem;">
    <div class="content-title">
        <h1>Summary Report</h1>
        <p>Stock summary report for remaining non-transferred and non-dispatched inventory</p>
    </div>
    <div>
        <button type="button" onclick="window.print()" class="btn-action-secondary" title="Print Report">
            🖨️ Print
        </button>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('reports.summary.index') }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1.5rem; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end;">
            <!-- PROD Filter -->
            <div class="form-group" style="min-width: 140px;">
                <label for="prod" style="font-weight: 700; color: #1e3a8a;">PROD</label>
                <select name="prod" id="prod" onchange="this.form.submit()" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600;">
                    <option value="all" {{ $prod == 'all' ? 'selected' : '' }}>(All)</option>
                    <option value="1" {{ $prod == '1' ? 'selected' : '' }}>Production</option>
                    <option value="2" {{ $prod == '2' ? 'selected' : '' }}>Purchase</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="form-group" style="min-width: 120px;">
                <label for="status" style="font-weight: 700; color: #1e3a8a;">status</label>
                <select name="status" id="status" onchange="this.form.submit()" style="border: 2px solid #3b82f6; background-color: #f0f9ff; font-weight: 600;">
                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>b (Active)</option>
                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
        </div>

        <!-- DATE Filter Box matching mockup header -->
        <div style="display: flex; align-items: center; border: 2px solid #000; padding: 4px 12px; background: #ffffff; border-radius: 4px; gap: 1rem;">
            <span style="font-weight: 700; font-size: 1.1rem; letter-spacing: 1px;">DATE</span>
            <input type="date" name="date" value="{{ $targetDate }}" onchange="this.form.submit()" style="border: 1px solid #ccc; padding: 4px 8px; font-weight: 700; font-size: 1rem; width: auto;">
        </div>
    </form>
</div>

<!-- Pivot Summary Report Table matching user mockup styling -->
<div class="card" style="padding: 0; border: 2px solid #1e40af; border-radius: 8px; overflow-x: auto; max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 0.9rem; table-layout: auto;">
        <thead>
            <!-- Main Header Row 1 -->
            <tr style="background-color: #2b547e; color: #ffffff; text-align: center;">
                <th style="padding: 8px 6px; border: 1px solid #1e3a8a; width: 70px;" rowspan="2">Size</th>
                <th style="padding: 8px 6px; border: 1px solid #1e3a8a; width: 70px;" rowspan="2">RGMW</th>
                <th style="padding: 8px 6px; border: 1px solid #1e3a8a;" rowspan="2">Type</th>
                <th style="padding: 6px; border: 1px solid #1e3a8a; background-color: #3b6598;" colspan="5">Values</th>
            </tr>
            <!-- Sub Header Row 2 -->
            <tr style="background-color: #3b6598; color: #ffffff; text-align: center; font-size: 0.85rem;">
                <th style="padding: 6px; border: 1px solid #1e3a8a;">Sum of METER</th>
                <th style="padding: 6px; border: 1px solid #1e3a8a;">Sum of NW</th>
                <th style="padding: 6px; border: 1px solid #1e3a8a;">Average of AGRM</th>
                <th style="padding: 6px; border: 1px solid #1e3a8a;">Average of Age</th>
                <th style="padding: 6px; border: 1px solid #1e3a8a; width: 60px;">Rolls</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedData as $sizeName => $sizeGroup)
                @php $firstSizeRow = true; @endphp
                @foreach($sizeGroup['rgms'] as $rgmName => $rgmGroup)
                    @php $firstRgmRow = true; @endphp
                    @foreach($rgmGroup['items'] as $item)
                        <tr style="border-bottom: 1px solid #94a3b8; text-align: center;">
                            <!-- Size Cell with Rowspan -->
                            @if($firstSizeRow)
                                <td rowspan="{{ $sizeGroup['totalRows'] }}" style="padding: 6px 4px; border: 1px solid #94a3b8; background-color: #dbeafe; font-weight: 700; color: #0f172a; vertical-align: middle;">
                                    {{ $sizeName }}
                                </td>
                                @php $firstSizeRow = false; @endphp
                            @endif

                            <!-- RGMW Cell with Rowspan -->
                            @if($firstRgmRow)
                                <td rowspan="{{ $rgmGroup['totalRows'] }}" style="padding: 6px 4px; border: 1px solid #94a3b8; background-color: #eff6ff; font-weight: 700; color: #0f172a; vertical-align: middle;">
                                    {{ $rgmName }}
                                </td>
                                @php $firstRgmRow = false; @endphp
                            @endif

                            <!-- Type / Fabric Color Cell -->
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: center; background-color: #ffffff;">
                                {{ $item->TypeName ?? '-' }}
                            </td>

                            <!-- Metrics Cells -->
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: right; background-color: #ffffff;">
                                {{ number_format($item->SumMeter, 0) }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: right; background-color: #ffffff;">
                                {{ number_format($item->SumNW, 1) }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: right; background-color: #ffffff;">
                                {{ number_format($item->AvgAGRM, 4) }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: right; background-color: #ffffff;">
                                {{ round($item->AvgAge) }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #94a3b8; text-align: right; background-color: #ffffff; font-weight: 600;">
                                {{ $item->RollsCount }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        No remaining stock inventory records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($groupedData) > 0)
        <tfoot>
            <tr style="background-color: #ffffff; color: #000000; font-weight: 700; border-top: 2px solid #000000; text-align: center;">
                <td colspan="3" style="padding: 8px; border: 1px solid #000000; text-align: center; font-size: 0.95rem;">
                    Grand Total
                </td>
                <td style="padding: 8px; border: 1px solid #000000; text-align: right; font-size: 0.95rem;">
                    {{ number_format($grandTotals['SumMeter'], 0) }}
                </td>
                <td style="padding: 8px; border: 1px solid #000000; text-align: right; font-size: 0.95rem;">
                    {{ number_format($grandTotals['SumNW'], 1) }}
                </td>
                <td style="padding: 8px; border: 1px solid #000000; text-align: right; font-size: 0.95rem;">
                    {{ number_format($grandTotals['AvgAGRM'], 4) }}
                </td>
                <td style="padding: 8px; border: 1px solid #000000; text-align: right; font-size: 0.95rem;">
                    {{ round($grandTotals['AvgAge']) }}
                </td>
                <td style="padding: 8px; border: 1px solid #000000; text-align: right; font-size: 0.95rem;">
                    {{ $grandTotals['Rolls'] }}
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
            overflow: visible !important;
        }
        .sidebar, .top-bar, .content-header button, form, .btn-action-secondary {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            overflow: visible !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            max-width: 100% !important;
        }
        table {
            width: 100% !important;
            table-layout: auto !important;
            font-size: 0.8rem !important;
            border: 1px solid #000000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        th, td {
            padding: 4px 6px !important;
        }
        tr {
            page-break-inside: avoid !important;
        }
    }
</style>
@endsection
