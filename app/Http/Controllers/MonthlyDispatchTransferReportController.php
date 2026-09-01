<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyDispatchTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $inward = $request->input('inward', 'all');

        // Calculate default month range: April of current financial year to current month
        $currentYear = (int)date('Y');
        $currentMonthNum = (int)date('n');
        $startYear = ($currentMonthNum >= 4) ? $currentYear : ($currentYear - 1);

        $defaultFromMonth = sprintf('%04d-04', $startYear);
        $defaultToMonth = date('Y-m');

        $fromMonth = $request->input('from_month', $defaultFromMonth);
        $toMonth = $request->input('to_month', $defaultToMonth);

        if (empty($fromMonth)) {
            $fromMonth = $defaultFromMonth;
        }
        if (empty($toMonth)) {
            $toMonth = $defaultToMonth;
        }

        // Fetch Monthly Dispatch Net Weight
        $dispatchQuery = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->join('intransaction as t', function ($join) {
                $join->on('dc.InTransactionID', '=', 't.ID')
                     ->on('dc.SourceType', '=', 't.TransactionType');
            })
            ->where('dc.IsActive', 1)
            ->where('d.IsActive', 1)
            ->where('t.IsActive', 1);

        $this->applyInwardFilter($dispatchQuery, $inward);

        if ($fromMonth) {
            $dispatchQuery->whereRaw("DATE_FORMAT(d.EntryDate, '%Y-%m') >= ?", [$fromMonth]);
        }
        if ($toMonth) {
            $dispatchQuery->whereRaw("DATE_FORMAT(d.EntryDate, '%Y-%m') <= ?", [$toMonth]);
        }

        $dispatchMonthly = $dispatchQuery->select([
            DB::raw("DATE_FORMAT(d.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_dispatch_nw")
        ])
        ->groupBy('ym')
        ->get()
        ->keyBy('ym');

        // Fetch Monthly Transfer Net Weight
        $transferQuery = DB::table('intransferchild as tc')
            ->join('intransfer as tr', 'tc.Transfer', '=', 'tr.ID')
            ->join('intransaction as t', function ($join) {
                $join->on('tc.InTransactionID', '=', 't.ID')
                     ->on('tc.SourceType', '=', 't.TransactionType');
            })
            ->where('tc.IsActive', 1)
            ->where('tr.IsActive', 1)
            ->where('t.IsActive', 1);

        $this->applyInwardFilter($transferQuery, $inward);

        if ($fromMonth) {
            $transferQuery->whereRaw("DATE_FORMAT(tr.EntryDate, '%Y-%m') >= ?", [$fromMonth]);
        }
        if ($toMonth) {
            $transferQuery->whereRaw("DATE_FORMAT(tr.EntryDate, '%Y-%m') <= ?", [$toMonth]);
        }

        $transferMonthly = $transferQuery->select([
            DB::raw("DATE_FORMAT(tr.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_transfer_nw")
        ])
        ->groupBy('ym')
        ->get()
        ->keyBy('ym');

        // Combine all month keys chronologically
        $allYmKeys = $dispatchMonthly->keys()->merge($transferMonthly->keys())->unique()->sort()->values();

        $rows = [];
        $grandTotalDispatch = 0;
        $grandTotalTransfer = 0;
        $overallGrandTotal = 0;

        foreach ($allYmKeys as $ym) {
            $dispatchNw = isset($dispatchMonthly[$ym]) ? (float)$dispatchMonthly[$ym]->total_dispatch_nw : 0;
            $transferNw = isset($transferMonthly[$ym]) ? (float)$transferMonthly[$ym]->total_transfer_nw : 0;
            $rowTotal = $dispatchNw + $transferNw;

            // Format Month Label e.g. "Apr-26", "May-26"
            $timestamp = strtotime($ym . '-01');
            $monthName = date('M', $timestamp);
            if ($monthName === 'Jul') {
                $monthName = 'July'; // Format as "July-26" matching mockup
            }
            $monthLabel = $monthName . '-' . date('y', $timestamp);

            $rows[] = [
                'ym' => $ym,
                'month_label' => $monthLabel,
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

        return view('reports.monthly_dispatch_transfer.index', compact(
            'rows',
            'grandTotals',
            'inward',
            'fromMonth',
            'toMonth'
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
