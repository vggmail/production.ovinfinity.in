<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RollSize;
use Illuminate\Support\Facades\Auth;

class RollSizeController extends Controller
{
    public function index()
    {
        return view('masters.rollsize.index');
    }

    public function data(Request $request)
    {
        $query = RollSize::query();

        if ($search = $request->input('search')) {
            $query->where('RollSize', 'like', "%{$search}%");
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'RollSize', 'IsActive', 'CreatedOn', 'UpdatedOn'];
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
        $rollsize = new RollSize();
        return view('masters.rollsize.form', compact('rollsize'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'RollSize' => 'required|string|max:50',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        RollSize::create($validated);

        return redirect()->route('masters.rollsize.index')->with('success', 'Roll Size created successfully.');
    }

    public function edit($id)
    {
        $rollsize = RollSize::findOrFail($id);
        return view('masters.rollsize.form', compact('rollsize'));
    }

    public function update(Request $request, $id)
    {
        $rollsize = RollSize::findOrFail($id);

        $validated = $request->validate([
            'RollSize' => 'required|string|max:50',
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $rollsize->update($validated);

        return redirect()->route('masters.rollsize.index')->with('success', 'Roll Size updated successfully.');
    }

    public function destroy($id)
    {
        $rollsize = RollSize::findOrFail($id);
        $rollsize->delete();

        return response()->json(['success' => true]);
    }
}
