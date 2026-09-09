<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PI;
use App\Models\PIChild;
use App\Models\MRLEntry;
use App\Models\MRLEntryChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PIController extends Controller
{
    public function index()
    {
        return view('store.pi.index');
    }

    public function data(Request $request)
    {
        $query = PI::with('supplierRelation');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('InvoiceEntryNo', 'like', "%{$search}%")
                  ->orWhere('PINumber', 'like', "%{$search}%")
                  ->orWhere('PIDate', 'like', "%{$search}%")
                  ->orWhereHas('supplierRelation', function ($sq) use ($search) {
                      $sq->where('SupplierName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'InvoiceEntryNo', 'PINumber', 'PIDate', 'TotalItems', 'TotalQuantity', 'TotalPIAmount', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->SupplierName = $item->supplierRelation->SupplierName ?? 'N/A';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $pi = new PI();
        $pi->PIDate = date('Y-m-d');
        
        // Auto generate Invoice Entry No e.g. PI-INV-20260906-0001
        $latest = PI::latest('ID')->first();
        $nextNum = $latest ? ($latest->ID + 1) : 1;
        $pi->InvoiceEntryNo = 'PI-INV-' . date('Ymd') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $mrlEntries = MRLEntry::where('IsActive', 1)->orderBy('ID', 'desc')->get();

        return view('store.pi.form', compact('pi', 'suppliers', 'mrlEntries'));
    }

    public function fetchMrlItems(Request $request)
    {
        $mrlIds = $request->input('mrl_ids');

        if (empty($mrlIds)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        if (is_string($mrlIds)) {
            $mrlIds = explode(',', $mrlIds);
        }

        $mrlEntries = MRLEntry::with(['children.itemMasterRelation'])
            ->whereIn('ID', $mrlIds)
            ->orderBy('ID', 'asc')
            ->get();

        $items = [];
        foreach ($mrlEntries as $mrl) {
            foreach ($mrl->children as $child) {
                $itemMaster = $child->itemMasterRelation;
                $items[] = [
                    'mrl_entry_id' => $mrl->ID,
                    'mrl_child_id' => $child->ID,
                    'item_id' => $child->ItemMaster,
                    'item_name' => $itemMaster->ItemName ?? 'Unknown Item',
                    'part_no' => $itemMaster->PartNo ?? '',
                    'catalogue_no' => $itemMaster->CatalogueNo ?? '',
                    'hsn_no' => $itemMaster->HSNNo ?? '',
                    'default_gst' => $itemMaster->GSTPercentage ?? 0,
                    'quantity' => $child->Quantity,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => count($items)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'InvoiceEntryNo' => 'nullable|string|max:100',
            'PIDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'PINumber' => 'required|string|max:100|unique:inpi,PINumber',
            'MRLNumbers' => 'nullable|array',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.001',
            'items.*.BasicRate' => 'required|numeric|min:0',
            'items.*.TradeDisPercent' => 'nullable|numeric|min:0',
            'items.*.AdPayDisPercent' => 'nullable|numeric|min:0',
            'items.*.PackChargesPercent' => 'nullable|numeric|min:0',
            'items.*.FreightPercent' => 'nullable|numeric|min:0',
            'items.*.GSTRate' => 'required|integer|min:0',
        ], [
            'items.required' => 'At least one item must be included in the PI.',
            'items.min' => 'At least one item must be included in the PI.',
            'Supplier.required' => 'Please select a supplier.',
            'PINumber.required' => 'PI Number is required.',
            'PINumber.unique' => 'This PI Number already exists. Duplicate PI Numbers are not allowed.',
        ]);

        $pi = null;
        DB::transaction(function () use ($request, $validated, &$pi) {
            if (empty($validated['InvoiceEntryNo'])) {
                $latest = PI::latest('ID')->first();
                $nextNum = $latest ? ($latest->ID + 1) : 1;
                $validated['InvoiceEntryNo'] = 'PI-INV-' . date('Ymd') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }

            $piNumber = strtoupper(trim($validated['PINumber']));
            $mrlNumbersJson = !empty($validated['MRLNumbers']) ? json_encode($validated['MRLNumbers']) : null;

            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAdPayDis = 0;
            $totalNetValAfterDis = 0;
            $totalPackCharges = 0;
            $totalFreight = 0;
            $totalNet = 0;
            $totalGST = 0;
            $totalPI = 0;

            $processedRows = [];

            foreach ($request->input('items', []) as $row) {
                $qty = (float)($row['Quantity'] ?? 0);
                $basicRate = (float)($row['BasicRate'] ?? 0);
                $amount = $qty * $basicRate;

                $tradeDisPercent = (float)($row['TradeDisPercent'] ?? 0);
                $tradeDisAmount = $amount * ($tradeDisPercent / 100);
                $netValAfterTrade = $amount - $tradeDisAmount;

                $adPayDisPercent = (float)($row['AdPayDisPercent'] ?? 0);
                $adPayDisAmount = $netValAfterTrade * ($adPayDisPercent / 100);
                $netValAfterDis = $netValAfterTrade - $adPayDisAmount;

                $packChargesPercent = (float)($row['PackChargesPercent'] ?? 0);
                $packChargesAmount = $netValAfterTrade * ($packChargesPercent / 100);

                $freightPercent = (float)($row['FreightPercent'] ?? 0);
                $freightAmount = $netValAfterTrade * ($freightPercent / 100);

                $netAmount = $netValAfterDis + $packChargesAmount + $freightAmount;

                $gstRate = (float)($row['GSTRate'] ?? 0);
                $gstAmount = $netAmount * ($gstRate / 100);

                $piAmount = $netAmount + $gstAmount;
                $netRate = $qty > 0 ? ($netAmount / $qty) : 0;

                // Accumulate header totals
                $totalQuantity += $qty;
                $totalAdPayDis += $adPayDisAmount;
                $totalNetValAfterDis += $netValAfterDis;
                $totalPackCharges += $packChargesAmount;
                $totalFreight += $freightAmount;
                $totalNet += $netAmount;
                $totalGST += $gstAmount;
                $totalPI += $piAmount;

                $processedRows[] = [
                    'MRLEntryChild' => !empty($row['MRLEntryChild']) ? $row['MRLEntryChild'] : null,
                    'MRLEntry' => !empty($row['MRLEntry']) ? $row['MRLEntry'] : null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'BasicRate' => $basicRate,
                    'Amount' => $amount,
                    'TradeDisPercent' => $tradeDisPercent,
                    'TradeDisAmount' => $tradeDisAmount,
                    'NetValAfterTrade' => $netValAfterTrade,
                    'AdPayDisPercent' => $adPayDisPercent,
                    'AdPayDisAmount' => $adPayDisAmount,
                    'NetValAfterDis' => $netValAfterDis,
                    'PackChargesPercent' => $packChargesPercent,
                    'PackChargesAmount' => $packChargesAmount,
                    'FreightPercent' => $freightPercent,
                    'FreightAmount' => $freightAmount,
                    'NetAmount' => $netAmount,
                    'GSTRate' => $gstRate,
                    'GSTAmount' => $gstAmount,
                    'PIAmount' => $piAmount,
                    'NetRate' => $netRate,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ];
            }

            $pi = PI::create([
                'InvoiceEntryNo' => $validated['InvoiceEntryNo'],
                'PIDate' => $validated['PIDate'],
                'Supplier' => $validated['Supplier'],
                'PINumber' => $piNumber,
                'MRLNumbers' => $mrlNumbersJson,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAdPayDisAmount' => $totalAdPayDis,
                'TotalNetValAfterDis' => $totalNetValAfterDis,
                'TotalPackChargesAmount' => $totalPackCharges,
                'TotalFreightAmount' => $totalFreight,
                'TotalNetAmount' => $totalNet,
                'TotalGSTAmount' => $totalGST,
                'TotalPIAmount' => $totalPI,
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($processedRows as $row) {
                $row['PI'] = $pi->ID;
                PIChild::create($row);
            }
        });

        return redirect()->route('store.pi.index')->with('success', "Proforma Invoice {$pi->PINumber} created successfully.");
    }

    public function edit($id)
    {
        $pi = PI::with(['children.itemMasterRelation', 'supplierRelation'])->findOrFail($id);
        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $mrlEntries = MRLEntry::where('IsActive', 1)->orderBy('ID', 'desc')->get();

        return view('store.pi.form', compact('pi', 'suppliers', 'mrlEntries'));
    }

    public function update(Request $request, $id)
    {
        $pi = PI::findOrFail($id);

        $validated = $request->validate([
            'InvoiceEntryNo' => 'required|string|max:100',
            'PIDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'PINumber' => 'required|string|max:100|unique:inpi,PINumber,' . $id . ',ID',
            'MRLNumbers' => 'nullable|array',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.001',
            'items.*.BasicRate' => 'required|numeric|min:0',
            'items.*.TradeDisPercent' => 'nullable|numeric|min:0',
            'items.*.AdPayDisPercent' => 'nullable|numeric|min:0',
            'items.*.PackChargesPercent' => 'nullable|numeric|min:0',
            'items.*.FreightPercent' => 'nullable|numeric|min:0',
            'items.*.GSTRate' => 'required|integer|min:0',
        ], [
            'items.required' => 'At least one item must be included in the PI.',
            'items.min' => 'At least one item must be included in the PI.',
            'Supplier.required' => 'Please select a supplier.',
            'PINumber.required' => 'PI Number is required.',
            'PINumber.unique' => 'This PI Number already exists. Duplicate PI Numbers are not allowed.',
        ]);

        DB::transaction(function () use ($request, $pi, $validated) {
            $piNumber = strtoupper(trim($validated['PINumber']));
            $mrlNumbersJson = !empty($validated['MRLNumbers']) ? json_encode($validated['MRLNumbers']) : null;

            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAdPayDis = 0;
            $totalNetValAfterDis = 0;
            $totalPackCharges = 0;
            $totalFreight = 0;
            $totalNet = 0;
            $totalGST = 0;
            $totalPI = 0;

            $processedRows = [];

            foreach ($request->input('items', []) as $row) {
                $qty = (float)($row['Quantity'] ?? 0);
                $basicRate = (float)($row['BasicRate'] ?? 0);
                $amount = $qty * $basicRate;

                $tradeDisPercent = (float)($row['TradeDisPercent'] ?? 0);
                $tradeDisAmount = $amount * ($tradeDisPercent / 100);
                $netValAfterTrade = $amount - $tradeDisAmount;

                $adPayDisPercent = (float)($row['AdPayDisPercent'] ?? 0);
                $adPayDisAmount = $netValAfterTrade * ($adPayDisPercent / 100);
                $netValAfterDis = $netValAfterTrade - $adPayDisAmount;

                $packChargesPercent = (float)($row['PackChargesPercent'] ?? 0);
                $packChargesAmount = $netValAfterTrade * ($packChargesPercent / 100);

                $freightPercent = (float)($row['FreightPercent'] ?? 0);
                $freightAmount = $netValAfterTrade * ($freightPercent / 100);

                $netAmount = $netValAfterDis + $packChargesAmount + $freightAmount;

                $gstRate = (float)($row['GSTRate'] ?? 0);
                $gstAmount = $netAmount * ($gstRate / 100);

                $piAmount = $netAmount + $gstAmount;
                $netRate = $qty > 0 ? ($netAmount / $qty) : 0;

                // Accumulate header totals
                $totalQuantity += $qty;
                $totalAdPayDis += $adPayDisAmount;
                $totalNetValAfterDis += $netValAfterDis;
                $totalPackCharges += $packChargesAmount;
                $totalFreight += $freightAmount;
                $totalNet += $netAmount;
                $totalGST += $gstAmount;
                $totalPI += $piAmount;

                $processedRows[] = [
                    'PI' => $pi->ID,
                    'MRLEntryChild' => !empty($row['MRLEntryChild']) ? $row['MRLEntryChild'] : null,
                    'MRLEntry' => !empty($row['MRLEntry']) ? $row['MRLEntry'] : null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'BasicRate' => $basicRate,
                    'Amount' => $amount,
                    'TradeDisPercent' => $tradeDisPercent,
                    'TradeDisAmount' => $tradeDisAmount,
                    'NetValAfterTrade' => $netValAfterTrade,
                    'AdPayDisPercent' => $adPayDisPercent,
                    'AdPayDisAmount' => $adPayDisAmount,
                    'NetValAfterDis' => $netValAfterDis,
                    'PackChargesPercent' => $packChargesPercent,
                    'PackChargesAmount' => $packChargesAmount,
                    'FreightPercent' => $freightPercent,
                    'FreightAmount' => $freightAmount,
                    'NetAmount' => $netAmount,
                    'GSTRate' => $gstRate,
                    'GSTAmount' => $gstAmount,
                    'PIAmount' => $piAmount,
                    'NetRate' => $netRate,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ];
            }

            $pi->update([
                'InvoiceEntryNo' => $validated['InvoiceEntryNo'],
                'PIDate' => $validated['PIDate'],
                'Supplier' => $validated['Supplier'],
                'PINumber' => $piNumber,
                'MRLNumbers' => $mrlNumbersJson,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAdPayDisAmount' => $totalAdPayDis,
                'TotalNetValAfterDis' => $totalNetValAfterDis,
                'TotalPackChargesAmount' => $totalPackCharges,
                'TotalFreightAmount' => $totalFreight,
                'TotalNetAmount' => $totalNet,
                'TotalGSTAmount' => $totalGST,
                'TotalPIAmount' => $totalPI,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            $pi->children()->delete();

            foreach ($processedRows as $row) {
                PIChild::create($row);
            }
        });

        return redirect()->route('store.pi.index')->with('success', 'Proforma Invoice updated successfully.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $pi = PI::findOrFail($id);
            $pi->children()->delete();
            $pi->delete();
        });

        return response()->json(['success' => true]);
    }

    public function print($id)
    {
        $pi = PI::with(['children.itemMasterRelation', 'supplierRelation'])->findOrFail($id);
        return view('store.pi.print', compact('pi'));
    }
}
