<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Party;
use App\Models\Supplier;
use App\Models\RollSize;
use App\Models\LoomNumber;
use App\Models\FabricColor;
use App\Models\InTransaction;
use App\Models\InDispatch;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Basic Master Counts
        $partiesCount = Party::count();
        $suppliersCount = Supplier::count();
        $rollSizesCount = RollSize::count();
        $loomsCount = LoomNumber::count();
        $colorsCount = FabricColor::count();

        // 2. Determine Selected Financial Year (FY) - starting from 2025
        $startYear = 2025;
        $latestDate = DB::table('intransaction')->where('IsActive', 1)->max('EntryDate');
        $latestYear = $latestDate ? (int)date('Y', strtotime($latestDate)) : (int)date('Y');
        $latestMonth = $latestDate ? (int)date('n', strtotime($latestDate)) : (int)date('n');
        $currentFY = ($latestMonth >= 4) ? $latestYear : ($latestYear - 1);
        $maxYear = max($currentFY, (int)date('Y'), $startYear);

        // Build list of available FYs starting from 2025
        $availableFYs = [];
        for ($y = $startYear; $y <= $maxYear; $y++) {
            $availableFYs[$y] = sprintf('%04d-%02d', $y, ($y + 1) % 100);
        }

        // Default to FY 2026-27 on initial load, or user selected FY
        $defaultFY = array_key_exists(2026, $availableFYs) ? 2026 : $maxYear;
        $selectedFY = (int)$request->input('fy', $defaultFY);
        if (!array_key_exists($selectedFY, $availableFYs)) {
            $selectedFY = $defaultFY;
        }

        $inward = $request->input('inward', 'all');
        $selectedLoom = $request->input('loom_id', 'all');

        $fyFromDate = sprintf('%04d-04-01 00:00:00', $selectedFY);
        $fyToDate = sprintf('%04d-03-31 23:59:59', $selectedFY + 1);

        // ---------------------------------------------------------
        // SECTION 1: MONTHLY PRODUCTION GRAPHS DATA
        // ---------------------------------------------------------
        $prodQuery = DB::table('intransaction as t')
            ->where('t.IsActive', 1)
            ->whereBetween('t.EntryDate', [$fyFromDate, $fyToDate]);

        $this->applyInwardFilter($prodQuery, $inward);

        if ($selectedLoom !== 'all' && !empty($selectedLoom)) {
            $prodQuery->where('t.LoomNumber', $selectedLoom);
        }

        $prodMonthly = $prodQuery->select([
            DB::raw("DATE_FORMAT(t.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.ActualMeter AS DECIMAL(12,2))) as total_meters"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(12,2))) as total_net_weight"),
            DB::raw("COUNT(t.ID) as total_rolls")
        ])
        ->groupBy('ym')
        ->orderBy('ym', 'asc')
        ->get()
        ->keyBy('ym');

        // Build 12-month array (April to March)
        $monthLabels = [];
        $prodMetersData = [];
        $prodWeightData = [];
        $totalActualMeters = 0;
        $totalNetWeight = 0;
        $totalRollsInward = 0;
        $monthlyWeights = [];

        for ($i = 4; $i <= 15; $i++) {
            $m = ($i > 12) ? ($i - 12) : $i;
            $y = ($i > 12) ? ($selectedFY + 1) : $selectedFY;
            $ym = sprintf('%04d-%02d', $y, $m);
            $monthShort = date('M', strtotime("$y-$m-01"));

            $meters = isset($prodMonthly[$ym]) ? (float)$prodMonthly[$ym]->total_meters : 0;
            $weight = isset($prodMonthly[$ym]) ? (float)$prodMonthly[$ym]->total_net_weight : 0;
            $rolls = isset($prodMonthly[$ym]) ? (int)$prodMonthly[$ym]->total_rolls : 0;

            $monthLabels[] = $monthShort;
            $prodMetersData[] = round($meters, 1);
            $prodWeightData[] = round($weight, 1);

            $totalActualMeters += $meters;
            $totalNetWeight += $weight;
            $totalRollsInward += $rolls;

            if ($weight > 0) {
                $monthlyWeights[] = $weight;
            }
        }

        // Calculate Growth Rate (last active month vs prior month)
        $growthRate = 0;
        $growthDirection = 'neutral';
        if (count($monthlyWeights) >= 2) {
            $lastWeight = end($monthlyWeights);
            $prevWeight = prev($monthlyWeights);
            if ($prevWeight > 0) {
                $growthRate = round((($lastWeight - $prevWeight) / $prevWeight) * 100, 1);
                $growthDirection = ($growthRate > 0) ? 'up' : (($growthRate < 0) ? 'down' : 'neutral');
            }
        }

        // Average Output per Loom (meters per active loom per production day)
        $activeLoomsCount = ($selectedLoom !== 'all' && !empty($selectedLoom)) ? 1 : ($loomsCount > 0 ? $loomsCount : 1);
        $productionDaysQuery = DB::table('intransaction as t')
            ->where('t.IsActive', 1)
            ->whereBetween('t.EntryDate', [$fyFromDate, $fyToDate]);
        $this->applyInwardFilter($productionDaysQuery, $inward);
        if ($selectedLoom !== 'all' && !empty($selectedLoom)) {
            $productionDaysQuery->where('t.LoomNumber', $selectedLoom);
        }
        $activeDaysCount = $productionDaysQuery->distinct()->count(DB::raw('DATE(t.EntryDate)'));
        $divisor = $activeLoomsCount * ($activeDaysCount > 0 ? $activeDaysCount : 1);
        $avgOutputPerLoom = $totalActualMeters > 0 ? round($totalActualMeters / $divisor, 1) : 0;

        // ---------------------------------------------------------
        // SECTION 2: MONTHLY ROLLS TRANSFER & DISPATCH GRAPHS DATA
        // ---------------------------------------------------------
        // Dispatch Query
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
            })
            ->whereBetween('d.EntryDate', [$fyFromDate, $fyToDate]);

        $this->applyInwardFilter($dispatchQuery, $inward);

        $dispatchMonthly = $dispatchQuery->select([
            DB::raw("DATE_FORMAT(d.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(12,2))) as total_nw"),
            DB::raw("COUNT(dc.ID) as total_rolls")
        ])
        ->groupBy('ym')
        ->get()
        ->keyBy('ym');

        // Transfer Query
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
            ->where('d.DispatchType', 'Transfer')
            ->whereBetween('d.EntryDate', [$fyFromDate, $fyToDate]);

        $this->applyInwardFilter($transferQuery, $inward);

        $transferMonthly = $transferQuery->select([
            DB::raw("DATE_FORMAT(d.EntryDate, '%Y-%m') as ym"),
            DB::raw("SUM(CAST(t.NetWeight AS DECIMAL(12,2))) as total_nw"),
            DB::raw("COUNT(dc.ID) as total_rolls")
        ])
        ->groupBy('ym')
        ->get()
        ->keyBy('ym');

        $dispatchNwData = [];
        $transferNwData = [];
        $totalDispatchNw = 0;
        $totalTransferNw = 0;
        $totalDispatchedRolls = 0;
        $totalTransferredRolls = 0;

        for ($i = 4; $i <= 15; $i++) {
            $m = ($i > 12) ? ($i - 12) : $i;
            $y = ($i > 12) ? ($selectedFY + 1) : $selectedFY;
            $ym = sprintf('%04d-%02d', $y, $m);

            $dNw = isset($dispatchMonthly[$ym]) ? (float)$dispatchMonthly[$ym]->total_nw : 0;
            $dRolls = isset($dispatchMonthly[$ym]) ? (int)$dispatchMonthly[$ym]->total_rolls : 0;

            $tNw = isset($transferMonthly[$ym]) ? (float)$transferMonthly[$ym]->total_nw : 0;
            $tRolls = isset($transferMonthly[$ym]) ? (int)$transferMonthly[$ym]->total_rolls : 0;

            $dispatchNwData[] = round($dNw, 1);
            $transferNwData[] = round($tNw, 1);

            $totalDispatchNw += $dNw;
            $totalDispatchedRolls += $dRolls;

            $totalTransferNw += $tNw;
            $totalTransferredRolls += $tRolls;
        }

        // Pending Dispatches (Active rolls produced but not yet dispatched or transferred)
        $pendingDispatchRolls = InTransaction::where('TransactionType', 1)
            ->where('IsActive', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('indispatchchild as dc')
                  ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                  ->whereColumn('dc.InTransactionID', 'intransaction.ID')
                  ->whereColumn('dc.SourceType', 'intransaction.TransactionType')
                  ->where(function ($sq) { $sq->whereNull('dc.IsActive')->orWhere('dc.IsActive', 1); })
                  ->where(function ($sq) { $sq->whereNull('d.IsActive')->orWhere('d.IsActive', 1); });
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('intransferchild as tc')
                  ->join('intransfer as t2', 'tc.Transfer', '=', 't2.ID')
                  ->whereColumn('tc.InTransactionID', 'intransaction.ID')
                  ->whereColumn('tc.SourceType', 'intransaction.TransactionType')
                  ->where(function ($sq) { $sq->whereNull('tc.IsActive')->orWhere('tc.IsActive', 1); })
                  ->where(function ($sq) { $sq->whereNull('t2.IsActive')->orWhere('t2.IsActive', 1); });
            })
            ->count();

        // Dispatch vs Transfer Percentage
        $totalCombinedNw = $totalDispatchNw + $totalTransferNw;
        $dispatchPercent = $totalCombinedNw > 0 ? round(($totalDispatchNw / $totalCombinedNw) * 100) : 50;
        $transferPercent = $totalCombinedNw > 0 ? round(($totalTransferNw / $totalCombinedNw) * 100) : 50;

        // Recent Movement Preview Table (latest 6 entries)
        $recentMovements = DB::table('indispatch as d')
            ->leftJoin('umparty as p', 'd.PartyName', '=', 'p.ID')
            ->where(function ($q) {
                $q->whereNull('d.IsActive')->orWhere('d.IsActive', 1);
            })
            ->orderBy('d.EntryDate', 'desc')
            ->orderBy('d.ID', 'desc')
            ->limit(6)
            ->select([
                'd.ID',
                'd.EntryDate',
                'd.InvoiceNumber',
                'd.DispatchType',
                'd.TotalRolls',
                'p.PartyName as party_name'
            ])
            ->get();

        $looms = LoomNumber::orderBy('LoomNumber', 'asc')->get();

        return view('dashboard', compact(
            'partiesCount',
            'suppliersCount',
            'rollSizesCount',
            'loomsCount',
            'colorsCount',
            'selectedFY',
            'availableFYs',
            'inward',
            'selectedLoom',
            'looms',
            'monthLabels',
            'prodMetersData',
            'prodWeightData',
            'totalActualMeters',
            'totalNetWeight',
            'growthRate',
            'growthDirection',
            'avgOutputPerLoom',
            'totalRollsInward',
            'dispatchNwData',
            'transferNwData',
            'totalDispatchNw',
            'totalTransferNw',
            'totalDispatchedRolls',
            'totalTransferredRolls',
            'pendingDispatchRolls',
            'dispatchPercent',
            'transferPercent',
            'recentMovements'
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
    }
}
