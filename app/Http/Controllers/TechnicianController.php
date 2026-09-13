<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Technician;
use Illuminate\Support\Facades\Auth;

class TechnicianController extends Controller
{
    public function index()
    {
        return view('masters.technician.index');
    }

    public function data(Request $request)
    {
        $query = Technician::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('Name', 'like', "%{$search}%")
                  ->orWhere('Code', 'like', "%{$search}%")
                  ->orWhere('Phone', 'like', "%{$search}%");
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'asc');

        $allowedCols = ['ID', 'Name', 'Code', 'Phone', 'IsActive', 'CreatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'asc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function create()
    {
        $technician = new Technician();
        return view('masters.technician.form', compact('technician'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:umtechnician,Name',
            'Code' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:20',
            'IsActive' => 'nullable|boolean',
        ]);

        $tech = Technician::create([
            'Name' => $validated['Name'],
            'Code' => $validated['Code'] ?? null,
            'Phone' => $validated['Phone'] ?? null,
            'IsActive' => $request->has('IsActive') ? ($request->input('IsActive') ? 1 : 0) : 1,
            'CreatedBy' => Auth::id() ?? 1,
            'UpdatedBy' => Auth::id() ?? 1,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Technician added successfully.', 'data' => $tech]);
        }

        return redirect()->route('masters.technician.index')->with('success', 'Technician added successfully.');
    }

    public function edit($id)
    {
        $technician = Technician::findOrFail($id);
        return view('masters.technician.form', compact('technician'));
    }

    public function update(Request $request, $id)
    {
        $technician = Technician::findOrFail($id);

        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:umtechnician,Name,' . $id . ',ID',
            'Code' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:20',
            'IsActive' => 'nullable|boolean',
        ]);

        $technician->update([
            'Name' => $validated['Name'],
            'Code' => $validated['Code'] ?? null,
            'Phone' => $validated['Phone'] ?? null,
            'IsActive' => $request->has('IsActive') ? ($request->input('IsActive') ? 1 : 0) : 1,
            'UpdatedBy' => Auth::id() ?? 1,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Technician updated successfully.', 'data' => $technician]);
        }

        return redirect()->route('masters.technician.index')->with('success', 'Technician updated successfully.');
    }

    public function destroy($id)
    {
        $technician = Technician::findOrFail($id);
        $technician->delete();

        return response()->json(['success' => true, 'message' => 'Technician deleted successfully.']);
    }
}
