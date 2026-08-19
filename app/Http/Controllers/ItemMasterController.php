<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Auth;

class ItemMasterController extends Controller
{
    public function index()
    {
        return view('store.itemmaster.index');
    }

    public function data(Request $request)
    {
        $query = ItemMaster::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ItemName', 'like', "%{$search}%")
                  ->orWhere('PartNo', 'like', "%{$search}%")
                  ->orWhere('CatalogueNo', 'like', "%{$search}%")
                  ->orWhere('HSNNo', 'like', "%{$search}%");
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'ItemName', 'PartNo', 'CatalogueNo', 'MinQuantity', 'HSNNo', 'GSTPercentage', 'IsActive', 'CreatedOn', 'UpdatedOn'];
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
        $item = new ItemMaster();
        return view('store.itemmaster.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ItemName' => 'required|string|max:255',
            'PartNo' => 'nullable|string|max:100',
            'CatalogueNo' => 'nullable|string|max:100',
            'MinQuantity' => 'nullable|numeric|min:0',
            'HSNNo' => 'nullable|string|max:50',
            'GSTPercentage' => 'nullable|numeric|min:0|max:100',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['MinQuantity'] = $validated['MinQuantity'] ?? 0;
        $validated['GSTPercentage'] = $validated['GSTPercentage'] ?? 0;
        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        ItemMaster::create($validated);

        return redirect()->route('store.itemmaster.index')->with('success', 'Item created successfully.');
    }

    public function edit($id)
    {
        $item = ItemMaster::findOrFail($id);
        return view('store.itemmaster.form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ItemMaster::findOrFail($id);

        $validated = $request->validate([
            'ItemName' => 'required|string|max:255',
            'PartNo' => 'nullable|string|max:100',
            'CatalogueNo' => 'nullable|string|max:100',
            'MinQuantity' => 'nullable|numeric|min:0',
            'HSNNo' => 'nullable|string|max:50',
            'GSTPercentage' => 'nullable|numeric|min:0|max:100',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['MinQuantity'] = $validated['MinQuantity'] ?? 0;
        $validated['GSTPercentage'] = $validated['GSTPercentage'] ?? 0;
        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $item->update($validated);

        return redirect()->route('store.itemmaster.index')->with('success', 'Item updated successfully.');
    }

    public function destroy($id)
    {
        $item = ItemMaster::findOrFail($id);
        $item->delete();

        return response()->json(['success' => true]);
    }
}
