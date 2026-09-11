<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GRN;
use App\Models\GRNChild;
use App\Models\PI;
use App\Models\PIChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GRNController extends Controller
{
    public function index()
    {
        return view('store.grn.index');
    }

    public function data(Request $request)
    {
        $query = GRN::with('supplierRelation')->where('IsActive', 1);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('GRNNumber', 'like', "%{$search}%")
                  ->orWhere('GRNDate', 'like', "%{$search}%")
                  ->orWhere('PINumbers', 'like', "%{$search}%")
                  ->orWhereHas('supplierRelation', function ($sq) use ($search) {
                      $sq->where('SupplierName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'GRNNumber', 'GRNDate', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->SupplierName = $item->supplierRelation->SupplierName ?? 'N/A';
            
            // Format PI Numbers for display
            $piArr = !empty($item->PINumbers) ? json_decode($item->PINumbers, true) : [];
            if (is_array($piArr) && count($piArr) > 0) {
                $piNumbers = PI::whereIn('ID', $piArr)->pluck('PINumber')->toArray();
                $item->PIDisplay = implode(', ', $piNumbers);
            } else {
                $item->PIDisplay = 'N/A';
            }

            // Total quantity calculated from children
            $item->TotalQty = GRNChild::where('GRN', $item->ID)->sum('Quantity');
            $item->TotalItemsCount = GRNChild::where('GRN', $item->ID)->count();

            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $grn = new GRN();
        $grn->GRNDate = date('Y-m-d');

        // Auto generate GRN No e.g. GRN-2026-0001
        $latest = GRN::latest('ID')->first();
        $nextNum = $latest ? ($latest->ID + 1) : 1;
        $year = date('Y');
        $grn->GRNNumber = 'GRN-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();

        return view('store.grn.form', compact('grn', 'suppliers'));
    }

    public function fetchPIs(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $currentGrnId = $request->input('current_grn_id');

        if (empty($supplierId)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Fetch active PIs for supplier
        $pis = PI::with('children')
            ->where('Supplier', $supplierId)
            ->where('IsActive', 1)
            ->orderBy('ID', 'desc')
            ->get();

        $pendingPIs = [];

        foreach ($pis as $pi) {
            $children = $pi->children;
            if ($children->isEmpty()) {
                continue;
            }

            $allCompleted = true;

            foreach ($children as $child) {
                // Calculate cumulative received quantity across all active GRNs for this PI child item
                $receivedQty = GRNChild::where('PIChild', $child->ID)
                    ->whereHas('grnRelation', function ($q) use ($currentGrnId) {
                        $q->where('IsActive', 1);
                        if ($currentGrnId) {
                            $q->where('ID', '!=', $currentGrnId);
                        }
                    })
                    ->sum('Quantity');

                // If at least one item has received < ordered Qty, then PI is not fully completed
                if ($receivedQty < (float)$child->Quantity) {
                    $allCompleted = false;
                    break;
                }
            }

            // Exclude PI if all items are fully received (unless it's selected in current GRN being edited)
            if (!$allCompleted) {
                $pendingPIs[] = [
                    'id' => $pi->ID,
                    'pi_number' => $pi->PINumber,
                    'pi_date' => $pi->PIDate,
                ];
            } else if ($currentGrnId) {
                // If editing existing GRN, check if this PI is part of current GRN
                $currentGrn = GRN::find($currentGrnId);
                if ($currentGrn && !empty($currentGrn->PINumbers)) {
                    $selectedPiIds = json_decode($currentGrn->PINumbers, true) ?? [];
                    if (in_array($pi->ID, $selectedPiIds)) {
                        $pendingPIs[] = [
                            'id' => $pi->ID,
                            'pi_number' => $pi->PINumber,
                            'pi_date' => $pi->PIDate,
                        ];
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $pendingPIs
        ]);
    }

    public function fetchPIItems(Request $request)
    {
        $piIds = $request->input('pi_ids');
        $currentGrnId = $request->input('current_grn_id');

        if (empty($piIds)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        if (is_string($piIds)) {
            $piIds = explode(',', $piIds);
        }

        $piEntries = PI::with(['children.itemMasterRelation'])
            ->whereIn('ID', $piIds)
            ->where('IsActive', 1)
            ->orderBy('ID', 'asc')
            ->get();

        $items = [];

        foreach ($piEntries as $pi) {
            foreach ($pi->children as $child) {
                $itemMaster = $child->itemMasterRelation;

                // Cumulative received quantity from other GRNs
                $alreadyReceived = GRNChild::where('PIChild', $child->ID)
                    ->whereHas('grnRelation', function ($q) use ($currentGrnId) {
                        $q->where('IsActive', 1);
                        if ($currentGrnId) {
                            $q->where('ID', '!=', $currentGrnId);
                        }
                    })
                    ->sum('Quantity');

                $orderedQty = (float)$child->Quantity;
                $pendingQty = max(0, $orderedQty - $alreadyReceived);

                // Default Qty Received in form
                $initialQtyReceived = $pendingQty;

                // If editing existing GRN, check if this line item has existing Qty in current GRN
                if ($currentGrnId) {
                    $existingChild = GRNChild::where('GRN', $currentGrnId)
                        ->where('PIChild', $child->ID)
                        ->first();
                    if ($existingChild) {
                        $initialQtyReceived = (float)$existingChild->Quantity;
                    }
                }

                $items[] = [
                    'pi_id' => $pi->ID,
                    'pi_child_id' => $child->ID,
                    'pi_number' => $pi->PINumber,
                    'item_id' => $child->ItemMaster,
                    'item_name' => $itemMaster->ItemName ?? 'Unknown Item',
                    'part_no' => $itemMaster->PartNo ?? '',
                    'ordered_qty' => $orderedQty,
                    'already_received' => $alreadyReceived,
                    'pending_qty' => $pendingQty,
                    'qty_received' => $initialQtyReceived,
                    'rate' => (float)$child->BasicRate,
                    'rack_no' => $itemMaster->RackNo ?? 'N/A',
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
            'GRNNumber' => 'nullable|string|max:100',
            'GRNDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'PINumbers' => 'required|array|min:1',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.PI' => 'required|exists:inpi,ID',
            'items.*.PIChild' => 'required|exists:inpichild,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ], [
            'Supplier.required' => 'Please select a supplier.',
            'PINumbers.required' => 'Please select at least one PI.',
            'items.required' => 'At least one item must be included in the GRN.',
            'items.min' => 'At least one item must be included in the GRN.',
        ]);

        $grn = null;

        DB::transaction(function () use ($request, $validated, &$grn) {
            if (empty($validated['GRNNumber'])) {
                $latest = GRN::latest('ID')->first();
                $nextNum = $latest ? ($latest->ID + 1) : 1;
                $year = date('Y');
                $validated['GRNNumber'] = 'GRN-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }

            $piNumbersJson = json_encode(array_values(array_map('intval', $validated['PINumbers'])));

            $grn = GRN::create([
                'GRNNumber' => $validated['GRNNumber'],
                'GRNDate' => $validated['GRNDate'],
                'Supplier' => $validated['Supplier'],
                'PINumbers' => $piNumbersJson,
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($request->input('items', []) as $row) {
                $qty = (float)($row['Quantity'] ?? 0);
                if ($qty <= 0) continue;

                GRNChild::create([
                    'GRN' => $grn->ID,
                    'PI' => $row['PI'],
                    'PIChild' => $row['PIChild'],
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        if ($request->input('action_type') === 'save_and_print') {
            return redirect()->route('store.grn.print', $grn->ID);
        }

        return redirect()->route('store.grn.index')->with('success', "GRN {$grn->GRNNumber} created successfully.");
    }

    public function edit($id)
    {
        $grn = GRN::with(['children.itemMasterRelation', 'children.piChildRelation', 'supplierRelation'])->findOrFail($id);
        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();

        // Selected PIs array
        $selectedPiIds = !empty($grn->PINumbers) ? json_decode($grn->PINumbers, true) : [];

        return view('store.grn.form', compact('grn', 'suppliers', 'selectedPiIds'));
    }

    public function update(Request $request, $id)
    {
        $grn = GRN::findOrFail($id);

        $validated = $request->validate([
            'GRNNumber' => 'required|string|max:100',
            'GRNDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'PINumbers' => 'required|array|min:1',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.PI' => 'required|exists:inpi,ID',
            'items.*.PIChild' => 'required|exists:inpichild,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ], [
            'Supplier.required' => 'Please select a supplier.',
            'PINumbers.required' => 'Please select at least one PI.',
            'items.required' => 'At least one item must be included in the GRN.',
        ]);

        DB::transaction(function () use ($request, $grn, $validated) {
            $piNumbersJson = json_encode(array_values(array_map('intval', $validated['PINumbers'])));

            $grn->update([
                'GRNNumber' => $validated['GRNNumber'],
                'GRNDate' => $validated['GRNDate'],
                'Supplier' => $validated['Supplier'],
                'PINumbers' => $piNumbersJson,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            // Delete existing child entries and recreate
            $grn->children()->delete();

            foreach ($request->input('items', []) as $row) {
                $qty = (float)($row['Quantity'] ?? 0);
                if ($qty <= 0) continue;

                GRNChild::create([
                    'GRN' => $grn->ID,
                    'PI' => $row['PI'],
                    'PIChild' => $row['PIChild'],
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        if ($request->input('action_type') === 'save_and_print') {
            return redirect()->route('store.grn.print', $grn->ID);
        }

        return redirect()->route('store.grn.index')->with('success', 'GRN updated successfully.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $grn = GRN::findOrFail($id);
            $grn->children()->delete();
            $grn->delete();
        });

        return response()->json(['success' => true]);
    }

    public function print($id)
    {
        $grn = GRN::with(['children.itemMasterRelation', 'children.piChildRelation', 'supplierRelation'])->findOrFail($id);
        
        $piIds = !empty($grn->PINumbers) ? json_decode($grn->PINumbers, true) : [];
        $piNumbers = PI::whereIn('ID', $piIds)->pluck('PINumber')->toArray();
        $piNumbersString = implode(', ', $piNumbers);

        return view('store.grn.print', compact('grn', 'piNumbersString'));
    }
}
