<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InTransaction;
use App\Models\RollSize;
use App\Models\FabricColor;
use App\Models\LoomNumber;
use App\Models\Party;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    private function getAvailableProductionQuery()
    {
        return InTransaction::where('TransactionType', 1)
            ->where('IsActive', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('indispatchchild as dc')
                  ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                  ->whereColumn('dc.InTransactionID', 'intransaction.ID')
                  ->whereColumn('dc.SourceType', 'intransaction.TransactionType')
                  ->where('dc.IsActive', 1)
                  ->where('d.IsActive', 1);
            });
    }

    public function index()
    {
        $baseQuery = InTransaction::where('TransactionType', 1)->where('IsActive', 1);

        $rollSizeIds = (clone $baseQuery)->whereNotNull('RollSize')->distinct()->pluck('RollSize');
        $rollSizes = RollSize::whereIn('ID', $rollSizeIds)->orderBy('RollSize', 'asc')->get();

        $colorIds = (clone $baseQuery)->whereNotNull('FabricColor')->distinct()->pluck('FabricColor');
        $fabricColors = FabricColor::whereIn('ID', $colorIds)->orderBy('FabricColor', 'asc')->get();

        $rgmOptions = (clone $baseQuery)
            ->whereNotNull('RequiredGramMeter')
            ->where('RequiredGramMeter', '!=', '')
            ->distinct()
            ->pluck('RequiredGramMeter')
            ->sort()
            ->values();

        $parties = Party::where('IsActive', 1)->orderBy('PartyName', 'asc')->get();

        return view('inventories.production.index', compact('rollSizes', 'fabricColors', 'rgmOptions', 'parties'));
    }

    public function data(Request $request)
    {
        $query = InTransaction::where('TransactionType', 1)
            ->where('IsActive', 1)
            ->with(['rollSizeRelation', 'fabricColorRelation', 'loomNumberRelation']);

        $showAll = $request->boolean('show_all') || $request->input('show_all') == '1';

        if (!$showAll) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('indispatchchild as dc')
                  ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                  ->whereColumn('dc.InTransactionID', 'intransaction.ID')
                  ->whereColumn('dc.SourceType', 'intransaction.TransactionType')
                  ->where('dc.IsActive', 1)
                  ->where('d.IsActive', 1);
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('EntryDate', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('EntryDate', '<=', $endDate);
        }

        if ($rollSize = $request->input('roll_size')) {
            $query->where('RollSize', $rollSize);
        }

        if ($rgm = $request->input('required_gram_meter')) {
            $query->where('RequiredGramMeter', $rgm);
        }

        if ($fabricColor = $request->input('fabric_color')) {
            $query->where('FabricColor', $fabricColor);
        }

        if ($loomNumber = $request->input('loom_number')) {
            $query->where('LoomNumber', $loomNumber);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%{$search}%")
                  ->orWhere('RollNumber', 'like', "%{$search}%")
                  ->orWhere('RequiredGramMeter', 'like', "%{$search}%")
                  ->orWhere('ClosingMeter', 'like', "%{$search}%")
                  ->orWhere('ActualMeter', 'like', "%{$search}%")
                  ->orWhere('GrossWeight', 'like', "%{$search}%")
                  ->orWhere('CoreWeight', 'like', "%{$search}%")
                  ->orWhere('NetWeight', 'like', "%{$search}%")
                  ->orWhere('ActualMeterWeight', 'like', "%{$search}%")
                  ->orWhere('Variation', 'like', "%{$search}%")
                  ->orWhereHas('rollSizeRelation', function ($rs) use ($search) {
                      $rs->where('RollSize', 'like', "%{$search}%");
                  })
                  ->orWhereHas('fabricColorRelation', function ($fc) use ($search) {
                      $fc->where('FabricColor', 'like', "%{$search}%");
                  })
                  ->orWhereHas('loomNumberRelation', function ($ln) use ($search) {
                      $ln->where('LoomNumber', 'like', "%{$search}%");
                  });
            });
        }

        $sortCol = $request->input('sort_col', 'ID');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedCols = [
            'ID', 'EntryDate', 'RollSize', 'FabricColor', 'LoomNumber',
            'RequiredGramMeter', 'ClosingMeter', 'ActualMeter', 'GrossWeight',
            'CoreWeight', 'NetWeight', 'ActualMeterWeight', 'Variation',
            'CreatedOn', 'UpdatedOn'
        ];

        if (in_array($sortCol, $allowedCols)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('ID', 'desc');
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->paginate($perPage);

        $pageIds = $data->getCollection()->pluck('ID')->toArray();

        $dispatches = [];

        if (!empty($pageIds)) {
            $dispatches = DB::table('indispatchchild as dc')
                ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
                ->where('dc.SourceType', 1)
                ->whereIn('dc.InTransactionID', $pageIds)
                ->where('dc.IsActive', 1)
                ->where('d.IsActive', 1)
                ->select('dc.InTransactionID', 'd.DispatchType')
                ->get()
                ->keyBy('InTransactionID');
        }

        // Transform collection for clean datatable rendering with status information
        $data->getCollection()->transform(function ($item) use ($dispatches) {
            $item->RollSizeName = $item->rollSizeRelation ? $item->rollSizeRelation->RollSize : ($item->RollSize ?? '-');
            $item->FabricColorName = $item->fabricColorRelation ? $item->fabricColorRelation->FabricColor : ($item->FabricColor ?? '-');
            $item->LoomNumberValue = $item->loomNumberRelation ? $item->loomNumberRelation->LoomNumber : ($item->LoomNumber ?? '-');
            $item->EntryDateFormatted = $item->EntryDate ? date('d-m-Y', strtotime($item->EntryDate)) : '-';

            if (isset($dispatches[$item->ID])) {
                $disp = $dispatches[$item->ID];
                $item->Status = ($disp->DispatchType === 'Transfer') ? 'Transferred' : 'Dispatched';
                $item->IsAvailable = false;
            } else {
                $item->Status = 'Available';
                $item->IsAvailable = true;
            }

            return $item;
        });

        return response()->json($data);
    }


    public function create()
    {
        $production = new InTransaction();
        $production->EntryDate = date('Y-m-d');
        
        $rollSizes = RollSize::where('IsActive', 1)->get();
        $fabricColors = FabricColor::where('IsActive', 1)->get();
        $loomNumbers = LoomNumber::where('IsActive', 1)->get();

        return view('inventories.production.form', compact('production', 'rollSizes', 'fabricColors', 'loomNumbers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|string|max:50',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'LoomNumber' => 'required|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'OpeningMeter' => 'nullable|string|max:50',
            'ClosingMeter' => 'nullable|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        $validated['TransactionType'] = 1;
        $validated['IsActive'] = 1;
        $validated['CreatedBy'] = Auth::id() ?? 1;
        $validated['UpdatedBy'] = Auth::id() ?? 1;

        InTransaction::create($validated);

        return redirect()->route('inventories.production.create')->with('success', 'Production record created successfully.');
    }

    public function edit($id)
    {
        $production = InTransaction::findOrFail($id);
        
        // Format date for HTML5 date input
        if ($production->EntryDate) {
            $production->EntryDate = date('Y-m-d', strtotime($production->EntryDate));
        }

        $isDispatchedOrTransferred = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->where('dc.SourceType', 1)
            ->where('dc.InTransactionID', $id)
            ->where('dc.IsActive', 1)
            ->where('d.IsActive', 1)
            ->exists() || DB::table('intransferchild as tc')
            ->join('intransfer as t', 'tc.Transfer', '=', 't.ID')
            ->where('tc.SourceType', 1)
            ->where('tc.InTransactionID', $id)
            ->where('tc.IsActive', 1)
            ->where('t.IsActive', 1)
            ->exists();

        $rollSizes = RollSize::where('IsActive', 1)->get();
        $fabricColors = FabricColor::where('IsActive', 1)->get();
        $loomNumbers = LoomNumber::where('IsActive', 1)->get();

        return view('inventories.production.form', compact('production', 'rollSizes', 'fabricColors', 'loomNumbers', 'isDispatchedOrTransferred'));
    }

    public function update(Request $request, $id)
    {
        $production = InTransaction::findOrFail($id);

        $isDispatchedOrTransferred = DB::table('indispatchchild as dc')
            ->join('indispatch as d', 'dc.Dispatch', '=', 'd.ID')
            ->where('dc.SourceType', 1)
            ->where('dc.InTransactionID', $id)
            ->where('dc.IsActive', 1)
            ->where('d.IsActive', 1)
            ->exists() || DB::table('intransferchild as tc')
            ->join('intransfer as t', 'tc.Transfer', '=', 't.ID')
            ->where('tc.SourceType', 1)
            ->where('tc.InTransactionID', $id)
            ->where('tc.IsActive', 1)
            ->where('t.IsActive', 1)
            ->exists();

        $validated = $request->validate([
            'EntryDate' => 'required|date',
            'RollNumber' => 'required|string|max:50',
            'RollSize' => 'required|integer',
            'FabricColor' => 'required|integer',
            'LoomNumber' => 'required|integer',
            'RequiredGramMeter' => 'required|string|max:50',
            'OpeningMeter' => 'nullable|string|max:50',
            'ClosingMeter' => 'nullable|string|max:50',
            'ActualMeter' => 'required|string|max:50',
            'GrossWeight' => 'required|string|max:50',
            'CoreWeight' => 'required|string|max:50',
            'NetWeight' => 'required|string|max:50',
            'ActualMeterWeight' => 'required|string|max:50',
            'Variation' => 'required|string|max:50',
        ]);

        if ($isDispatchedOrTransferred) {
            $validated['RollNumber'] = $production->RollNumber;
            $validated['RollSize'] = $production->RollSize;
            $validated['FabricColor'] = $production->FabricColor;
            $validated['LoomNumber'] = $production->LoomNumber;
        }

        $validated['UpdatedBy'] = Auth::id() ?? 1;

        $production->update($validated);

        return redirect()->route('inventories.production.index')->with('success', 'Production record updated successfully.');
    }

    public function destroy($id)
    {
        $production = InTransaction::findOrFail($id);
        $production->delete();

        return response()->json(['success' => true]);
    }
}
