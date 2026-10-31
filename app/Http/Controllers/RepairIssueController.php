<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RepairIssue;
use App\Models\RepairIssueChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepairIssueController extends Controller
{
    public function index()
    {
        return view('repair.issue.index');
    }

    public function data(Request $request)
    {
        $query = RepairIssue::with(['supplierRelation'])->where('IsActive', 1);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('RepairNo', 'like', "%{$search}%")
                  ->orWhere('RepairDate', 'like', "%{$search}%")
                  ->orWhere('Status', 'like', "%{$search}%")
                  ->orWhere('Remarks', 'like', "%{$search}%")
                  ->orWhereHas('supplierRelation', function ($s) use ($search) {
                      $s->where('SupplierName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'RepairNo', 'RepairDate', 'TotalItems', 'TotalQuantity', 'TotalAmount', 'Status', 'CreatedOn'];
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
        $repairIssue = new RepairIssue();
        $repairIssue->RepairNo = $this->generateRepairNo();
        $repairIssue->RepairDate = date('Y-m-d');
        $repairIssue->Status = 'Pending';

        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $items = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        return view('repair.issue.form', compact('repairIssue', 'suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'RepairNo' => 'required|string|max:50|unique:inrepairissue,RepairNo',
            'RepairDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
            'items.*.Rate' => 'required|numeric|min:0',
        ], [
            'RepairNo.required' => 'Repair Issue Number is required.',
            'RepairNo.unique' => 'Repair Issue Number has already been taken.',
            'Supplier.required' => 'Please select a supplier.',
            'items.required' => 'At least one item must be added.',
            'items.*.ItemMaster.required' => 'Please select an item for all rows.',
            'items.*.Quantity.required' => 'Please enter a valid quantity.',
            'items.*.Quantity.min' => 'Quantity must be greater than zero.',
            'items.*.Rate.required' => 'Please enter rate.',
        ]);

        DB::transaction(function () use ($validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $totalQuantity += $qty;
                $totalAmount += ($qty * $rate);
            }

            $repairIssue = RepairIssue::create([
                'RepairNo' => $validated['RepairNo'],
                'RepairDate' => $validated['RepairDate'],
                'Supplier' => $validated['Supplier'],
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAmount' => $totalAmount,
                'Status' => 'Pending',
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $amount = $qty * $rate;

                RepairIssueChild::create([
                    'RepairIssue' => $repairIssue->ID,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'Rate' => $rate,
                    'Amount' => $amount,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('repair.issue.index')->with('success', 'Repair Material Issue created successfully.');
    }

    public function edit($id)
    {
        $repairIssue = RepairIssue::with(['children.itemMasterRelation'])->findOrFail($id);
        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $items = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        return view('repair.issue.form', compact('repairIssue', 'suppliers', 'items'));
    }

    public function update(Request $request, $id)
    {
        $repairIssue = RepairIssue::findOrFail($id);

        $validated = $request->validate([
            'RepairNo' => 'required|string|max:50|unique:inrepairissue,RepairNo,' . $id . ',ID',
            'RepairDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
            'items.*.Rate' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $repairIssue) {
            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $totalQuantity += $qty;
                $totalAmount += ($qty * $rate);
            }

            // Delete old children and insert updated ones
            RepairIssueChild::where('RepairIssue', $repairIssue->ID)->delete();

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $amount = $qty * $rate;

                RepairIssueChild::create([
                    'RepairIssue' => $repairIssue->ID,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $qty,
                    'Rate' => $rate,
                    'Amount' => $amount,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }

            // Check if all items are fully returned (if returns exist)
            $status = $this->calculateIssueStatus($repairIssue->ID);

            $repairIssue->update([
                'RepairNo' => $validated['RepairNo'],
                'RepairDate' => $validated['RepairDate'],
                'Supplier' => $validated['Supplier'],
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAmount' => $totalAmount,
                'Status' => $status,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);
        });

        return redirect()->route('repair.issue.index')->with('success', 'Repair Material Issue updated successfully.');
    }

    public function destroy($id)
    {
        $repairIssue = RepairIssue::findOrFail($id);

        // Prevent delete if return entry exists
        if ($repairIssue->returns()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this Repair Issue because a Repair Return has already been recorded against it.'
            ], 422);
        }

        $repairIssue->update(['IsActive' => 0]);
        $repairIssue->children()->update(['IsActive' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Repair Material Issue deleted successfully.'
        ]);
    }

    private function generateRepairNo()
    {
        $year = date('Y');
        $prefix = 'RPR-' . $year . '-';

        $latest = RepairIssue::where('RepairNo', 'like', "{$prefix}%")
            ->orderBy('ID', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->RepairNo, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    public function calculateIssueStatus($repairIssueId)
    {
        $issue = RepairIssue::with(['children', 'returns.children'])->find($repairIssueId);
        if (!$issue || $issue->children->isEmpty()) {
            return 'Pending';
        }

        // Sum returned quantity per item
        $returnedTotals = [];
        foreach ($issue->returns as $ret) {
            if (!$ret->IsActive) continue;
            foreach ($ret->children as $retChild) {
                $itemId = $retChild->ItemMaster;
                $returnedTotals[$itemId] = ($returnedTotals[$itemId] ?? 0) + (float)$retChild->Quantity;
            }
        }

        foreach ($issue->children as $child) {
            $returnedQty = $returnedTotals[$child->ItemMaster] ?? 0;
            if ($returnedQty < (float)$child->Quantity) {
                return 'Pending';
            }
        }

        return 'Complete';
    }
}
