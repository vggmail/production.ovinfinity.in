<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CurrentStockReportController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->input('department_id', 'all');
        $itemId = $request->input('item_id', 'all');
        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'all');

        // Fetch active departments for filter dropdown
        $departments = Department::where('IsActive', 1)
            ->orderBy('DepartmentName', 'asc')
            ->get();

        // Fetch all active items for dynamic cascading dropdown
        $allItems = ItemMaster::where('IsActive', 1)
            ->orderBy('ItemName', 'asc')
            ->get(['ID', 'ItemName', 'PartNo', 'CatalogueNo', 'Department']);

        $data = $this->getReportData($departmentId, $search, $statusFilter, $itemId);

        return view('reports.current_stock.index', [
            'rows' => $data['rows'],
            'grandTotals' => $data['grandTotals'],
            'summaryCounts' => $data['summaryCounts'],
            'departments' => $departments,
            'departmentId' => $departmentId,
            'allItems' => $allItems,
            'itemId' => $itemId,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function export(Request $request)
    {
        $departmentId = $request->input('department_id', 'all');
        $itemId = $request->input('item_id', 'all');
        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'all');

        $data = $this->getReportData($departmentId, $search, $statusFilter, $itemId);
        $rows = $data['rows'];
        $grandTotals = $data['grandTotals'];

        $fileName = 'current_stock_report_' . date('Y-m-d_His') . '.csv';

        $response = new StreamedResponse(function () use ($rows, $grandTotals) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header Row matching mockup
            fputcsv($handle, [
                'Sr No',
                'Item Name',
                'PART No',
                'Catalogue No',
                'Department',
                'Minimum Qty',
                'Current Stock Qty',
                'Unit',
                'Rate (INR)',
                'Current Stock Value (INR)',
                'Age',
                'Status'
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['item_name'],
                    $row['part_no'] ?: '-',
                    $row['catalogue_no'] ?: '-',
                    $row['department_name'],
                    number_format($row['min_qty'], 2, '.', ''),
                    number_format($row['current_stock_qty'], 2, '.', ''),
                    $row['unit'] ?: 'Nos',
                    number_format($row['rate'], 2, '.', ''),
                    number_format($row['current_stock_val'], 2, '.', ''),
                    $row['age'],
                    $row['status_label']
                ]);
            }

            // Grand Total Row
            fputcsv($handle, [
                'Grand Total',
                '',
                '',
                '',
                '',
                number_format($grandTotals['min_qty'], 2, '.', ''),
                number_format($grandTotals['current_stock_qty'], 2, '.', ''),
                '',
                '',
                number_format($grandTotals['current_stock_val'], 2, '.', ''),
                '',
                ''
            ]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * Retrieve and calculate stock, valuation, age, and status data.
     */
    private function getReportData($departmentId, $search, $statusFilter, $itemId = 'all')
    {
        // 1. Query ItemMaster records
        $itemQuery = ItemMaster::with('departmentRelation')
            ->where('IsActive', 1);

        if (!empty($departmentId) && $departmentId !== 'all') {
            if (is_array($departmentId)) {
                $cleanDepts = array_filter($departmentId, function ($v) {
                    return !empty($v) && $v !== 'all';
                });
                if (!empty($cleanDepts)) {
                    $itemQuery->whereIn('Department', $cleanDepts);
                }
            } else {
                $itemQuery->where('Department', $departmentId);
            }
        }

        if (!empty($itemId) && $itemId !== 'all') {
            $itemQuery->where('ID', $itemId);
        }

        if (!empty($search)) {
            $itemQuery->where(function ($q) use ($search) {
                $q->where('ItemName', 'like', "%{$search}%")
                  ->orWhere('PartNo', 'like', "%{$search}%")
                  ->orWhere('CatalogueNo', 'like', "%{$search}%");
            });
        }

        $items = $itemQuery->orderBy('ItemName', 'asc')->get();

        // 2. Fetch cumulative received quantities and latest GRN date per item
        $grnAgg = [];
        if (DB::getSchemaBuilder()->hasTable('ingrnchild')) {
            $grnAgg = DB::table('ingrnchild as gc')
                ->join('ingrn as g', 'gc.GRN', '=', 'g.ID')
                ->where(function ($q) {
                    $q->whereNull('g.IsActive')->orWhere('g.IsActive', 1);
                })
                ->select(
                    'gc.ItemMaster',
                    DB::raw('SUM(CAST(gc.Quantity AS DECIMAL(14,2))) as total_received'),
                    DB::raw('MAX(g.GRNDate) as latest_grn_date')
                )
                ->groupBy('gc.ItemMaster')
                ->get()
                ->keyBy('ItemMaster');
        }

        // 3. Fetch latest purchase rate per item from GRN
        $latestRates = [];
        if (DB::getSchemaBuilder()->hasTable('ingrnchild')) {
            $latestRates = DB::table('ingrnchild as gc')
                ->join('ingrn as g', 'gc.GRN', '=', 'g.ID')
                ->where('gc.Rate', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('g.IsActive')->orWhere('g.IsActive', 1);
                })
                ->orderBy('g.GRNDate', 'desc')
                ->orderBy('gc.ID', 'desc')
                ->select('gc.ItemMaster', 'gc.Rate')
                ->get()
                ->unique('ItemMaster')
                ->pluck('Rate', 'ItemMaster');
        }

        // 4. Fetch cumulative issued quantities per item
        $issueAgg = [];
        if (DB::getSchemaBuilder()->hasTable('inmaterialissuechild')) {
            $issueAgg = DB::table('inmaterialissuechild as ic')
                ->join('inmaterialissue as i', 'ic.MaterialIssue', '=', 'i.ID')
                ->where(function ($q) {
                    $q->whereNull('i.IsActive')->orWhere('i.IsActive', 1);
                })
                ->where(function ($q) {
                    $q->whereNull('ic.IsActive')->orWhere('ic.IsActive', 1);
                })
                ->select(
                    'ic.ItemMaster',
                    DB::raw('SUM(CAST(ic.Quantity AS DECIMAL(14,2))) as total_issued')
                )
                ->groupBy('ic.ItemMaster')
                ->get()
                ->keyBy('ItemMaster');
        }

        $rows = [];
        $grandTotals = [
            'min_qty' => 0,
            'current_stock_qty' => 0,
            'current_stock_val' => 0,
        ];

        $summaryCounts = [
            'total_items' => count($items),
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];

        $now = Carbon::now();

        foreach ($items as $item) {
            $totalReceived = isset($grnAgg[$item->ID]) ? (float)$grnAgg[$item->ID]->total_received : 0;
            $totalIssued = isset($issueAgg[$item->ID]) ? (float)$issueAgg[$item->ID]->total_issued : 0;

            $currentStock = max(0, $totalReceived - $totalIssued);
            $minQty = (float)($item->MinQuantity ?? 0);
            $rate = isset($latestRates[$item->ID]) ? (float)$latestRates[$item->ID] : 0;
            $stockVal = $currentStock * $rate;

            // Age calculation: days since latest GRN inward, or days since item creation
            $latestGrnDate = isset($grnAgg[$item->ID]->latest_grn_date) ? $grnAgg[$item->ID]->latest_grn_date : null;
            if ($latestGrnDate) {
                $days = Carbon::parse($latestGrnDate)->diffInDays($now);
                $age = $days . ' ' . ($days == 1 ? 'Day' : 'Days');
            } elseif ($currentStock > 0 && $item->CreatedOn) {
                $days = Carbon::parse($item->CreatedOn)->diffInDays($now);
                $age = $days . ' ' . ($days == 1 ? 'Day' : 'Days');
            } else {
                $age = '-';
            }

            // Status determination: In Stock vs Low Stock (current stock >= min qty and > 0 is In Stock; below min qty is Low Stock)
            if ($currentStock > 0 && $currentStock >= $minQty) {
                $statusKey = 'in_stock';
                $statusLabel = 'In Stock';
                $statusBadgeClass = 'badge-in-stock';
                $summaryCounts['in_stock']++;
            } else {
                $statusKey = 'low_stock';
                $statusLabel = 'Low Stock';
                $statusBadgeClass = 'badge-low-stock';
                $summaryCounts['low_stock']++;
            }

            // Filter check for status
            if ($statusFilter !== 'all' && $statusFilter !== $statusKey) {
                continue;
            }

            $rawUnit = trim($item->Unit ?? '');
            $cleanUnit = (empty($rawUnit) || strtolower($rawUnit) === 'not required') ? '' : $rawUnit;

            $rows[] = [
                'item_id' => $item->ID,
                'item_name' => $item->ItemName,
                'part_no' => $item->PartNo,
                'catalogue_no' => $item->CatalogueNo,
                'unit' => $cleanUnit,
                'department_name' => $item->departmentRelation ? $item->departmentRelation->DepartmentName : 'General',
                'min_qty' => $minQty,
                'current_stock_qty' => $currentStock,
                'rate' => $rate,
                'current_stock_val' => $stockVal,
                'age' => $age,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'status_class' => $statusBadgeClass,
            ];

            $grandTotals['min_qty'] += $minQty;
            $grandTotals['current_stock_qty'] += $currentStock;
            $grandTotals['current_stock_val'] += $stockVal;
        }

        return [
            'rows' => $rows,
            'grandTotals' => $grandTotals,
            'summaryCounts' => $summaryCounts,
        ];
    }
}
