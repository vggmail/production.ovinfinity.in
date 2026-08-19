<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationChild;
use App\Models\MRLEntry;
use App\Models\MRLEntryChild;
use App\Models\Supplier;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index()
    {
        return view('store.quotation.index');
    }

    public function data(Request $request)
    {
        $query = Quotation::with('supplierRelation');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('QuotationNumber', 'like', "%{$search}%")
                  ->orWhere('QuotationDate', 'like', "%{$search}%")
                  ->orWhereHas('supplierRelation', function ($sq) use ($search) {
                      $sq->where('SupplierName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'QuotationNumber', 'QuotationDate', 'TotalItems', 'TotalQuantity', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->SupplierName = $item->supplierRelation->SupplierName ?? 'N/A';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $quotation = new Quotation();
        $quotation->QuotationDate = date('Y-m-d');
        
        // Generate auto Quotation Number e.g. QT-20260815-0001
        $latest = Quotation::latest('ID')->first();
        $nextNum = $latest ? ($latest->ID + 1) : 1;
        $quotation->QuotationNumber = 'QT-' . date('Ymd') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $itemList = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        return view('store.quotation.form', compact('quotation', 'suppliers', 'itemList'));
    }

    public function fetchMrlItems(Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (!$fromDate || !$toDate) {
            return response()->json(['success' => false, 'message' => 'Please select both From Date and To Date.'], 400);
        }

        $mrlEntries = MRLEntry::with(['children.itemMasterRelation'])
            ->whereBetween('EntryDate', [$fromDate, $toDate])
            ->orderBy('EntryDate', 'asc')
            ->get();

        $mrlItems = [];
        foreach ($mrlEntries as $mrl) {
            $entryDateFormatted = is_string($mrl->EntryDate) ? substr($mrl->EntryDate, 0, 10) : $mrl->EntryDate->format('Y-m-d');
            foreach ($mrl->children as $child) {
                $itemMaster = $child->itemMasterRelation;
                $mrlItems[] = [
                    'mrl_entry_id' => $mrl->ID,
                    'mrl_child_id' => $child->ID,
                    'entry_date' => $entryDateFormatted,
                    'item_id' => $child->ItemMaster,
                    'item_name' => $itemMaster->ItemName ?? 'Unknown Item',
                    'part_no' => $itemMaster->PartNo ?? '',
                    'catalogue_no' => $itemMaster->CatalogueNo ?? '',
                    'quantity' => $child->Quantity,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $mrlItems,
            'count' => count($mrlItems)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'QuotationNumber' => 'nullable|string|max:100',
            'QuotationDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'FromDate' => 'nullable|date',
            'ToDate' => 'nullable|date',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
            'items.*.MRLEntryChild' => 'nullable|integer',
        ], [
            'items.required' => 'At least one item must be included in the quotation.',
            'items.min' => 'At least one item must be included in the quotation.',
            'Supplier.required' => 'Please select a supplier.',
        ]);

        DB::transaction(function () use ($validated, &$quotation) {
            // Auto generate QuotationNumber if empty
            if (empty($validated['QuotationNumber'])) {
                $latest = Quotation::latest('ID')->first();
                $nextNum = $latest ? ($latest->ID + 1) : 1;
                $validated['QuotationNumber'] = 'QT-' . date('Ymd') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }

            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            $quotation = Quotation::create([
                'QuotationNumber' => $validated['QuotationNumber'],
                'QuotationDate' => $validated['QuotationDate'],
                'Supplier' => $validated['Supplier'],
                'FromDate' => $validated['FromDate'] ?? null,
                'ToDate' => $validated['ToDate'] ?? null,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'Remarks' => $validated['Remarks'] ?? null,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($validated['items'] as $row) {
                QuotationChild::create([
                    'Quotation' => $quotation->ID,
                    'MRLEntryChild' => !empty($row['MRLEntryChild']) ? $row['MRLEntryChild'] : null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.quotation.index')->with('success', "Quotation {$quotation->QuotationNumber} created successfully.");
    }

    public function edit($id)
    {
        $quotation = Quotation::with(['children.itemMasterRelation', 'supplierRelation'])->findOrFail($id);
        $suppliers = Supplier::where('IsActive', 1)->orderBy('SupplierName', 'asc')->get();
        $itemList = ItemMaster::where('IsActive', 1)->orderBy('ItemName', 'asc')->get();

        return view('store.quotation.form', compact('quotation', 'suppliers', 'itemList'));
    }

    public function update(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);

        $validated = $request->validate([
            'QuotationNumber' => 'required|string|max:100',
            'QuotationDate' => 'required|date',
            'Supplier' => 'required|exists:umsupplier,ID',
            'FromDate' => 'nullable|date',
            'ToDate' => 'nullable|date',
            'Remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
            'items.*.MRLEntryChild' => 'nullable|integer',
        ], [
            'items.required' => 'At least one item must be included in the quotation.',
            'items.min' => 'At least one item must be included in the quotation.',
            'Supplier.required' => 'Please select a supplier.',
        ]);

        DB::transaction(function () use ($quotation, $validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            $quotation->update([
                'QuotationNumber' => $validated['QuotationNumber'],
                'QuotationDate' => $validated['QuotationDate'],
                'Supplier' => $validated['Supplier'],
                'FromDate' => $validated['FromDate'] ?? null,
                'ToDate' => $validated['ToDate'] ?? null,
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'Remarks' => $validated['Remarks'] ?? null,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            $quotation->children()->delete();

            foreach ($validated['items'] as $row) {
                QuotationChild::create([
                    'Quotation' => $quotation->ID,
                    'MRLEntryChild' => !empty($row['MRLEntryChild']) ? $row['MRLEntryChild'] : null,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.quotation.index')->with('success', 'Quotation updated successfully.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $quotation = Quotation::findOrFail($id);
            $quotation->children()->delete();
            $quotation->delete();
        });

        return response()->json(['success' => true]);
    }

    public function print($id)
    {
        $quotation = Quotation::with(['children.itemMasterRelation', 'supplierRelation'])->findOrFail($id);
        return view('store.quotation.print', compact('quotation'));
    }
}
