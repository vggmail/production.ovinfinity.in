<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $prod = $request->input('prod', 'all');
        $status = $request->input('status', 'active');
        $targetDate = $request->input('date', date('Y-m-d'));

        $query = DB::table('intransaction as t')
            ->leftJoin('umrollsize as rs', 't.RollSize', '=', 'rs.ID')
            ->leftJoin('umfabriccolor as fc', 't.FabricColor', '=', 'fc.ID')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('indispatchchild as dc')
                  ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                  ->whereColumn('dc.InTransactionID', 't.ID')
                  ->whereColumn('dc.SourceType', 't.TransactionType')
                  ->where('dc.IsActive', 1)
                  ->where('d.IsActive', 1);
            });

        if ($prod !== 'all' && in_array($prod, ['1', '2'])) {
            $query->where('t.TransactionType', (int)$prod);
        }

        if ($status === 'active') {
            $query->where('t.IsActive', 1);
        }

        $rawRows = $query->select([
            DB::raw("COALESCE(rs.RollSize, CAST(t.RollSize AS CHAR)) as SizeName"),
            't.RollSize',
            't.RequiredGramMeter as RGMW',
            DB::raw("COALESCE(fc.FabricColor, CAST(t.FabricColor AS CHAR)) as TypeName"),
            't.FabricColor',
            DB::raw('COUNT(t.ID) as RollsCount'),
            DB::raw('SUM(CAST(t.ActualMeter AS DECIMAL(10,2))) as SumMeter'),
            DB::raw('SUM(CAST(t.NetWeight AS DECIMAL(10,2))) as SumNW'),
            DB::raw('AVG(CAST(t.ActualMeterWeight AS DECIMAL(10,2))) as AvgAGRM'),
            DB::raw("ROUND(AVG(ABS(DATEDIFF('$targetDate', t.EntryDate)))) as AvgAge"),
        ])
        ->groupBy('rs.RollSize', 't.RollSize', 't.RequiredGramMeter', 'fc.FabricColor', 't.FabricColor')
        ->orderBy('rs.RollSize', 'asc')
        ->orderBy('t.RequiredGramMeter', 'asc')
        ->orderBy('fc.FabricColor', 'asc')
        ->get();

        // Calculate Grand Totals
        $grandTotalMeter = 0;
        $grandTotalNW = 0;
        $grandTotalRolls = 0;
        $weightedAgrmSum = 0;
        $weightedAgeSum = 0;

        foreach ($rawRows as $r) {
            $grandTotalMeter += (float)$r->SumMeter;
            $grandTotalNW += (float)$r->SumNW;
            $grandTotalRolls += (int)$r->RollsCount;
            $weightedAgrmSum += ((float)$r->AvgAGRM * (int)$r->RollsCount);
            $weightedAgeSum += ((float)$r->AvgAge * (int)$r->RollsCount);
        }

        $grandTotalAvgAGRM = $grandTotalRolls > 0 ? ($weightedAgrmSum / $grandTotalRolls) : 0;
        $grandTotalAvgAge = $grandTotalRolls > 0 ? round($weightedAgeSum / $grandTotalRolls) : 0;

        $grandTotals = [
            'SumMeter' => $grandTotalMeter,
            'SumNW' => $grandTotalNW,
            'AvgAGRM' => $grandTotalAvgAGRM,
            'AvgAge' => $grandTotalAvgAge,
            'Rolls' => $grandTotalRolls,
        ];

        // Process data structure for Blade View with Rowspans
        $groupedData = [];
        foreach ($rawRows as $row) {
            $sizeKey = $row->SizeName ?? 'Other';
            $rgmKey = $row->RGMW ?? 'Other';

            if (!isset($groupedData[$sizeKey])) {
                $groupedData[$sizeKey] = [
                    'sizeName' => $sizeKey,
                    'totalRows' => 0,
                    'rgms' => []
                ];
            }

            if (!isset($groupedData[$sizeKey]['rgms'][$rgmKey])) {
                $groupedData[$sizeKey]['rgms'][$rgmKey] = [
                    'rgmName' => $rgmKey,
                    'totalRows' => 0,
                    'items' => []
                ];
            }

            $groupedData[$sizeKey]['rgms'][$rgmKey]['items'][] = $row;
            $groupedData[$sizeKey]['rgms'][$rgmKey]['totalRows']++;
            $groupedData[$sizeKey]['totalRows']++;
        }

        return view('reports.summary.index', compact(
            'groupedData',
            'grandTotals',
            'prod',
            'status',
            'targetDate'
        ));
    }
}
