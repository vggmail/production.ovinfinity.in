<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InTransaction;
use App\Models\RollSize;
use App\Models\FabricColor;
use App\Models\LoomNumber;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    public function index()
    {
        return view('inventories.production.index');
    }

    public function data(Request $request)
    {
        $query = InTransaction::with(['rollSizeRelation', 'fabricColorRelation', 'loomNumberRelation']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('RollNumber', 'like', "%{$search}%")
                  ->orWhere('RequiredGramMeter', 'like', "%{$search}%")
                  ->orWhere('ClosingMeter', 'like', "%{$search}%")
                  ->orWhere('ActualMeter', 'like', "%{$search}%")
                  ->orWhere('GrossWeight', 'like', "%{$search}%")
                  ->orWhere('CoreWeight', 'like', "%{$search}%")
                  ->orWhere('NetWeight', 'like', "%{$search}%")
                  ->orWhere('ActualMeterWeight', 'like', "%{$search}%")
                  ->orWhere('Variation', 'like', "%{$search}%")
                  ->orWhereHas('rollSizeRelation', function ($rs) use ($search) {
                      $rs->where('RollSize', 'like', "%{$search}%");
                  })
                  ->orWhereHas('fabricColorRelation', function ($fc) use ($search) {
                      $fc->where('FabricColor', 'like', "%{$search}%");
                  })
                  ->orWhereHas('loomNumberRelation', function ($ln) use ($search) {
                      $ln->where('LoomNumber', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = [
            'ID', 'EntryDate', 'RollSize', 'FabricColor', 'LoomNumber',
            'RequiredGramMeter', 'ClosingMeter', 'ActualMeter', 'GrossWeight',
            'CoreWeight', 'NetWeight', 'ActualMeterWeight', 'Variation',
            'CreatedOn', 'UpdatedOn'
        ];

        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        // Transform collection for clean datatable rendering
        $data->getCollection()->transform(function ($item) {
            $item->RollSizeName = $item->rollSizeRelation ? $item->rollSizeRelation->RollSize : ($item->RollSize ?? '-');
            $item->FabricColorName = $item->fabricColorRelation ? $item->fabricColorRelation->FabricColor : ($item->FabricColor ?? '-');
            $item->LoomNumberValue = $item->loomNumberRelation ? $item->loomNumberRelation->LoomNumber : ($item->LoomNumber ?? '-');
            $item->EntryDateFormatted = $item->EntryDate ? date('d-m-Y', strtotime($item->EntryDate)) : '-';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $production = new InTransaction();
        $production->EntryDate = date('Y-m-d');
        
        $rollSizes = RollSize::where('IsActive', 1)->get();
        $fabricColors = FabricColor::where('IsActive', 1)->get();
        $loomNumbers = LoomNumber::where('IsActive', 1)->get();

        return view('inventories.production.form', compact('production', 'rollSizes', 'fabricColors', 'loomNumbers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|integer',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'LoomNumber' => 'required|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'OpeningMeter' => 'nullable|string|max:50',
            'ClosingMeter' => 'nullable|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        $validated['TransactionType'] = 1;
        $validated['IsActive'] = 1;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        InTransaction::create($validated);

        return redirect()->route('inventories.production.index')->with('success', 'Production record created successfully.');
    }

    public function edit($id)
    {
        $production = InTransaction::findOrFail($id);
        
        // Format date for HTML5 date input
        if ($production->EntryDate) {
            $production->EntryDate = date('Y-m-d', strtotime($production->EntryDate));
        }

        $rollSizes = RollSize::all();
        $fabricColors = FabricColor::all();
        $loomNumbers = LoomNumber::all();

        return view('inventories.production.form', compact('production', 'rollSizes', 'fabricColors', 'loomNumbers'));
    }

    public function update(Request $request, $id)
    {
        $production = InTransaction::findOrFail($id);

        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|integer',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'LoomNumber' => 'required|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'OpeningMeter' => 'nullable|string|max:50',
            'ClosingMeter' => 'nullable|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $production->update($validated);

        return redirect()->route('inventories.production.index')->with('success', 'Production record updated successfully.');
    }

    public function destroy($id)
    {
        $production = InTransaction::findOrFail($id);
        $production->delete();

        return response()->json(['success' => true]);
    }
}
