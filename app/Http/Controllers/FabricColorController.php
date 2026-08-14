<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FabricColor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FabricColorController extends Controller
{
    public function index()
    {
        return view('masters.fabriccolor.index');
    }

    public function data(Request $request)
    {
        $query = FabricColor::query();

        if ($search = $request->input('search')) {
            $query->where('FabricColor', 'like', "%{$search}%");
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'FabricColor', 'IsActive', 'CreatedOn', 'UpdatedOn'];
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
        $fabriccolor = new FabricColor();
        return view('masters.fabriccolor.form', compact('fabriccolor'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'FabricColor' => ['required', 'string', 'max:50', Rule::unique('umfabriccolor', 'FabricColor')],
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        FabricColor::create($validated);

        return redirect()->route('masters.fabriccolor.index')->with('success', 'Fabric Color created successfully.');
    }

    public function edit($id)
    {
        $fabriccolor = FabricColor::findOrFail($id);
        return view('masters.fabriccolor.form', compact('fabriccolor'));
    }

    public function update(Request $request, $id)
    {
        $fabriccolor = FabricColor::findOrFail($id);

        $validated = $request->validate([
            'FabricColor' => ['required', 'string', 'max:50', Rule::unique('umfabriccolor', 'FabricColor')->ignore($id, 'ID')],
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $fabriccolor->update($validated);

        return redirect()->route('masters.fabriccolor.index')->with('success', 'Fabric Color updated successfully.');
    }

    public function destroy($id)
    {
        $fabriccolor = FabricColor::findOrFail($id);
        $fabriccolor->delete();

        return response()->json(['success' => true]);
    }
}
