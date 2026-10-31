<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RepairReturn;
use App\Models\RepairReturnChild;
use App\Models\RepairIssue;
use App\Models\RepairIssueChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepairReturnController extends Controller
{
    public function index()
    {
        return view('repair.return.index');
    }

    public function data(Request $request)
    {
        $query = RepairReturn::with(['supplierRelation', 'repairIssueRelation'])
            ->where('IsActive', 1);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ReturnNo', 'like', "%{$search}%")
                  ->orWhere('ReturnDate', 'like', "%{$search}%")
                  ->orWhere('Status', 'like', "%{$search}%")
                  ->orWhere('Remarks', 'like', "%{$search}%")
                  ->orWhereHas('repairIssueRelation', function ($r) use ($search) {
                      $r->where('RepairNo', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplierRelation', function ($s) use ($search) {
                      $s->where('SupplierName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'ReturnNo', 'ReturnDate', 'TotalItems', 'TotalQuantity', 'TotalAmount', 'Status', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->SupplierName = $item->supplierRelation->SupplierName ?? 'N/A';
            $item->RepairNo = $item->repairIssueRelation->RepairNo ?? 'N/A';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $repairReturn = new RepairReturn();
        $repairReturn->ReturnNo = $this->generateReturnNo();
        $repairReturn->ReturnDate = date('Y-m-d');
        $repairReturn->Status = 'Pending';

        // Requirement: Dropdown shows only Repair Issues that do NOT have any active return entry yet
        $repairs = RepairIssue::with('supplierRelation')
            ->where('IsActive', 1)
            ->whereDoesntHave('returns', function ($q) {
                $q->where('IsActive', 1);
            })
            ->orderBy('ID', 'desc')
            ->get();

        return view('repair.return.form', compact('repairReturn', 'repairs'));
    }

    public function fetchRepairDetails(Request $request)
    {
        $repairId = $request->input('repair_id');
        if (empty($repairId)) {
            return response()->json(['success' => false, 'message' => 'No Repair Issue selected.']);
        }

        $repair = RepairIssue::with(['children.itemMasterRelation', 'supplierRelation'])
            ->where('IsActive', 1)
            ->find($repairId);

        if (!$repair) {
            return response()->json(['success' => false, 'message' => 'Repair Issue not found.']);
        }

        $items = [];
        foreach ($repair->children as $child) {
            $items[] = [
                'repair_child_id' => $child->ID,
                'item_id' => $child->ItemMaster,
                'item_name' => $child->itemMasterRelation->ItemName ?? 'Item #' . $child->ItemMaster,
                'part_no' => $child->itemMasterRelation->PartNo ?? '',
                'issued_qty' => (float)$child->Quantity,
                'rate' => (float)$child->Rate,
                'amount' => (float)$child->Amount,
                // Default return qty is issued qty (user can set 0 if pending)
                'return_qty' => (float)$child->Quantity,
            ];
        }

        return response()->json([
            'success' => true,
            'repair' => [
                'id' => $repair->ID,
                'repair_no' => $repair->RepairNo,
                'repair_date' => $repair->RepairDate ? $repair->RepairDate->format('Y-m-d') : '',
                'supplier_id' => $repair->Supplier,
                'supplier_name' => $repair->supplierRelation->SupplierName ?? 'N/A',
                'remarks' => $repair->Remarks,
            ],
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ReturnNo' => 'required|string|max:50|unique:inrepairreturn,ReturnNo',
            'ReturnDate' => 'required|date',
            'RepairIssue' => 'required|exists:inrepairissue,ID',
            'Supplier' => 'required|exists:umsupplier,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.RepairIssueChild' => 'nullable|exists:inrepairissuechild,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.IssuedQuantity' => 'required|numeric|min:0',
            'items.*.Quantity' => 'required|numeric|min:0',
            'items.*.Rate' => 'required|numeric|min:0',
        ], [
            'ReturnNo.required' => 'Return Number is required.',
            'ReturnNo.unique' => 'Return Number has already been taken.',
            'RepairIssue.required' => 'Please select a Repair Issue.',
            'items.required' => 'Items are required from the selected Repair Issue.',
            'items.*.Quantity.min' => 'Return quantity cannot be negative.',
        ]);

        // Check if a return already exists for this repair issue
        $existingReturn = RepairReturn::where('RepairIssue', $validated['RepairIssue'])
            ->where('IsActive', 1)
            ->first();

        if ($existingReturn) {
            return back()->withInput()->withErrors([
                'RepairIssue' => 'A Repair Return has already been recorded for this Repair Issue.'
            ]);
        }

        // Validate that return quantity cannot exceed issued quantity
        foreach ($validated['items'] as $index => $row) {
            $issued = (float)$row['IssuedQuantity'];
            $returned = (float)$row['Quantity'];
            if ($returned > $issued) {
                return back()->withInput()->withErrors([
                    'items' => "Return quantity ({$returned}) cannot exceed issued quantity ({$issued}) for item row #" . ($index + 1) . "."
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAmount = 0;
            $allReceived = true;

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $issued = (float)$row['IssuedQuantity'];
                $rate = (float)$row['Rate'];

                $totalQuantity += $qty;
                $totalAmount += ($qty * $rate);

                // If returned quantity is less than issued quantity, then it's not fully received
                if ($qty < $issued) {
                    $allReceived = false;
                }
            }

            // Return status: 'Received' if all items match issued qty; 'Pending' if some items are pending (e.g. qty 0)
            $returnStatus = $allReceived ? 'Received' : 'Pending';

            // Create Return Header
            $repairReturn = RepairReturn::create([
                'ReturnNo' => $validated['ReturnNo'],
                'ReturnDate' => $validated['ReturnDate'],
                'RepairIssue' => $validated['RepairIssue'],
                'Supplier' => $validated['Supplier'],
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAmount' => $totalAmount,
                'Status' => $returnStatus,
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            // Create Return Children
            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $amount = $qty * $rate;

                RepairReturnChild::create([
                    'RepairReturn' => $repairReturn->ID,
                    'RepairIssueChild' => $row['RepairIssueChild'] ?? null,
                    'ItemMaster' => $row['ItemMaster'],
                    'IssuedQuantity' => (float)$row['IssuedQuantity'],
                    'Quantity' => $qty,
                    'Rate' => $rate,
                    'Amount' => $amount,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }

            // Update Repair Issue Status
            // 'Complete' if all items are fully returned, otherwise 'Pending'
            $issueStatus = $allReceived ? 'Complete' : 'Pending';
            RepairIssue::where('ID', $validated['RepairIssue'])->update([
                'Status' => $issueStatus,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);
        });

        return redirect()->route('repair.return.index')->with('success', 'Repair Material Return created successfully.');
    }

    public function edit($id)
    {
        $repairReturn = RepairReturn::with(['children.itemMasterRelation', 'repairIssueRelation.children.itemMasterRelation', 'supplierRelation'])->findOrFail($id);

        // Include current repair issue + any repairs that don't have return
        $repairs = RepairIssue::with('supplierRelation')
            ->where('IsActive', 1)
            ->where(function ($query) use ($repairReturn) {
                $query->where('ID', $repairReturn->RepairIssue)
                      ->orWhereDoesntHave('returns', function ($q) {
                          $q->where('IsActive', 1);
                      });
            })
            ->orderBy('ID', 'desc')
            ->get();

        return view('repair.return.form', compact('repairReturn', 'repairs'));
    }

    public function update(Request $request, $id)
    {
        $repairReturn = RepairReturn::findOrFail($id);

        $validated = $request->validate([
            'ReturnNo' => 'required|string|max:50|unique:inrepairreturn,ReturnNo,' . $id . ',ID',
            'ReturnDate' => 'required|date',
            'RepairIssue' => 'required|exists:inrepairissue,ID',
            'Supplier' => 'required|exists:umsupplier,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.RepairIssueChild' => 'nullable|exists:inrepairissuechild,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.IssuedQuantity' => 'required|numeric|min:0',
            'items.*.Quantity' => 'required|numeric|min:0',
            'items.*.Rate' => 'required|numeric|min:0',
        ]);

        // Validate that return quantity cannot exceed issued quantity
        foreach ($validated['items'] as $index => $row) {
            $issued = (float)$row['IssuedQuantity'];
            $returned = (float)$row['Quantity'];
            if ($returned > $issued) {
                return back()->withInput()->withErrors([
                    'items' => "Return quantity ({$returned}) cannot exceed issued quantity ({$issued}) for item row #" . ($index + 1) . "."
                ]);
            }
        }

        DB::transaction(function () use ($validated, $repairReturn) {
            $totalItems = count($validated['items']);
            $totalQuantity = 0;
            $totalAmount = 0;
            $allReceived = true;

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $issued = (float)$row['IssuedQuantity'];
                $rate = (float)$row['Rate'];

                $totalQuantity += $qty;
                $totalAmount += ($qty * $rate);

                if ($qty < $issued) {
                    $allReceived = false;
                }
            }

            $returnStatus = $allReceived ? 'Received' : 'Pending';

            // Delete old children and insert updated ones
            RepairReturnChild::where('RepairReturn', $repairReturn->ID)->delete();

            foreach ($validated['items'] as $row) {
                $qty = (float)$row['Quantity'];
                $rate = (float)$row['Rate'];
                $amount = $qty * $rate;

                RepairReturnChild::create([
                    'RepairReturn' => $repairReturn->ID,
                    'RepairIssueChild' => $row['RepairIssueChild'] ?? null,
                    'ItemMaster' => $row['ItemMaster'],
                    'IssuedQuantity' => (float)$row['IssuedQuantity'],
                    'Quantity' => $qty,
                    'Rate' => $rate,
                    'Amount' => $amount,
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }

            $repairReturn->update([
                'ReturnNo' => $validated['ReturnNo'],
                'ReturnDate' => $validated['ReturnDate'],
                'Supplier' => $validated['Supplier'],
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'TotalAmount' => $totalAmount,
                'Status' => $returnStatus,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            // Update Repair Issue Status
            $issueStatus = $allReceived ? 'Complete' : 'Pending';
            RepairIssue::where('ID', $validated['RepairIssue'])->update([
                'Status' => $issueStatus,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);
        });

        return redirect()->route('repair.return.index')->with('success', 'Repair Material Return updated successfully.');
    }

    public function destroy($id)
    {
        $repairReturn = RepairReturn::findOrFail($id);
        $repairIssueId = $repairReturn->RepairIssue;

        $repairReturn->update(['IsActive' => 0]);
        $repairReturn->children()->update(['IsActive' => 0]);

        // Revert Repair Issue Status back to Pending
        RepairIssue::where('ID', $repairIssueId)->update([
            'Status' => 'Pending',
            'UpdatedBy' => Auth::id() ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Repair Material Return deleted successfully.'
        ]);
    }

    private function generateReturnNo()
    {
        $year = date('Y');
        $prefix = 'RET-' . $year . '-';

        $latest = RepairReturn::where('ReturnNo', 'like', "{$prefix}%")
            ->orderBy('ID', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->ReturnNo, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
