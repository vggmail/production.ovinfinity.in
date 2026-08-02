<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InDispatch;
use App\Models\InDispatchChild;
use App\Models\InTransaction;
use App\Models\Party;
use App\Models\RollSize;
use App\Models\FabricColor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function index()
    {
        return view('inventories.dispatch.index');
    }

    public function data(Request $request)
    {
        $query = InDispatch::with('partyRelation')->where('IsActive', 1);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('EntryDate', 'like', "%{$search}%")
                  ->orWhere('InvoiceNumber', 'like', "%{$search}%")
                  ->orWhere('TotalRolls', 'like', "%{$search}%")
                  ->orWhereHas('partyRelation', function ($pr) use ($search) {
                      $pr->where('PartyName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'EntryDate', 'PartyName', 'InvoiceNumber', 'TotalRolls', 'CreatedOn', 'UpdatedOn'];

        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->PartyNameValue = $item->partyRelation ? $item->partyRelation->PartyName : ($item->PartyName ?? '-');
            $item->EntryDateFormatted = $item->EntryDate ? date('n/j/Y g:i:s A', strtotime($item->EntryDate)) : '-';
            $item->CreatedOnFormatted = $item->CreatedOn ? date('d-m-Y', strtotime($item->CreatedOn)) : '-';
            $item->UpdatedOnFormatted = $item->UpdatedOn ? date('d-m-Y', strtotime($item->UpdatedOn)) : '-';
            return $item;
        });

        return response()->json($data);
    }

    public function getOptions(Request $request)
    {
        $step = $request->input('step');
        $sourceType = $request->input('source_type');
        $rollSize = $request->input('roll_size');
        $rgm = $request->input('rgm');
        $fabricColor = $request->input('fabric_color');

        if (!$sourceType) {
            return response()->json([]);
        }

        switch ($step) {
            case 'roll_size':
                $ids = InTransaction::where('TransactionType', $sourceType)
                    ->where('IsActive', 1)
                    ->whereNotNull('RollSize')
                    ->distinct()
                    ->pluck('RollSize');

                $list = RollSize::whereIn('ID', $ids)->orderBy('RollSize', 'asc')->get(['ID', 'RollSize']);
                return response()->json($list);

            case 'rgm':
                if (!$rollSize) {
                    return response()->json([]);
                }
                $rgms = InTransaction::where('TransactionType', $sourceType)
                    ->where('RollSize', $rollSize)
                    ->where('IsActive', 1)
                    ->whereNotNull('RequiredGramMeter')
                    ->distinct()
                    ->orderBy('RequiredGramMeter', 'asc')
                    ->pluck('RequiredGramMeter');

                return response()->json($rgms);

            case 'fabric_color':
                if (!$rollSize || !$rgm) {
                    return response()->json([]);
                }
                $colorIds = InTransaction::where('TransactionType', $sourceType)
                    ->where('RollSize', $rollSize)
                    ->where('RequiredGramMeter', $rgm)
                    ->where('IsActive', 1)
                    ->whereNotNull('FabricColor')
                    ->distinct()
                    ->pluck('FabricColor');

                $colors = FabricColor::whereIn('ID', $colorIds)->orderBy('FabricColor', 'asc')->get(['ID', 'FabricColor']);
                return response()->json($colors);

            case 'roll_number':
                if (!$rollSize || !$rgm || !$fabricColor) {
                    return response()->json([]);
                }
                $rolls = InTransaction::where('TransactionType', $sourceType)
                    ->where('RollSize', $rollSize)
                    ->where('RequiredGramMeter', $rgm)
                    ->where('FabricColor', $fabricColor)
                    ->where('IsActive', 1)
                    ->whereNotNull('RollNumber')
                    ->distinct()
                    ->orderBy('RollNumber', 'asc')
                    ->pluck('RollNumber');

                return response()->json($rolls);

            default:
                return response()->json([]);
        }
    }

    public function create()
    {
        $dispatch = new InDispatch();
        $dispatch->EntryDate = date('Y-m-d');
        $dispatch->children = collect();

        $parties = Party::where('IsActive', 1)->get();

        return view('inventories.dispatch.form', compact('dispatch', 'parties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'PartyName' => 'required|integer',
            'InvoiceNumber' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.SourceType' => 'required|integer|in:1,2',
            'items.*.RollSize' => 'required|integer',
            'items.*.RequiredGramMeter' => 'required|string|max:50',
            'items.*.FabricColor' => 'required|integer',
            'items.*.RollNumber' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated) {
            $userId = Auth::id() ?? 1;

            $dispatch = InDispatch::create([
                'EntryDate' => $validated['EntryDate'],
                'PartyName' => $validated['PartyName'],
                'InvoiceNumber' => $validated['InvoiceNumber'],
                'TotalRolls' => count($validated['items']),
                'IsActive' => 1,
                'CreatedBy' => $userId,
                'UpdatedBy' => $userId,
            ]);

            foreach ($validated['items'] as $item) {
                InDispatchChild::create([
                    'Dispatch' => $dispatch->ID,
                    'SourceType' => $item['SourceType'],
                    'RollSize' => $item['RollSize'],
                    'RequiredGramMeter' => $item['RequiredGramMeter'],
                    'FabricColor' => $item['FabricColor'],
                    'RollNumber' => $item['RollNumber'],
                    'IsActive' => 1,
                    'CreatedBy' => $userId,
                    'UpdatedBy' => $userId,
                ]);
            }
        });

        return redirect()->route('inventories.dispatch.index')->with('success', 'Dispatch record created successfully.');
    }

    public function edit($id)
    {
        $dispatch = InDispatch::with('children')->findOrFail($id);

        if ($dispatch->EntryDate) {
            $dispatch->EntryDate = date('Y-m-d', strtotime($dispatch->EntryDate));
        }

        $parties = Party::all();

        return view('inventories.dispatch.form', compact('dispatch', 'parties'));
    }

    public function update(Request $request, $id)
    {
        $dispatch = InDispatch::findOrFail($id);

        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'PartyName' => 'required|integer',
            'InvoiceNumber' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.SourceType' => 'required|integer|in:1,2',
            'items.*.RollSize' => 'required|integer',
            'items.*.RequiredGramMeter' => 'required|string|max:50',
            'items.*.FabricColor' => 'required|integer',
            'items.*.RollNumber' => 'required|integer',
        ]);

        DB::transaction(function () use ($dispatch, $validated, $id) {
            $userId = Auth::id() ?? 1;

            $dispatch->update([
                'EntryDate' => $validated['EntryDate'],
                'PartyName' => $validated['PartyName'],
                'InvoiceNumber' => $validated['InvoiceNumber'],
                'TotalRolls' => count($validated['items']),
                'UpdatedBy' => $userId,
            ]);

            InDispatchChild::where('Dispatch', $id)->delete();

            foreach ($validated['items'] as $item) {
                InDispatchChild::create([
                    'Dispatch' => $id,
                    'SourceType' => $item['SourceType'],
                    'RollSize' => $item['RollSize'],
                    'RequiredGramMeter' => $item['RequiredGramMeter'],
                    'FabricColor' => $item['FabricColor'],
                    'RollNumber' => $item['RollNumber'],
                    'IsActive' => 1,
                    'CreatedBy' => $userId,
                    'UpdatedBy' => $userId,
                ]);
            }
        });

        return redirect()->route('inventories.dispatch.index')->with('success', 'Dispatch record updated successfully.');
    }

    public function destroy($id)
    {
        $dispatch = InDispatch::findOrFail($id);

        DB::transaction(function () use ($dispatch, $id) {
            InDispatchChild::where('Dispatch', $id)->delete();
            $dispatch->delete();
        });

        return response()->json(['success' => true]);
    }
}
