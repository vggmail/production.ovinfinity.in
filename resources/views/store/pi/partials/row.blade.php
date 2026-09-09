@php
    $idx = $index ?? 0;
    $itemMasterId = isset($item) ? ($item['ItemMaster'] ?? null) : ($child->ItemMaster ?? null);
    $mrlChildId = isset($item) ? ($item['MRLEntryChild'] ?? null) : ($child->MRLEntryChild ?? null);
    $mrlId = isset($item) ? ($item['MRLEntry'] ?? null) : ($child->MRLEntry ?? null);
    
    $qty = isset($item) ? ($item['Quantity'] ?? 0) : ($child->Quantity ?? 0);
    $basicRate = isset($item) ? ($item['BasicRate'] ?? 0) : ($child->BasicRate ?? 0);
    $tradeDisPct = isset($item) ? ($item['TradeDisPercent'] ?? 0) : ($child->TradeDisPercent ?? 0);
    $adPayDisPct = isset($item) ? ($item['AdPayDisPercent'] ?? 0) : ($child->AdPayDisPercent ?? 0);
    $packChargesPct = isset($item) ? ($item['PackChargesPercent'] ?? 0) : ($child->PackChargesPercent ?? 0);
    $freightPct = isset($item) ? ($item['FreightPercent'] ?? 0) : ($child->FreightPercent ?? 0);
    $gstRate = (int)(isset($item) ? ($item['GSTRate'] ?? 0) : ($child->GSTRate ?? 0));
    
    $itemMasterModel = isset($child) ? $child->itemMasterRelation : \App\Models\ItemMaster::find($itemMasterId);
    $itemName = $itemMasterModel->ItemName ?? 'Unknown Item';
    $partNo = $itemMasterModel->PartNo ?? '';
    $catNo = $itemMasterModel->CatalogueNo ?? '';
    $hsnNo = $itemMasterModel->HSNNo ?? '';
@endphp

<tr class="pi-item-row" data-index="{{ $idx }}" style="border-bottom: 1px solid #e2e8f0;">
    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <div style="font-size: 0.75rem; line-height: 1.2;">
            <strong style="color:#0f172a;">{{ $itemName }}</strong>@if ($partNo) <span style="color:#475569;"> | {{ $partNo }}</span>@endif @if ($catNo)<span style="color:#475569;"> | {{ $catNo }}</span>@endif
            @if ($hsnNo) <br><small style="color:#64748b;">HSN: {{ $hsnNo }}</small> @endif
        </div>
        <input type="hidden" name="items[{{ $idx }}][ItemMaster]" value="{{ $itemMasterId }}">
        <input type="hidden" name="items[{{ $idx }}][MRLEntryChild]" value="{{ $mrlChildId }}" class="row-mrl-child-id">
        <input type="hidden" name="items[{{ $idx }}][MRLEntry]" value="{{ $mrlId }}">
        
        <!-- Backhand calculated hidden fields (SS 2) -->
        <input type="hidden" name="items[{{ $idx }}][TradeDisAmount]" class="row-trade-dis-amount" value="0">
        <input type="hidden" name="items[{{ $idx }}][NetValAfterTrade]" class="row-net-val-after-trade" value="0">
        <input type="hidden" name="items[{{ $idx }}][AdPayDisAmount]" class="row-ad-pay-dis-amount" value="0">
        <input type="hidden" name="items[{{ $idx }}][PackChargesAmount]" class="row-pack-charges-amount" value="0">
        <input type="hidden" name="items[{{ $idx }}][FreightAmount]" class="row-freight-amount" value="0">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.001" min="0.001" name="items[{{ $idx }}][Quantity]" class="form-control row-qty" value="{{ $qty }}" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required>
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][BasicRate]" class="form-control row-basic-rate" value="{{ $basicRate }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required>
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][Amount]" class="form-control row-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][TradeDisPercent]" class="form-control row-trade-dis-pct" value="{{ $tradeDisPct }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Trade Discount Amount: ₹ 0.00">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][AdPayDisPercent]" class="form-control row-ad-pay-dis-pct" value="{{ $adPayDisPct }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Ad Pay Discount Amount: ₹ 0.00">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][NetValAfterDis]" class="form-control row-net-v-after-dis" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][PackChargesPercent]" class="form-control row-pack-charges-pct" value="{{ $packChargesPct }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Pack Charges Amount: ₹ 0.00">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][FreightPercent]" class="form-control row-freight-pct" value="{{ $freightPct }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Freight Amount: ₹ 0.00">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][NetAmount]" class="form-control row-net-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="number" step="1" min="0" name="items[{{ $idx }}][GSTRate]" class="form-control row-gst-rate" value="{{ $gstRate }}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required title="GST Amount: ₹ 0.00">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][GSTAmount]" class="form-control row-gst-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][PIAmount]" class="form-control row-pi-amount" value="0.00" readonly style="background:#eef2ff; color:#3730a3; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:700;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
        <input type="text" name="items[{{ $idx }}][NetRate]" class="form-control row-net-rate" value="0.00" readonly style="background:#fffbebf0; color:#b45309; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:700;">
    </td>

    <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top; text-align: center;">
        <button type="button" class="btn-delete-row" style="background:none; border:none; color:#ef4444; font-size:0.9rem; cursor:pointer;" title="Remove row">🗑️</button>
    </td>
</tr>
