<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueChild;
use App\Models\Technician;
use App\Models\Department;
use App\Models\LoomNumber;
use App\Models\ItemMaster;
use App\Models\GRNChild;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaterialIssueController extends Controller
{
    public function index()
    {
        return view('store.materialissue.index');
    }

    public function data(Request $request)
    {
        $query = MaterialIssue::with(['technicianRelation']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('IssueNo', 'like', "%{$search}%")
                  ->orWhere('IssueDate', 'like', "%{$search}%")
                  ->orWhere('Remarks', 'like', "%{$search}%")
                  ->orWhereHas('technicianRelation', function ($t) use ($search) {
                      $t->where('Name', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'IssueNo', 'IssueDate', 'TotalItems', 'TotalQuantity', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function create()
    {
        $materialIssue = new MaterialIssue();
        $materialIssue->IssueNo = $this->generateIssueNo();
        $materialIssue->IssueDate = date('Y-m-d');

        $technicians = Technician::where('IsActive', 1)->orderBy('Name', 'asc')->get();
        $departments = Department::where('IsActive', 1)->orderBy('DepartmentName', 'asc')->get();
        $looms = LoomNumber::where('IsActive', 1)->orderBy('LoomNumber', 'asc')->get();
        $items = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        $stockMap = $this->calculateStockMap();

        return view('store.materialissue.form', compact(
            'materialIssue',
            'technicians',
            'departments',
            'looms',
            'items',
            'stockMap'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'IssueNo' => 'required|string|max:50|unique:inmaterialissue,IssueNo',
            'IssueDate' => 'required|date',
            'Technician' => 'nullable|exists:umtechnician,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.LoomNumber' => 'nullable|exists:umloomnumber,ID',
            'items.*.Department' => 'nullable|exists:umdepartment,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ], [
            'IssueNo.required' => 'Issue Number is required.',
            'IssueNo.unique' => 'Issue Number has already been taken.',
            'items.required' => 'At least one item row must be added.',
            'items.min' => 'At least one item row must be added.',
            'items.*.ItemMaster.required' => 'Please select an item for all rows.',
            'items.*.Quantity.required' => 'Please enter a valid quantity.',
        ]);

        DB::transaction(function () use ($validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            $materialIssue = MaterialIssue::create([
                'IssueNo' => $validated['IssueNo'],
                'IssueDate' => $validated['IssueDate'],
                'Technician' => $validated['Technician'] ?? null,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($validated['items'] as $row) {
                MaterialIssueChild::create([
                    'MaterialIssue' => $materialIssue->ID,
                    'LoomNumber' => $row['LoomNumber'] ?? null,
                    'Department' => $row['Department'] ?? null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.materialissue.index')->with('success', 'Material Issue created successfully.');
    }

    public function edit($id)
    {
        $materialIssue = MaterialIssue::with(['children'])->findOrFail($id);

        $technicians = Technician::where('IsActive', 1)->orderBy('Name', 'asc')->get();
        $departments = Department::where('IsActive', 1)->orderBy('DepartmentName', 'asc')->get();
        $looms = LoomNumber::where('IsActive', 1)->orderBy('LoomNumber', 'asc')->get();
        $items = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        $stockMap = $this->calculateStockMap($id);

        return view('store.materialissue.form', compact(
            'materialIssue',
            'technicians',
            'departments',
            'looms',
            'items',
            'stockMap'
        ));
    }

    public function update(Request $request, $id)
    {
        $materialIssue = MaterialIssue::findOrFail($id);

        $validated = $request->validate([
            'IssueNo' => 'required|string|max:50|unique:inmaterialissue,IssueNo,' . $id . ',ID',
            'IssueDate' => 'required|date',
            'Technician' => 'nullable|exists:umtechnician,ID',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.LoomNumber' => 'nullable|exists:umloomnumber,ID',
            'items.*.Department' => 'nullable|exists:umdepartment,ID',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($materialIssue, $validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            $materialIssue->update([
                'IssueNo' => $validated['IssueNo'],
                'IssueDate' => $validated['IssueDate'],
                'Technician' => $validated['Technician'] ?? null,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            $materialIssue->children()->delete();

            foreach ($validated['items'] as $row) {
                MaterialIssueChild::create([
                    'MaterialIssue' => $materialIssue->ID,
                    'LoomNumber' => $row['LoomNumber'] ?? null,
                    'Department' => $row['Department'] ?? null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.materialissue.index')->with('success', 'Material Issue updated successfully.');
    }

    public function destroy($id)
    {
        $materialIssue = MaterialIssue::findOrFail($id);

        DB::transaction(function () use ($materialIssue) {
            $materialIssue->children()->delete();
            $materialIssue->delete();
        });

        return response()->json(['success' => true, 'message' => 'Material Issue deleted successfully.']);
    }

    public function print($id)
    {
        $materialIssue = MaterialIssue::with([
            'technicianRelation',
            'children.loomRelation',
            'children.departmentRelation',
            'children.itemMasterRelation'
        ])->findOrFail($id);

        return view('store.materialissue.print', compact('materialIssue'));
    }

    /**
     * Generate auto Issue No format: issue-2026-00001
     */
    private function generateIssueNo()
    {
        $year = date('Y');
        $prefix = "issue-{$year}-";

        $lastIssue = MaterialIssue::where('IssueNo', 'like', "{$prefix}%")
            ->orderBy('ID', 'desc')
            ->first();

        if ($lastIssue) {
            $lastNum = (int) substr($lastIssue->IssueNo, strlen($prefix));
            $nextNum = str_pad($lastNum + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '00125'; // Matches prompt screenshot sample starting number format
        }

        return $prefix . $nextNum;
    }

    /**
     * Calculate available stock per item:
     * Received (from GRN) - Issued (from Material Issue)
     */
    private function calculateStockMap($excludeIssueId = null)
    {
        $grnReceived = [];
        if (Schema::hasTable('ingrnchild')) {
            $grnReceived = DB::table('ingrnchild')
                ->select('ItemMaster', DB::raw('SUM(Quantity) as total_received'))
                ->groupBy('ItemMaster')
                ->pluck('total_received', 'ItemMaster')
                ->toArray();
        }

        $query = DB::table('inmaterialissuechild')
            ->select('ItemMaster', DB::raw('SUM(Quantity) as total_issued'));

        if ($excludeIssueId) {
            $query->where('MaterialIssue', '!=', $excludeIssueId);
        }

        $materialIssued = $query->groupBy('ItemMaster')
            ->pluck('total_issued', 'ItemMaster')
            ->toArray();

        $stockMap = [];
        $allItems = ItemMaster::pluck('ID');

        foreach ($allItems as $itemId) {
            $received = $grnReceived[$itemId] ?? 0;
            $issued = $materialIssued[$itemId] ?? 0;
            $stockMap[$itemId] = max(0, $received - $issued);
        }

        return $stockMap;
    }
}
