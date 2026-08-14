<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoomNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LoomNumberController extends Controller
{
    public static $yarnTypes = [
        1 => 'LSL-6',
        2 => 'Airjet',
        3 => 'Rapier',
        4 => 'Waterjet',
        5 => 'Projectile',
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
            foreach (self::$yarnTypes as $id => $name) {
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

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        // Map LoomType ID to name for JSON response
        $data->getCollection()->transform(function ($item) {
            $item->LoomTypeName = self::$yarnTypes[$item->LoomType] ?? 'Unknown';
            return $item;
        });

        return response()->json($data);
    }

    public function create()
    {
        $loomnumber = new LoomNumber();
        $yarnTypes = self::$yarnTypes;
        return view('masters.loomnumber.form', compact('loomnumber', 'yarnTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'LoomNumber' => ['required', 'string', 'max:50', Rule::unique('umloomnumber', 'LoomNumber')],
            'LoomType' => 'required|integer|in:' . implode(',', array_keys(self::$yarnTypes)),
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
        $yarnTypes = self::$yarnTypes;
        return view('masters.loomnumber.form', compact('loomnumber', 'yarnTypes'));
    }

    public function update(Request $request, $id)
    {
        $loomnumber = LoomNumber::findOrFail($id);

        $validated = $request->validate([
            'LoomNumber' => ['required', 'string', 'max:50', Rule::unique('umloomnumber', 'LoomNumber')->ignore($id, 'ID')],
            'LoomType' => 'required|integer|in:' . implode(',', array_keys(self::$yarnTypes)),
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
