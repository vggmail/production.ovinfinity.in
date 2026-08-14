<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Party;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function index()
    {
        return view('masters.party.index');
    }

    public function data(Request $request)
    {
        $query = Party::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('PartyName', 'like', "%{$search}%")
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

        $allowedCols = ['ID', 'PartyName', 'GSTIN', 'ContactNo', 'Address', 'Street', 'City', 'District', 'State', 'PinCode', 'CreatedOn', 'UpdatedOn'];
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
        $party = new Party();
        return view('masters.party.form', compact('party'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'PartyName' => ['required', 'string', 'max:50', Rule::unique('umparty', 'PartyName')],
            'GSTIN' => 'nullable|string|max:50',
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

        Party::create($validated);

        return redirect()->route('masters.party.index')->with('success', 'Party created successfully.');
    }

    public function edit($id)
    {
        $party = Party::findOrFail($id);
        return view('masters.party.form', compact('party'));
    }

    public function update(Request $request, $id)
    {
        $party = Party::findOrFail($id);

        $validated = $request->validate([
            'PartyName' => ['required', 'string', 'max:50', Rule::unique('umparty', 'PartyName')->ignore($id, 'ID')],
            'GSTIN' => 'nullable|string|max:50',
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

        $party->update($validated);

        return redirect()->route('masters.party.index')->with('success', 'Party updated successfully.');
    }

    public function destroy($id)
    {
        $party = Party::findOrFail($id);
        $party->delete();

        return response()->json(['success' => true]);
    }
}
