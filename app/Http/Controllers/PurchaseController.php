<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InTransaction;
use App\Models\RollSize;
use App\Models\FabricColor;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('inventories.purchase.index');
    }

    public function data(Request $request)
    {
        $query = InTransaction::where('TransactionType', 2)
            ->with(['rollSizeRelation', 'fabricColorRelation']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('RollNumber', 'like', "%{$search}%")
                  ->orWhere('RequiredGramMeter', 'like', "%{$search}%")
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
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = [
            'ID', 'EntryDate', 'RollSize', 'FabricColor', 'Lamination',
            'RequiredGramMeter', 'ActualMeter', 'GrossWeight',
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

        // Transform collection for datatable rendering
        $data->getCollection()->transform(function ($item) {
            $item->RollSizeName = $item->rollSizeRelation ? $item->rollSizeRelation->RollSize : ($item->RollSize ?? '-');
            $item->FabricColorName = $item->fabricColorRelation ? $item->fabricColorRelation->FabricColor : ($item->FabricColor ?? '-');
            $item->LaminationName = ($item->Lamination == 1) ? 'Laminate' : (($item->Lamination === 0) ? 'Unlaminate' : '-');
            $item->EntryDateFormatted = $item->EntryDate ? date('d-m-Y', strtotime($item->EntryDate)) : '-';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $purchase = new InTransaction();
        $purchase->EntryDate = date('Y-m-d');
        
        $rollSizes = RollSize::where('IsActive', 1)->get();
        $fabricColors = FabricColor::where('IsActive', 1)->get();

        return view('inventories.purchase.form', compact('purchase', 'rollSizes', 'fabricColors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|integer',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'Lamination' => 'nullable|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        $validated['TransactionType'] = 2;
        $validated['IsActive'] = 1;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        InTransaction::create($validated);

        return redirect()->route('inventories.purchase.index')->with('success', 'Purchase record created successfully.');
    }

    public function edit($id)
    {
        $purchase = InTransaction::where('TransactionType', 2)->findOrFail($id);
        
        if ($purchase->EntryDate) {
            $purchase->EntryDate = date('Y-m-d', strtotime($purchase->EntryDate));
        }

        $rollSizes = RollSize::all();
        $fabricColors = FabricColor::all();

        return view('inventories.purchase.form', compact('purchase', 'rollSizes', 'fabricColors'));
    }

    public function update(Request $request, $id)
    {
        $purchase = InTransaction::where('TransactionType', 2)->findOrFail($id);

        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|integer',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'Lamination' => 'nullable|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $purchase->update($validated);

        return redirect()->route('inventories.purchase.index')->with('success', 'Purchase record updated successfully.');
    }

    public function destroy($id)
    {
        $purchase = InTransaction::where('TransactionType', 2)->findOrFail($id);
        $purchase->delete();

        return response()->json(['success' => true]);
    }
}
