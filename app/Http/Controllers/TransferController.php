<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InTransfer;
use App\Models\InTransferChild;
use App\Models\InTransaction;
use App\Models\Party;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index()
    {
        return view('inventories.transfer.index');
    }

    public function data(Request $request)
    {
        $query = InTransfer::with('partyRelation')->where('IsActive', 1);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('EntryDate', 'like', "%{$search}%")
                  ->orWhere('TotalRolls', 'like', "%{$search}%")
                  ->orWhereHas('partyRelation', function ($pr) use ($search) {
                      $pr->where('PartyName', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = ['ID', 'EntryDate', 'PartyName', 'TotalRolls', 'CreatedOn', 'UpdatedOn'];

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

    public function getRolls(Request $request)
    {
        $sourceType = $request->input('source_type');

        if (!$sourceType) {
            return response()->json([]);
        }

        $rolls = InTransaction::where('TransactionType', $sourceType)
            ->where('IsActive', 1)
            ->select('RollNumber')
            ->distinct()
            ->orderBy('RollNumber', 'asc')
            ->pluck('RollNumber');

        return response()->json($rolls);
    }

    public function create()
    {
        $transfer = new InTransfer();
        $transfer->EntryDate = date('Y-m-d');
        $transfer->children = collect();

        $parties = Party::where('IsActive', 1)->get();

        return view('inventories.transfer.form', compact('transfer', 'parties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'PartyName' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.SourceType' => 'required|integer|in:1,2',
            'items.*.RollNumber' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $userId = Auth::id() ?? 1;

            $transfer = InTransfer::create([
                'EntryDate' => $validated['EntryDate'],
                'PartyName' => $validated['PartyName'],
                'TotalRolls' => count($validated['items']),
                'IsActive' => 1,
                'CreatedBy' => $userId,
                'UpdatedBy' => $userId,
            ]);

            foreach ($validated['items'] as $item) {
                InTransferChild::create([
                    'Transfer' => $transfer->ID,
                    'SourceType' => $item['SourceType'],
                    'RollNumber' => $item['RollNumber'],
                    'IsActive' => 1,
                    'CreatedBy' => $userId,
                    'UpdatedBy' => $userId,
                ]);
            }
        });

        return redirect()->route('inventories.transfer.index')->with('success', 'Transfer record created successfully.');
    }

    public function edit($id)
    {
        $transfer = InTransfer::with('children')->findOrFail($id);

        if ($transfer->EntryDate) {
            $transfer->EntryDate = date('Y-m-d', strtotime($transfer->EntryDate));
        }

        $parties = Party::all();

        return view('inventories.transfer.form', compact('transfer', 'parties'));
    }

    public function update(Request $request, $id)
    {
        $transfer = InTransfer::findOrFail($id);

        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'PartyName' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.SourceType' => 'required|integer|in:1,2',
            'items.*.RollNumber' => 'required|integer',
        ]);

        DB::transaction(function () use ($transfer, $validated, $id) {
            $userId = Auth::id() ?? 1;

            $transfer->update([
                'EntryDate' => $validated['EntryDate'],
                'PartyName' => $validated['PartyName'],
                'TotalRolls' => count($validated['items']),
                'UpdatedBy' => $userId,
            ]);

            InTransferChild::where('Transfer', $id)->delete();

            foreach ($validated['items'] as $item) {
                InTransferChild::create([
                    'Transfer' => $id,
                    'SourceType' => $item['SourceType'],
                    'RollNumber' => $item['RollNumber'],
                    'IsActive' => 1,
                    'CreatedBy' => $userId,
                    'UpdatedBy' => $userId,
                ]);
            }
        });

        return redirect()->route('inventories.transfer.index')->with('success', 'Transfer record updated successfully.');
    }

    public function destroy($id)
    {
        $transfer = InTransfer::findOrFail($id);

        DB::transaction(function () use ($transfer, $id) {
            InTransferChild::where('Transfer', $id)->delete();
            $transfer->delete();
        });

        return response()->json(['success' => true]);
    }
}
