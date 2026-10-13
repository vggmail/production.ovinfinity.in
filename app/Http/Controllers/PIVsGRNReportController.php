<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PI;
use App\Models\PIChild;
use App\Models\GRNChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\DB;

class PIVsGRNReportController extends Controller
{
    public function index(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        $supplierId = $request->input('supplier_id');
        $itemId = $request->input('item_id');
        $piId = $request->input('pi_id');
        $status = $request->input('status', 'all');

        // Fetch suppliers for dropdown
        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();

        // Fetch PIs for dropdown (filtered by supplier if set)
        $pisQuery = PI::where('IsActive', 1);
        if ($supplierId) {
            $pisQuery->where('Supplier', $supplierId);
        }
        $pis = $pisQuery->orderBy('ID', 'desc')->get(['ID', 'PINumber', 'Supplier']);

        // Fetch Items for dropdown (filtered by PI or Supplier if set)
        $itemsQuery = ItemMaster::query();
        if ($piId) {
            $itemIds = PIChild::where('PI', $piId)->where('IsActive', 1)->pluck('ItemMaster')->toArray();
            $itemsQuery->whereIn('ID', $itemIds);
        } elseif ($supplierId) {
            $piIds = PI::where('Supplier', $supplierId)->where('IsActive', 1)->pluck('ID')->toArray();
            $itemIds = PIChild::whereIn('PI', $piIds)->where('IsActive', 1)->pluck('ItemMaster')->toArray();
            $itemsQuery->whereIn('ID', $itemIds);
        }
        $items = $itemsQuery->orderBy('ItemName', 'asc')->get(['ID', 'ItemName']);

        // Main report query for PI Child records
        $query = PIChild::with(['piRelation.supplierRelation', 'itemMasterRelation'])
            ->where('IsActive', 1)
            ->whereHas('piRelation', function ($q) use ($fromDate, $toDate, $supplierId, $piId) {
                $q->where('IsActive', 1);
                if ($fromDate) {
                    $q->where('PIDate', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->where('PIDate', '<=', $toDate);
                }
                if ($supplierId) {
                    $q->where('Supplier', $supplierId);
                }
                if ($piId) {
                    $q->where('ID', $piId);
                }
            });

        if ($itemId) {
            $query->where('ItemMaster', $itemId);
        }

        $piChildren = $query->get();

        $rows = [];
        $grandTotals = [
            'pi_qty' => 0,
            'pi_val' => 0,
            'grn_qty' => 0,
            'grn_val' => 0,
            'diff_qty' => 0,
            'diff_val' => 0,
        ];

        foreach ($piChildren as $child) {
            $pi = $child->piRelation;
            if (!$pi) continue;

            $piQty = (float)$child->Quantity;
            $piVal = (float)($child->PIAmount ?: ($child->NetAmount ?: ($piQty * $child->NetRate)));

            // Cumulative GRN Received for this PI Child
            $grnChildren = GRNChild::with('grnRelation')
                ->where('PIChild', $child->ID)
                ->whereHas('grnRelation', function ($q) {
                    $q->where('IsActive', 1);
                })
                ->get();

            $grnQty = 0;
            $grnVal = 0;
            $grnNumbersArr = [];

            foreach ($grnChildren as $gChild) {
                $grnQty += (float)$gChild->Quantity;
                $grnVal += (float)($gChild->TotalAmount ?: $gChild->Amount);
                if ($gChild->grnRelation && !empty($gChild->grnRelation->GRNNumber)) {
                    $grnNumbersArr[] = $gChild->grnRelation->GRNNumber;
                }
            }

            $grnNumbersArr = array_unique($grnNumbersArr);
            $grnNoStr = count($grnNumbersArr) > 0 ? implode(', ', $grnNumbersArr) : '-';

            $diffQty = $piQty - $grnQty;
            $diffVal = $piVal - $grnVal;

            // Status logic:
            // completed: grnQty >= piQty
            // partly: grnQty > 0 && grnQty < piQty
            // pending: grnQty == 0
            $itemStatus = 'pending';
            $statusLabel = 'Pending for Received (Active)';

            if ($grnQty >= $piQty && $piQty > 0) {
                $itemStatus = 'completed';
                $statusLabel = 'Completed (Closed)';
            } elseif ($grnQty > 0 && $grnQty < $piQty) {
                $itemStatus = 'partly';
                $statusLabel = 'Partly Received (Active)';
            }

            // Status filter check
            if ($status === 'completed' || $status === '2') {
                if ($itemStatus !== 'completed') continue;
            } elseif ($status === 'partly' || $status === '3') {
                if ($itemStatus !== 'partly') continue;
            } elseif ($status === 'pending' || $status === '4') {
                if ($itemStatus !== 'pending') continue;
            }

            $rows[] = [
                'pi_no' => $pi->PINumber ?? 'N/A',
                'grn_no' => $grnNoStr,
                'item_name' => $child->itemMasterRelation->ItemName ?? 'N/A',
                'pi_qty' => $piQty,
                'pi_val' => $piVal,
                'grn_qty' => $grnQty,
                'grn_val' => $grnVal,
                'diff_qty' => $diffQty,
                'diff_val' => $diffVal,
                'status' => $itemStatus,
                'status_label' => $statusLabel,
            ];

            $grandTotals['pi_qty'] += $piQty;
            $grandTotals['pi_val'] += $piVal;
            $grandTotals['grn_qty'] += $grnQty;
            $grandTotals['grn_val'] += $grnVal;
            $grandTotals['diff_qty'] += $diffQty;
            $grandTotals['diff_val'] += $diffVal;
        }

        return view('reports.pi_vs_grn.index', compact(
            'fromDate',
            'toDate',
            'supplierId',
            'itemId',
            'piId',
            'status',
            'suppliers',
            'pis',
            'items',
            'rows',
            'grandTotals'
        ));
    }

    public function filterOptions(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $piId = $request->input('pi_id');

        // Filter PIs
        $pisQuery = PI::where('IsActive', 1);
        if ($supplierId) {
            $pisQuery->where('Supplier', $supplierId);
        }
        $pis = $pisQuery->orderBy('ID', 'desc')->get(['ID', 'PINumber']);

        // Filter Items
        $itemsQuery = ItemMaster::query();
        if ($piId) {
            $itemIds = PIChild::where('PI', $piId)->where('IsActive', 1)->pluck('ItemMaster')->toArray();
            $itemsQuery->whereIn('ID', $itemIds);
        } elseif ($supplierId) {
            $piIds = PI::where('Supplier', $supplierId)->where('IsActive', 1)->pluck('ID')->toArray();
            $itemIds = PIChild::whereIn('PI', $piIds)->where('IsActive', 1)->pluck('ItemMaster')->toArray();
            $itemsQuery->whereIn('ID', $itemIds);
        }
        $items = $itemsQuery->orderBy('ItemName', 'asc')->get(['ID', 'ItemName']);

        return response()->json([
            'success' => true,
            'pis' => $pis,
            'items' => $items,
        ]);
    }
}
