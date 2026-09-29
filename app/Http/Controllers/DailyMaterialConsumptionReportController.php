<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\LoomNumber;
use App\Models\ItemMaster;

class DailyMaterialConsumptionReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Line 7 Default Selection requirements:
        // From Date: current month start date (e.g. 2026-09-01)
        // To Date: today (e.g. 2026-09-28)
        $defaultFromDate = date('Y-m-01');
        $defaultToDate = date('Y-m-d');

        $fromDate = $request->input('from_date', $defaultFromDate);
        $toDate = $request->input('to_date', $defaultToDate);

        if (empty($fromDate)) {
            $fromDate = $defaultFromDate;
        }
        if (empty($toDate)) {
            $toDate = $defaultToDate;
        }

        $departmentId = $request->input('department_id', 'all');
        $loomId = $request->input('loom_id', 'all');
        $itemId = $request->input('item_id', 'all');

        // Fetch options for filter dropdowns
        $departments = Department::where('IsActive', 1)->orderBy('DepartmentName', 'asc')->get();
        $looms = LoomNumber::where('IsActive', 1)->orderBy('LoomNumber', 'asc')->get();
        $items = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        // Calculate item rates from GRN child table (latest GRN rate per item, defaulting to 0)
        $itemRates = [];
        if (DB::getSchemaBuilder()->hasTable('ingrnchild')) {
            $ratesQuery = DB::table('ingrnchild')
                ->select('ItemMaster', DB::raw('MAX(Rate) as rate'))
                ->where('Rate', '>', 0)
                ->groupBy('ItemMaster')
                ->get();
            foreach ($ratesQuery as $r) {
                $itemRates[$r->ItemMaster] = (float)$r->rate;
            }
        }

        // Query Material Issue Child joined with Header
        $query = DB::table('inmaterialissuechild as ic')
            ->join('inmaterialissue as i', 'ic.MaterialIssue', '=', 'i.ID')
            ->where(function ($q) {
                $q->whereNull('ic.IsActive')->orWhere('ic.IsActive', 1);
            })
            ->where(function ($q) {
                $q->whereNull('i.IsActive')->orWhere('i.IsActive', 1);
            });

        if ($fromDate) {
            $query->whereDate('i.IssueDate', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('i.IssueDate', '<=', $toDate);
        }

        if (!empty($departmentId) && $departmentId !== 'all') {
            $query->where('ic.Department', $departmentId);
        }

        if (!empty($loomId) && $loomId !== 'all') {
            $query->where('ic.LoomNumber', $loomId);
        }

        if (!empty($itemId) && $itemId !== 'all') {
            $query->where('ic.ItemMaster', $itemId);
        }

        // Fetch daily records
        $rawRecords = $query->select([
            DB::raw("DATE(i.IssueDate) as issue_date"),
            'ic.ItemMaster',
            DB::raw("SUM(CAST(ic.Quantity AS DECIMAL(12,2))) as total_qty")
        ])
        ->groupBy('issue_date', 'ic.ItemMaster')
        ->orderBy('issue_date', 'asc')
        ->get();

        // Group by day to aggregate Qty and calculated Value
        $dailyAggregated = [];
        foreach ($rawRecords as $rec) {
            $dateStr = $rec->issue_date;
            $qty = (float)$rec->total_qty;
            $rate = $itemRates[$rec->ItemMaster] ?? 0;
            $val = $qty * $rate;

            if (!isset($dailyAggregated[$dateStr])) {
                $dailyAggregated[$dateStr] = [
                    'qty' => 0,
                    'value' => 0,
                ];
            }
            $dailyAggregated[$dateStr]['qty'] += $qty;
            $dailyAggregated[$dateStr]['value'] += $val;
        }

        $rows = [];
        $grandTotalQty = 0;
        $grandTotalValue = 0;

        foreach ($dailyAggregated as $dateStr => $data) {
            // Format date matching mockup e.g. "9/1/2026" or "01/09/2026"
            $dateLabel = date('n/j/Y', strtotime($dateStr));

            $rows[] = [
                'raw_date' => $dateStr,
                'date_label' => $dateLabel,
                'qty' => $data['qty'],
                'value' => $data['value'],
            ];

            $grandTotalQty += $data['qty'];
            $grandTotalValue += $data['value'];
        }

        $grandTotals = [
            'qty' => $grandTotalQty,
            'value' => $grandTotalValue,
        ];

        // Format date title range
        $dateTitle = date('d/m/Y', strtotime($fromDate)) . ' to ' . date('d/m/Y', strtotime($toDate));

        return view('reports.material_consumption.daily', compact(
            'rows',
            'grandTotals',
            'fromDate',
            'toDate',
            'departmentId',
            'loomId',
            'itemId',
            'departments',
            'looms',
            'items',
            'dateTitle'
        ));
    }
}
