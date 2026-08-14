<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        return view('masters.supplier.index');
    }

    public function data(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('SupplierName', 'like', "%{$search}%")
                  ->orWhere('GSTIN', 'like', "%{$search}%")
                  ->orWhere('ContactNo', 'like', "%{$search}%")
                  ->orWhere('Address', 'like', "%{$search}%")
                  ->orWhere('Street', 'like', "%{$search}%")
                  ->orWhere('City', 'like', "%{$search}%")
                  ->orWhere('District', 'like', "%{$search}%")
                  ->orWhere('State', 'like', "%{$search}%")
                  ->orWhere('PinCode', 'like', "%{$search}%");
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'SupplierName', 'GSTIN', 'ContactNo', 'Address', 'Street', 'City', 'District', 'State', 'PinCode', 'CreatedOn', 'UpdatedOn'];
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
        $supplier = new Supplier();
        return view('masters.supplier.form', compact('supplier'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'SupplierName' => ['required', 'string', 'max:50', Rule::unique('umsupplier', 'SupplierName')],
            'GSTIN' => 'required|string|max:50',
            'ContactNo' => 'required|string|max:50',
            'Address' => 'required|string|max:50',
            'Street' => 'required|string|max:50',
            'City' => 'required|string|max:50',
            'District' => 'required|string|max:50',
            'State' => 'required|string|max:50',
            'PinCode' => 'required|string|max:50',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        Supplier::create($validated);

        return redirect()->route('masters.supplier.index')->with('success', 'Supplier created successfully.');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('masters.supplier.form', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'SupplierName' => ['required', 'string', 'max:50', Rule::unique('umsupplier', 'SupplierName')->ignore($id, 'ID')],
            'GSTIN' => 'required|string|max:50',
            'ContactNo' => 'required|string|max:50',
            'Address' => 'required|string|max:50',
            'Street' => 'required|string|max:50',
            'City' => 'required|string|max:50',
            'District' => 'required|string|max:50',
            'State' => 'required|string|max:50',
            'PinCode' => 'required|string|max:50',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $supplier->update($validated);

        return redirect()->route('masters.supplier.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json(['success' => true]);
    }
}
