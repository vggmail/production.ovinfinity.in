<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyDispatchTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $inward = $request->input('inward', 'all'); // Default 'all' matching monthly report
        
        // Fetch all available Ym options from indispatch
        $allYmOptions = DB::table('indispatch')
            ->where(function ($q) {
                $q->whereNull('IsActive')->orWhere('IsActive', 1);
            })
            ->selectRaw("DATE_FORMAT(EntryDate, '%Y-%m') as ym")
            ->distinct()
            ->pluck('ym')
            ->toArray();
        $allYmOptions = array_unique(array_filter($allYmOptions));
        rsort($allYmOptions);

        $dispatchMonth = $request->input('dm');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Handle date range and month selection defaults
        if (empty($fromDate) && empty($toDate)) {
            if ($dispatchMonth && in_array($dispatchMonth, $allYmOptions)) {
                $fromDate = $dispatchMonth . '-01';
                $toDate = date('Y-m-t', strtotime($fromDate));
            } else {
                $dispatchMonth = !empty($allYmOptions) ? $allYmOptions[0] : date('Y-m');
                $fromDate = $dispatchMonth . '-01';
                $toDate = date('Y-m-t', strtotime($fromDate));
            }
        } elseif (!empty($fromDate) && empty($toDate)) {
            $toDate = date('Y-m-t', strtotime($fromDate));
        } elseif (empty($fromDate) && !empty($toDate)) {
            $fromDate = date('Y-m-01', strtotime($toDate));
        }

        // Fetch Daily Dispatch Net Weight
        $dispatchQuery = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->join('intransaction as t', 'dc.InTransactionID', '=', 't.ID')
            ->where(function ($q) {
                $q->whereNull('dc.IsActive')->orWhere('dc.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('d.IsActive')->orWhere('d.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('t.IsActive')->orWhere('t.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('d.DispatchType')
                  ->orWhere('d.DispatchType', 'Dispatch')
                  ->orWhere('d.DispatchType', '');
            });

        if ($fromDate) {
            $dispatchQuery->whereDate('d.EntryDate', '>=', $fromDate);
        }
        if ($toDate) {
            $dispatchQuery->whereDate('d.EntryDate', '<=', $toDate);
        }

        $this->applyInwardFilter($dispatchQuery, $inward);

        $dispatchDaily = $dispatchQuery->select([
            DB::raw("DATE(d.EntryDate) as entry_date"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_dispatch_nw")
        ])
        ->groupBy('entry_date')
        ->get()
        ->keyBy('entry_date');

        // Fetch Daily Transfer Net Weight (from indispatch where DispatchType = 'Transfer')
        $transferQuery = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->join('intransaction as t', 'dc.InTransactionID', '=', 't.ID')
            ->where(function ($q) {
                $q->whereNull('dc.IsActive')->orWhere('dc.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('d.IsActive')->orWhere('d.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('t.IsActive')->orWhere('t.IsActive', 1);
            })
            ->where('d.DispatchType', 'Transfer');

        if ($fromDate) {
            $transferQuery->whereDate('d.EntryDate', '>=', $fromDate);
        }
        if ($toDate) {
            $transferQuery->whereDate('d.EntryDate', '<=', $toDate);
        }

        $this->applyInwardFilter($transferQuery, $inward);

        $transferDaily = $transferQuery->select([
            DB::raw("DATE(d.EntryDate) as entry_date"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_transfer_nw")
        ])
        ->groupBy('entry_date')
        ->get()
        ->keyBy('entry_date');

        // Combine all distinct dates chronologically
        $allDates = $dispatchDaily->keys()->merge($transferDaily->keys())->unique()->sort()->values();

        $rows = [];
        $grandTotalDispatch = 0;
        $grandTotalTransfer = 0;
        $overallGrandTotal = 0;

        foreach ($allDates as $dateStr) {
            $dispatchNw = isset($dispatchDaily[$dateStr]) ? (float)$dispatchDaily[$dateStr]->total_dispatch_nw : 0;
            $transferNw = isset($transferDaily[$dateStr]) ? (float)$transferDaily[$dateStr]->total_transfer_nw : 0;
            $rowTotal = $dispatchNw + $transferNw;

            // Format date e.g. "7/1/2026", "7/2/2026" matching mockup
            $dateLabel = date('n/j/Y', strtotime($dateStr));

            $rows[] = [
                'raw_date' => $dateStr,
                'date_label' => $dateLabel,
                'dispatch_nw' => $dispatchNw,
                'transfer_nw' => $transferNw,
                'grand_total' => $rowTotal,
            ];

            $grandTotalDispatch += $dispatchNw;
            $grandTotalTransfer += $transferNw;
            $overallGrandTotal += $rowTotal;
        }

        $grandTotals = [
            'dispatch_nw' => $grandTotalDispatch,
            'transfer_nw' => $grandTotalTransfer,
            'grand_total' => $overallGrandTotal,
        ];

        // Readable date title e.g. "01/07/2026 to 31/07/2026" or "July 2026"
        if ($fromDate && $toDate && (date('Y-m', strtotime($fromDate)) !== date('Y-m', strtotime($toDate)) || $fromDate !== date('Y-m-01', strtotime($fromDate)) || $toDate !== date('Y-m-t', strtotime($toDate)))) {
            $monthTitle = date('d/m/Y', strtotime($fromDate)) . ' to ' . date('d/m/Y', strtotime($toDate));
        } else {
            $monthTitle = date('F Y', strtotime($fromDate ?: ($dispatchMonth . '-01')));
        }

        return view('reports.daily_dispatch_transfer.index', compact(
            'rows',
            'grandTotals',
            'inward',
            'dispatchMonth',
            'fromDate',
            'toDate',
            'allYmOptions',
            'monthTitle'
        ));
    }

    private function applyInwardFilter($query, $inward)
    {
        if ($inward === 'prod' || $inward === '1' || $inward === 'production') {
            $query->where('t.TransactionType', 1);
        } elseif ($inward === 'purchase_lam' || $inward === '3' || $inward === 'purchase_laminate') {
            $query->where('t.TransactionType', 2)
                  ->where('t.Lamination', 1);
        } elseif ($inward === 'purchase' || $inward === '2' || $inward === 'purchase_non_lam') {
            $query->where('t.TransactionType', 2)
                  ->where(function ($q) {
                      $q->whereNull('t.Lamination')
                        ->orWhere('t.Lamination', 0)
                        ->orWhere('t.Lamination', '');
                  });
        }
        // 'all' includes all inward types
    }
}
