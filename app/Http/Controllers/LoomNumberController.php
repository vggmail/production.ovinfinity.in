<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoomNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LoomNumberController extends Controller
{
    public static $loomTypes = [
        1 => '610',
        2 => 'LSL-6',
        3 => 'Nova-6',
        4 => 'LSL-8',
    ];

    public function index()
    {
        return view('masters.loomnumber.index');
    }

    public function data(Request $request)
    {
        $query = LoomNumber::query();

        if ($search = $request->input('search')) {
            $query->where('LoomNumber', 'like', "%{$search}%");
            
            // Allow searching by Loom Type name
            foreach (self::$loomTypes as $id => $name) {
                if (stripos($name, $search) !== false) {
                    $query->orWhere('LoomType', $id);
                }
            }
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'LoomNumber', 'LoomType', 'IsActive', 'CreatedOn', 'UpdatedOn'];
        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        // Map LoomType ID to name for JSON response
        $data->getCollection()->transform(function ($item) {
            $item->LoomTypeName = self::$loomTypes[$item->LoomType] ?? 'Unknown';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $loomnumber = new LoomNumber();
        $loomTypes = self::$loomTypes;
        return view('masters.loomnumber.form', compact('loomnumber', 'loomTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'LoomNumber' => ['required', 'string', 'max:50', Rule::unique('umloomnumber', 'LoomNumber')],
            'LoomType' => 'required|integer|in:' . implode(',', array_keys(self::$loomTypes)),
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        LoomNumber::create($validated);

        return redirect()->route('masters.loomnumber.index')->with('success', 'Loom Number created successfully.');
    }

    public function edit($id)
    {
        $loomnumber = LoomNumber::findOrFail($id);
        $loomTypes = self::$loomTypes;
        return view('masters.loomnumber.form', compact('loomnumber', 'loomTypes'));
    }

    public function update(Request $request, $id)
    {
        $loomnumber = LoomNumber::findOrFail($id);

        $validated = $request->validate([
            'LoomNumber' => ['required', 'string', 'max:50', Rule::unique('umloomnumber', 'LoomNumber')->ignore($id, 'ID')],
            'LoomType' => 'required|integer|in:' . implode(',', array_keys(self::$loomTypes)),
            'IsActive' => 'nullable|boolean',
        ]);

        $validated['IsActive'] = $request->has('IsActive') ? 1 : 0;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $loomnumber->update($validated);

        return redirect()->route('masters.loomnumber.index')->with('success', 'Loom Number updated successfully.');
    }

    public function destroy($id)
    {
        $loomnumber = LoomNumber::findOrFail($id);
        $loomnumber->delete();

        return response()->json(['success' => true]);
    }
}
