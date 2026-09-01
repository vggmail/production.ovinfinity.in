<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyProductionReportController extends Controller
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

        // Query intransaction records for Monthly Production / Inward
        $query = DB::table('intransaction as t')
            ->where('t.IsActive', 1);

        // Apply Source Type / Inward filter
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

        if ($fromMonth) {
            $query->whereRaw("DATE_FORMAT(t.EntryDate, '%Y-%m') >= ?", [$fromMonth]);
        }
        if ($toMonth) {
            $query->whereRaw("DATE_FORMAT(t.EntryDate, '%Y-%m') <= ?", [$toMonth]);
        }

        $monthlyData = $query->select([
            DB::raw("DATE_FORMAT(t.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.ActualMeter AS DECIMAL(10,2))) as total_actual_meter"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as total_net_weight")
        ])
        ->groupBy('ym')
        ->orderBy('ym', 'asc')
        ->get()
        ->keyBy('ym');

        $rows = [];
        $grandTotalMeter = 0;
        $grandTotalNetWeight = 0;

        foreach ($monthlyData as $ym => $data) {
            $actualMeter = (float)$data->total_actual_meter;
            $netWeight = (float)$data->total_net_weight;

            $timestamp = strtotime($ym . '-01');
            $mNum = (int)date('n', $timestamp);

            // Financial year month index starting April = 1
            $fyIndex = ($mNum >= 4) ? ($mNum - 3) : ($mNum + 9);

            $monthName = date('M', $timestamp);
            if ($monthName === 'Jun') {
                $monthName = 'June';
            } elseif ($monthName === 'Jul') {
                $monthName = 'July';
            }

            // Month Label matching mockup e.g. "01 Apr", "02 May", "03 June", "04 July", "05 Aug"
            $monthLabel = sprintf('%02d %s', $fyIndex, $monthName);

            $rows[] = [
                'ym' => $ym,
                'month_label' => $monthLabel,
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

        return view('reports.monthly_production.index', compact(
            'rows',
            'grandTotals',
            'inward',
            'fromMonth',
            'toMonth'
        ));
    }
}
