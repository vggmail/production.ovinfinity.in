<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MRLEntry;
use App\Models\MRLEntryChild;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MRLEntryController extends Controller
{
    public function index()
    {
        return view('store.mrlentry.index');
    }

    public function data(Request $request)
    {
        $query = MRLEntry::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('EntryDate', 'like', "%{$search}%");
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'EntryDate', 'TotalItems', 'TotalQuantity', 'IsActive', 'CreatedOn', 'UpdatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function create()
    {
        $mrl = new MRLEntry();
        $mrl->EntryDate = date('Y-m-d');
        $itemList = ItemMaster::where('IsActive', 1)
            ->select('ID', 'ItemName', 'PartNo', 'CatalogueNo')
            ->orderBy('ItemName', 'asc')
            ->get();
        return view('store.mrlentry.form', compact('mrl', 'itemList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ], [
            'items.required' => 'At least one item row must be added.',
            'items.min' => 'At least one item row must be added.',
            'items.*.ItemMaster.required' => 'Please select an item for all rows.',
            'items.*.Quantity.required' => 'Please enter a valid quantity.',
        ]);

        // Validate uniqueness: Each item can only have one entry per MRL
        $itemIds = array_column($validated['items'], 'ItemMaster');
        if (count($itemIds) !== count(array_unique($itemIds))) {
            return back()->withInput()->withErrors(['items' => 'Duplicate items are not allowed. One item should have only one entry in an MRL.']);
        }

        DB::transaction(function () use ($validated, $request) {
            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            $mrl = MRLEntry::create([
                'EntryDate' => $validated['EntryDate'],
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'IsActive' => 1,
                'CreatedBy' => Auth::id() ?? 1,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            foreach ($validated['items'] as $row) {
                MRLEntryChild::create([
                    'MRLEntry' => $mrl->ID,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.mrlentry.index')->with('success', 'MRL Entry created successfully.');
    }

    public function edit($id)
    {
        $mrl = MRLEntry::with(['children.itemMasterRelation'])->findOrFail($id);
        $itemList = ItemMaster::where('IsActive', 1)
            ->select('ID', 'ItemName', 'PartNo', 'CatalogueNo')
            ->orderBy('ItemName', 'asc')
            ->get();

        // Check if quoted
        $isQuoted = false;
        if (Schema::hasTable('inquotationchild')) {
            $childIds = $mrl->children->pluck('ID')->toArray();
            $isQuoted = DB::table('inquotationchild')->whereIn('MRLEntryChild', $childIds)->exists();
        }

        return view('store.mrlentry.form', compact('mrl', 'itemList', 'isQuoted'));
    }

    public function update(Request $request, $id)
    {
        $mrl = MRLEntry::findOrFail($id);

        // Check if locked due to existing quotation
        if (Schema::hasTable('inquotationchild')) {
            $childIds = $mrl->children()->pluck('ID')->toArray();
            $isQuoted = DB::table('inquotationchild')->whereIn('MRLEntryChild', $childIds)->exists();
            if ($isQuoted) {
                return back()->withInput()->withErrors(['items' => 'This MRL Entry has already been used in a Vendor Quotation and cannot be modified.']);
            }
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.ItemMaster' => 'required|exists:umitemmaster,ID',
            'items.*.Quantity' => 'required|numeric|min:0.01',
        ], [
            'items.required' => 'At least one item row must be added.',
            'items.min' => 'At least one item row must be added.',
            'items.*.ItemMaster.required' => 'Please select an item for all rows.',
            'items.*.Quantity.required' => 'Please enter a valid quantity.',
        ]);

        // Validate uniqueness: Each item can only have one entry per MRL
        $itemIds = array_column($validated['items'], 'ItemMaster');
        if (count($itemIds) !== count(array_unique($itemIds))) {
            return back()->withInput()->withErrors(['items' => 'Duplicate items are not allowed. One item should have only one entry in an MRL.']);
        }

        DB::transaction(function () use ($mrl, $validated) {
            $totalItems = count($validated['items']);
            $totalQuantity = array_sum(array_column($validated['items'], 'Quantity'));

            // EntryDate remains non-editable on update
            $mrl->update([
                'TotalItems' => $totalItems,
                'TotalQuantity' => $totalQuantity,
                'UpdatedBy' => Auth::id() ?? 1,
            ]);

            $mrl->children()->delete();

            foreach ($validated['items'] as $row) {
                MRLEntryChild::create([
                    'MRLEntry' => $mrl->ID,
                    'ItemMaster' => $row['ItemMaster'],
                    'Quantity' => $row['Quantity'],
                    'IsActive' => 1,
                    'CreatedBy' => Auth::id() ?? 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                ]);
            }
        });

        return redirect()->route('store.mrlentry.index')->with('success', 'MRL Entry updated successfully.');
    }

    public function destroy($id)
    {
        $mrl = MRLEntry::findOrFail($id);

        if (Schema::hasTable('inquotationchild')) {
            $childIds = $mrl->children()->pluck('ID')->toArray();
            $isQuoted = DB::table('inquotationchild')->whereIn('MRLEntryChild', $childIds)->exists();
            if ($isQuoted) {
                return response()->json(['success' => false, 'message' => 'This MRL Entry is used in a Vendor Quotation and cannot be deleted.'], 422);
            }
        }

        DB::transaction(function () use ($mrl) {
            $mrl->children()->delete();
            $mrl->delete();
        });

        return response()->json(['success' => true]);
    }
}
