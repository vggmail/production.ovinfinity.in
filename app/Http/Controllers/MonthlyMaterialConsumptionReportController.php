<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\LoomNumber;
use App\Models\ItemMaster;

class MonthlyMaterialConsumptionReportController extends Controller
{
    public function index(Request $request)
    {
        // Default month range: Current Year Start (or current month) to Current Month
        $defaultFromMonth = date('Y-01');
        $defaultToMonth = date('Y-m');

        // Allow month filter, or fallback from date filter when switching tabs
        $fromMonth = $request->input('from_month');
        $toMonth = $request->input('to_month');

        if (empty($fromMonth) && $request->has('from_date')) {
            $fromMonth = date('Y-m', strtotime($request->input('from_date')));
        }
        if (empty($toMonth) && $request->has('to_date')) {
            $toMonth = date('Y-m', strtotime($request->input('to_date')));
        }

        if (empty($fromMonth)) {
            $fromMonth = $defaultFromMonth;
        }
        if (empty($toMonth)) {
            $toMonth = $defaultToMonth;
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

        if ($fromMonth) {
            $query->whereRaw("DATE_FORMAT(i.IssueDate, '%Y-%m') >= ?", [$fromMonth]);
        }
        if ($toMonth) {
            $query->whereRaw("DATE_FORMAT(i.IssueDate, '%Y-%m') <= ?", [$toMonth]);
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

        // Fetch monthly grouped raw records
        $rawRecords = $query->select([
            DB::raw("DATE_FORMAT(i.IssueDate, '%Y-%m') as ym"),
            'ic.ItemMaster',
            DB::raw("SUM(CAST(ic.Quantity AS DECIMAL(12,2))) as total_qty")
        ])
        ->groupBy('ym', 'ic.ItemMaster')
        ->orderBy('ym', 'asc')
        ->get();

        // Group by Year-Month to aggregate Qty and calculated Value
        $monthlyAggregated = [];
        foreach ($rawRecords as $rec) {
            $ymStr = $rec->ym;
            $qty = (float)$rec->total_qty;
            $rate = $itemRates[$rec->ItemMaster] ?? 0;
            $val = $qty * $rate;

            if (!isset($monthlyAggregated[$ymStr])) {
                $monthlyAggregated[$ymStr] = [
                    'qty' => 0,
                    'value' => 0,
                ];
            }
            $monthlyAggregated[$ymStr]['qty'] += $qty;
            $monthlyAggregated[$ymStr]['value'] += $val;
        }

        $rows = [];
        $grandTotalQty = 0;
        $grandTotalValue = 0;

        foreach ($monthlyAggregated as $ymStr => $data) {
            $monthLabel = date('M Y', strtotime($ymStr . '-01'));

            $rows[] = [
                'ym' => $ymStr,
                'month_label' => $monthLabel,
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

        // Format month title range e.g. "Jan 2026 to Sep 2026"
        $dateTitle = date('M Y', strtotime($fromMonth . '-01')) . ' to ' . date('M Y', strtotime($toMonth . '-01'));

        return view('reports.material_consumption.monthly', compact(
            'rows',
            'grandTotals',
            'fromMonth',
            'toMonth',
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
