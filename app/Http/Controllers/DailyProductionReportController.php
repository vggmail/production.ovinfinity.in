<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyProductionReportController extends Controller
{
    public function index(Request $request)
    {
        $inward = $request->input('inward', 'all');

        // Fetch all available Ym options from intransaction for month dropdown
        $allYmOptions = DB::table('intransaction')
            ->where('IsActive', 1)
            ->whereNotNull('EntryDate')
            ->selectRaw("DATE_FORMAT(EntryDate, '%Y-%m') as ym")
            ->distinct()
            ->pluck('ym')
            ->toArray();
        $allYmOptions = array_unique(array_filter($allYmOptions));
        rsort($allYmOptions);

        $prodMonth = $request->input('pm');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Handle date range and month selection defaults
        if (empty($fromDate) && empty($toDate)) {
            if ($prodMonth && in_array($prodMonth, $allYmOptions)) {
                $fromDate = $prodMonth . '-01';
                $toDate = date('Y-m-t', strtotime($fromDate));
            } else {
                $prodMonth = !empty($allYmOptions) ? $allYmOptions[0] : date('Y-m');
                $fromDate = $prodMonth . '-01';
                $toDate = date('Y-m-t', strtotime($fromDate));
            }
        } elseif (!empty($fromDate) && empty($toDate)) {
            $toDate = date('Y-m-t', strtotime($fromDate));
        } elseif (empty($fromDate) && !empty($toDate)) {
            $fromDate = date('Y-m-01', strtotime($toDate));
        }

        // Query Daily Production records
        $query = DB::table('intransaction as t')
            ->where('t.IsActive', 1);

        // Apply Inward Filter
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

        if ($fromDate) {
            $query->whereDate('t.EntryDate', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('t.EntryDate', '<=', $toDate);
        }

        $dailyData = $query->select([
            DB::raw("DATE(t.EntryDate) as entry_date"),
            DB::raw("SUM(CAST(t.ActualMeter AS DECIMAL(10,2))) as total_actual_meter"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_net_weight")
        ])
        ->groupBy('entry_date')
        ->orderBy('entry_date', 'asc')
        ->get();

        $rows = [];
        $grandTotalMeter = 0;
        $grandTotalNetWeight = 0;

        foreach ($dailyData as $item) {
            $actualMeter = (float)$item->total_actual_meter;
            $netWeight = (float)$item->total_net_weight;

            // Format date as n/j/Y matching user Excel layout e.g. "7/1/2026"
            $dateLabel = date('n/j/Y', strtotime($item->entry_date));

            $rows[] = [
                'raw_date' => $item->entry_date,
                'date_label' => $dateLabel,
                'actual_meter' => $actualMeter,
                'net_weight' => $netWeight,
            ];

            $grandTotalMeter += $actualMeter;
            $grandTotalNetWeight += $netWeight;
        }

        $grandTotals = [
            'actual_meter' => $grandTotalMeter,
            'net_weight' => $grandTotalNetWeight,
        ];

        // Readable date title e.g. "01/07/2026 to 31/07/2026" or "July 2026"
        if ($fromDate && $toDate && (date('Y-m', strtotime($fromDate)) !== date('Y-m', strtotime($toDate)) || $fromDate !== date('Y-m-01', strtotime($fromDate)) || $toDate !== date('Y-m-t', strtotime($toDate)))) {
            $dateTitle = date('d/m/Y', strtotime($fromDate)) . ' to ' . date('d/m/Y', strtotime($toDate));
        } else {
            $dateTitle = date('F Y', strtotime($fromDate ?: ($prodMonth . '-01')));
        }

        return view('reports.daily_production.index', compact(
            'rows',
            'grandTotals',
            'inward',
            'prodMonth',
            'fromDate',
            'toDate',
            'allYmOptions',
            'dateTitle'
        ));
    }
}
