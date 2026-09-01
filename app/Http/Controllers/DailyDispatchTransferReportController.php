<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyDispatchTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $inward = $request->input('inward', 'prod'); // Default 'prod' matching mockup (02 Prod)
        
        // Fetch all available Ym options (e.g. 2026-07)
        $dispatchMonths = DB::table('indispatch')->where('IsActive', 1)->selectRaw("DATE_FORMAT(EntryDate, '%Y-%m') as ym")->distinct()->pluck('ym')->toArray();
        $transferMonths = DB::table('intransfer')->where('IsActive', 1)->selectRaw("DATE_FORMAT(EntryDate, '%Y-%m') as ym")->distinct()->pluck('ym')->toArray();
        $allYmOptions = array_unique(array_filter(array_merge($dispatchMonths, $transferMonths)));
        rsort($allYmOptions);

        $dispatchMonth = $request->input('dm');
        if (!$dispatchMonth || !in_array($dispatchMonth, $allYmOptions)) {
            $dispatchMonth = !empty($allYmOptions) ? $allYmOptions[0] : date('Y-m');
        }

        // Fetch Daily Dispatch Net Weight
        $dispatchQuery = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->join('intransaction as t', function ($join) {
                $join->on('dc.InTransactionID', '=', 't.ID')
                     ->on('dc.SourceType', '=', 't.TransactionType');
            })
            ->where('dc.IsActive', 1)
            ->where('d.IsActive', 1)
            ->where('t.IsActive', 1)
            ->whereRaw("DATE_FORMAT(d.EntryDate, '%Y-%m') = ?", [$dispatchMonth]);

        $this->applyInwardFilter($dispatchQuery, $inward);

        $dispatchDaily = $dispatchQuery->select([
            DB::raw("DATE(d.EntryDate) as entry_date"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_dispatch_nw")
        ])
        ->groupBy('entry_date')
        ->get()
        ->keyBy('entry_date');

        // Fetch Daily Transfer Net Weight
        $transferQuery = DB::table('intransferchild as tc')
            ->join('intransfer as tr', 'tc.Transfer', '=', 'tr.ID')
            ->join('intransaction as t', function ($join) {
                $join->on('tc.InTransactionID', '=', 't.ID')
                     ->on('tc.SourceType', '=', 't.TransactionType');
            })
            ->where('tc.IsActive', 1)
            ->where('tr.IsActive', 1)
            ->where('t.IsActive', 1)
            ->whereRaw("DATE_FORMAT(tr.EntryDate, '%Y-%m') = ?", [$dispatchMonth]);

        $this->applyInwardFilter($transferQuery, $inward);

        $transferDaily = $transferQuery->select([
            DB::raw("DATE(tr.EntryDate) as entry_date"),
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

        // Readable month name for title e.g. "July 2026"
        $monthTitle = date('F Y', strtotime($dispatchMonth . '-01'));

        return view('reports.daily_dispatch_transfer.index', compact(
            'rows',
            'grandTotals',
            'inward',
            'dispatchMonth',
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
