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
        $transferId = $request->input('transfer_id');

        if (!$sourceType) {
            return response()->json([]);
        }

        $rolls = InTransaction::where('TransactionType', $sourceType)
            ->where('IsActive', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('indispatchchild as dc')
                  ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                  ->whereColumn('dc.RollNumber', 'intransaction.ID')
                  ->whereColumn('dc.SourceType', 'intransaction.TransactionType')
                  ->where('dc.IsActive', 1)
                  ->where('d.IsActive', 1);
            })
            ->whereNotExists(function ($q) use ($transferId) {
                $q->select(DB::raw(1))
                  ->from('intransferchild as tc')
                  ->join('intransfer as t', 'tc.Transfer', '=', 't.ID')
                  ->whereColumn('tc.RollNumber', 'intransaction.ID')
                  ->whereColumn('tc.SourceType', 'intransaction.TransactionType')
                  ->where('tc.IsActive', 1)
                  ->where('t.IsActive', 1);
                if ($transferId) {
                    $q->where('tc.Transfer', '!=', $transferId);
                }
            })
            ->select('ID', 'RollNumber')
            ->distinct()
            ->orderBy('RollNumber', 'asc')
            ->get();

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
            'items.*.InTransactionID' => 'required|integer',
        ]);

        $seenItems = [];
        foreach ($validated['items'] as $item) {
            $key = $item['SourceType'] . '_' . $item['InTransactionID'];
            if (isset($seenItems[$key])) {
                $errMsg = "Duplicate Roll Item in submission.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 422);
                }
                return back()->withInput()->withErrors(['items' => $errMsg]);
            }
            $seenItems[$key] = true;

            $alreadyDispatched = DB::table('indispatchchild as dc')
                ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                ->where('dc.SourceType', $item['SourceType'])
                ->where('dc.InTransactionID', $item['InTransactionID'])
                ->where('dc.IsActive', 1)
                ->where('d.IsActive', 1)
                ->exists();

            if ($alreadyDispatched) {
                $errMsg = "Selected Roll (ID: {$item['InTransactionID']}) is already dispatched and cannot be transferred.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 422);
                }
                return back()->withInput()->withErrors(['items' => $errMsg]);
            }

            $alreadyTransferred = DB::table('intransferchild as tc')
                ->join('intransfer as t', 'tc.Transfer', '=', 't.ID')
                ->where('tc.SourceType', $item['SourceType'])
                ->where('tc.InTransactionID', $item['InTransactionID'])
                ->where('tc.IsActive', 1)
                ->where('t.IsActive', 1)
                ->exists();

            if ($alreadyTransferred) {
                $errMsg = "Selected Roll (ID: {$item['InTransactionID']}) is already transferred.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 422);
                }
                return back()->withInput()->withErrors(['items' => $errMsg]);
            }
        }

        $transfer = DB::transaction(function () use ($validated, $request) {
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
                    'InTransactionID' => $item['InTransactionID'],
                    'IsActive' => 1,
                    'CreatedBy' => $userId,
                    'UpdatedBy' => $userId,
                ]);
            }
            return $transfer;
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transfer record created successfully.',
                'id' => $transfer->ID,
                'edit_url' => route('inventories.transfer.edit', $transfer->ID)
            ]);
        }

        return redirect()->route('inventories.transfer.index')->with('success', 'Transfer record created successfully.');
    }

    public function edit($id)
    {
        $transfer = InTransfer::with(['children.transactionRelation'])->findOrFail($id);

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
            'items.*.InTransactionID' => 'required|integer',
        ]);

        $seenItems = [];
        foreach ($validated['items'] as $item) {
            $key = $item['SourceType'] . '_' . $item['InTransactionID'];
            if (isset($seenItems[$key])) {
                return back()->withInput()->withErrors(['items' => "Duplicate Roll Item in submission."]);
            }
            $seenItems[$key] = true;

            $alreadyDispatched = DB::table('indispatchchild as dc')
                ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                ->where('dc.SourceType', $item['SourceType'])
                ->where('dc.InTransactionID', $item['InTransactionID'])
                ->where('dc.IsActive', 1)
                ->where('d.IsActive', 1)
                ->exists();

            if ($alreadyDispatched) {
                return back()->withInput()->withErrors(['items' => "Selected Roll is already dispatched and cannot be transferred."]);
            }

            $alreadyTransferred = DB::table('intransferchild as tc')
                ->join('intransfer as t', 'tc.Transfer', '=', 't.ID')
                ->where('tc.SourceType', $item['SourceType'])
                ->where('tc.InTransactionID', $item['InTransactionID'])
                ->where('tc.Transfer', '!=', $id)
                ->where('tc.IsActive', 1)
                ->where('t.IsActive', 1)
                ->exists();

            if ($alreadyTransferred) {
                return back()->withInput()->withErrors(['items' => "Selected Roll is already transferred."]);
            }
        }

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
                    'InTransactionID' => $item['InTransactionID'],
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
