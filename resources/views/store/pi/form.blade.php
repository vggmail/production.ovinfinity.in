@extends('layouts.app')

@section('title', $pi->exists ? 'Edit PI' : 'Add PI')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $pi->exists ? 'Edit PI' : 'Add PI' }}</h1>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <!-- <a href="{{ route('store.pi.index') }}" class="btn-action-secondary">
            &larr; Back to List
        </a>
        @if($pi->exists)
            <a href="{{ route('store.pi.print', $pi->ID) }}" target="_blank" class="btn-print-blue">
                <span>📄</span> <span>Print / Download PDF</span>
            </a>
        @endif -->
        <a href="{{ route('store.pi.index') }}" class="btn-action-secondary btn-close-circle" title="Close">
            ✕
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <strong style="font-weight: 700;">Please fix the following errors:</strong>
        <ul style="margin-top: 0.5rem; margin-bottom: 0; padding-left: 1.25rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $pi->exists ? route('store.pi.update', $pi->ID) : route('store.pi.store') }}" method="POST" id="pi-form">
    @csrf
    @if ($pi->exists)
        @method('PUT')
    @endif

    <!-- Top Fields Card with Select2 & col-12 layout -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; align-items: end;">
            
            <!-- Invoice Entry No. (col 2) -->
            <div style="grid-column: span 2;">
                <label for="InvoiceEntryNo" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Invoice Entry No.
                </label>
                <input type="text" name="InvoiceEntryNo" id="InvoiceEntryNo" class="form-control" 
                       value="{{ old('InvoiceEntryNo', $pi->InvoiceEntryNo) }}" readonly 
                       style="background-color: #f8fafc; font-weight: 600; color: #64748b; font-size: 0.85rem;" placeholder="Auto Issued">
            </div>

            <!-- Date (col 2) -->
            <div style="grid-column: span 2;">
                <label for="PIDate" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Date <span style="color: #ef4444;">*</span>
                </label>
                <input type="date" name="PIDate" id="PIDate" class="form-control" 
                       value="{{ old('PIDate', is_string($pi->PIDate) ? substr($pi->PIDate, 0, 10) : ($pi->PIDate ? $pi->PIDate->format('Y-m-d') : date('Y-m-d'))) }}" required style="font-size: 0.85rem;">
            </div>

            <!-- Supplier Name (col 4) -->
            <div style="grid-column: span 4;">
                <label for="Supplier" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Supplier Name <span style="color: #ef4444;">*</span>
                </label>
                <select name="Supplier" id="Supplier" class="form-control" required style="font-size: 0.85rem;">
                    <option value="">-- Select Supplier --</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->ID }}" {{ old('Supplier', $pi->Supplier) == $sup->ID ? 'selected' : '' }}>
                            {{ $sup->SupplierName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- PI No. (col 4) -->
            <div style="grid-column: span 4;">
                <label for="PINumber" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    PI No. <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="PINumber" id="PINumber" class="form-control text-uppercase" 
                       value="{{ old('PINumber', $pi->PINumber) }}" 
                       oninput="this.value = this.value.toUpperCase()" 
                       placeholder="e.g. PI-2026-A1" required style="font-weight: 700; letter-spacing: 0.5px; font-size: 0.85rem;">
            </div>

            <!-- Select MRL Entry No. (col-12 Select2 Multi Select) -->
            <div style="grid-column: span 12;">
                <label for="select-mrl-entry" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">
                    Select MRL Entry No.
                </label>
                @php
                    $selectedMrls = old('MRLNumbers', $pi->MRLNumbers ? json_decode($pi->MRLNumbers, true) : []);
                    if (!is_array($selectedMrls)) $selectedMrls = [];
                @endphp
                <select id="select-mrl-entry" class="form-control select2-mrl-entry" multiple style="width: 100%;">
                    @foreach ($mrlEntries as $mrl)
                        <option value="{{ $mrl->ID }}" {{ in_array($mrl->ID, $selectedMrls) ? 'selected' : '' }}>
                            MRL #{{ $mrl->ID }} (Date: {{ is_string($mrl->EntryDate) ? substr($mrl->EntryDate, 0, 10) : ($mrl->EntryDate ? $mrl->EntryDate->format('Y-m-d') : '') }} | Items: {{ $mrl->TotalItems }})
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    <!-- Hidden field for MRL Numbers array -->
    <div id="mrl-numbers-hidden-container">
        @foreach ($selectedMrls as $mrlId)
            <input type="hidden" name="MRLNumbers[]" value="{{ $mrlId }}" class="mrl-hidden-input" data-mrl="{{ $mrlId }}">
        @endforeach
    </div>

    <!-- Table Section matching SS 1 layout & SS 2 calculation fields -->
    <div class="card" style="padding: 0.75rem; margin-bottom: 1.25rem; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow-x: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding: 0 0.25rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0;">Item Wise Calculations</h3>
            <span style="font-size: 0.72rem; color: #64748b;">(Row calculations happen automatically. Hover over % inputs to see amount tooltips)</span>
        </div>

        <div style="overflow-x: auto; width: 100%;">
            <table class="table" id="pi-items-table" style="width: 100%; border-collapse: collapse; min-width: 980px; font-size: 0.75rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; text-align: left; font-size: 0.72rem;">
                        <th style="padding: 0.3rem 0.2rem; min-width: 130px; vertical-align: bottom;">Select Item</th>
                        <th style="padding: 0.3rem 0.2rem; width: 65px; vertical-align: bottom;">Qty</th>
                        <th style="padding: 0.3rem 0.2rem; width: 75px; vertical-align: bottom;">Basic Rate</th>
                        <th style="padding: 0.3rem 0.2rem; width: 80px; vertical-align: bottom;">Amount</th>
                        <th style="padding: 0.3rem 0.2rem; width: 65px; vertical-align: bottom;">Trade Dis.%</th>
                        <th style="padding: 0.3rem 0.2rem; width: 70px; vertical-align: bottom;">Ad. Pay. Dis. %</th>
                        <th style="padding: 0.3rem 0.2rem; width: 85px; vertical-align: bottom;">Net V. After Dis.</th>
                        <th style="padding: 0.3rem 0.2rem; width: 70px; vertical-align: bottom;">Pack. Charges %</th>
                        <th style="padding: 0.3rem 0.2rem; width: 65px; vertical-align: bottom;">Freight %</th>
                        <th style="padding: 0.3rem 0.2rem; width: 85px; vertical-align: bottom;">Net Amount</th>
                        <th style="padding: 0.3rem 0.2rem; width: 60px; vertical-align: bottom;">GST %</th>
                        <th style="padding: 0.3rem 0.2rem; width: 80px; vertical-align: bottom;">GST Amount</th>
                        <th style="padding: 0.3rem 0.2rem; width: 85px; vertical-align: bottom;">PI Amount</th>
                        <th style="padding: 0.3rem 0.2rem; width: 75px; vertical-align: bottom;">Net Rate</th>
                        <th style="padding: 0.3rem 0.2rem; width: 30px; text-align: center; vertical-align: bottom;">Action</th>
                    </tr>
                </thead>
                <tbody id="pi-items-body">
                    @php
                        $oldItems = old('items', []);
                    @endphp

                    @if (!empty($oldItems))
                        @foreach ($oldItems as $idx => $item)
                            @include('store.pi.partials.row', ['index' => $idx, 'item' => $item])
                        @endforeach
                    @elseif ($pi->exists && $pi->children->count() > 0)
                        @foreach ($pi->children as $idx => $child)
                            @include('store.pi.partials.row', ['index' => $idx, 'child' => $child])
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        
        <div id="no-items-message" style="display: {{ (!empty($oldItems) || ($pi->exists && $pi->children->count() > 0)) ? 'none' : 'block' }}; text-align: center; padding: 1.5rem; color: #94a3b8; font-weight: 500; font-size: 0.85rem;">
            No items added yet. Select MRL Entry No. above to fetch items into the table row wise.
        </div>
    </div>

    <!-- Bottom Summary Totals matching SS 1 -->
    <div class="card" style="padding: 1rem; margin-bottom: 1.25rem; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0;">
        <h4 style="font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Summary Totals</h4>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.6rem;">
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total Ad. Pay. Dis.</label>
                <input type="text" id="total-ad-pay-dis" name="TotalAdPayDisAmount" class="form-control" 
                       value="{{ old('TotalAdPayDisAmount', number_format($pi->TotalAdPayDisAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total Net V. After Dis.</label>
                <input type="text" id="total-net-v-after-dis" name="TotalNetValAfterDis" class="form-control" 
                       value="{{ old('TotalNetValAfterDis', number_format($pi->TotalNetValAfterDis ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total Pack. Charges</label>
                <input type="text" id="total-pack-charges" name="TotalPackChargesAmount" class="form-control" 
                       value="{{ old('TotalPackChargesAmount', number_format($pi->TotalPackChargesAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total Freight</label>
                <input type="text" id="total-freight" name="TotalFreightAmount" class="form-control" 
                       value="{{ old('TotalFreightAmount', number_format($pi->TotalFreightAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total Net Amount</label>
                <input type="text" id="total-net-amount" name="TotalNetAmount" class="form-control" 
                       value="{{ old('TotalNetAmount', number_format($pi->TotalNetAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 0.2rem;">Total GST Amount</label>
                <input type="text" id="total-gst-amount" name="TotalGSTAmount" class="form-control" 
                       value="{{ old('TotalGSTAmount', number_format($pi->TotalGSTAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #ffffff; font-weight: 700; color: #1e293b; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 0.7rem; color: #4338ca; margin-bottom: 0.2rem;">Total PI Amount</label>
                <input type="text" id="total-pi-amount" name="TotalPIAmount" class="form-control" 
                       value="{{ old('TotalPIAmount', number_format($pi->TotalPIAmount ?? 0, 2, '.', '')) }}" readonly 
                       style="background: #eef2ff; font-weight: 800; color: #3730a3; border-color: #c7d2fe; font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px;">
            </div>
        </div>
    </div>

    <!-- Remarks & Standard Form Actions -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="margin-bottom: 1rem;">
            <label for="Remarks" style="display: block; font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Remarks</label>
            <textarea name="Remarks" id="Remarks" class="form-control" rows="2" placeholder="Optional notes or terms..." style="font-size: 0.85rem;">{{ old('Remarks', $pi->Remarks) }}</textarea>
        </div>

        <div class="form-actions" style="display: flex; gap: 0.75rem; align-items: center;">
            <button type="submit" class="btn-save-green">
                {{ $pi->exists ? 'Update PI' : 'Save PI' }}
            </button>

            <!-- @if($pi->exists)
                <a href="{{ route('store.pi.print', $pi->ID) }}" target="_blank" class="btn-print-blue">
                    <span>📄</span> <span>Print / Download PDF</span>
                </a>
            @endif -->

            <!-- <a href="{{ route('store.pi.index') }}" class="btn-action-secondary">
                Cancel
            </a> -->
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let rowIndex = {{ max(count(old('items', [])), ($pi->exists ? $pi->children->count() : 0)) }};
        const loadedMrlChildIds = new Set();

        // Track already loaded mrl_child_ids in the DOM
        document.querySelectorAll('.row-mrl-child-id').forEach(input => {
            if (input.value) loadedMrlChildIds.add(String(input.value));
        });

        const selectMrl = document.getElementById('select-mrl-entry');
        const itemsBody = document.getElementById('pi-items-body');
        const noItemsMsg = document.getElementById('no-items-message');
        const hiddenMrlContainer = document.getElementById('mrl-numbers-hidden-container');

        // Initialize Select2 on MRL Entry selector if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('#select-mrl-entry').select2({
                placeholder: '-- Select MRL Entry No. (Search by ID or Date) --',
                width: '100%',
                allowClear: true
            }).on('change select2:select select2:unselect select2:clear', () => {
                triggerMrlFetch();
            });
        } else {
            selectMrl.addEventListener('change', triggerMrlFetch);
        }

        function triggerMrlFetch() {
            let selectedVals = [];
            if (typeof $.fn.select2 !== 'undefined' && $('#select-mrl-entry').hasClass('select2-hidden-accessible')) {
                selectedVals = $('#select-mrl-entry').val() || [];
            } else {
                selectedVals = Array.from(selectMrl.selectedOptions).map(opt => opt.value);
            }

            // Sync hidden inputs for MRLNumbers[]
            hiddenMrlContainer.innerHTML = '';
            selectedVals.forEach(mrlId => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'MRLNumbers[]';
                hiddenInput.value = mrlId;
                hiddenInput.className = 'mrl-hidden-input';
                hiddenMrlContainer.appendChild(hiddenInput);
            });

            if (selectedVals.length === 0) return;

            // Fetch MRL items via AJAX
            const fetchUrl = "{{ route('store.pi.fetchMrl') }}?mrl_ids=" + selectedVals.join(',');
            fetch(fetchUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    let addedAny = false;
                    res.data.forEach(item => {
                        const childKey = String(item.mrl_child_id);
                        if (!loadedMrlChildIds.has(childKey)) {
                            loadedMrlChildIds.add(childKey);
                            appendRow(item);
                            addedAny = true;
                        }
                    });

                    if (addedAny) {
                        updateTableVisibility();
                        recalculateAllRows();
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching MRL items:', err);
            });
        }

        function appendRow(item) {
            const tr = document.createElement('tr');
            tr.className = 'pi-item-row';
            tr.dataset.index = rowIndex;
            tr.style.borderBottom = '1px solid #e2e8f0';

            const itemText = `<strong style="color:#0f172a;">${escapeHtml(item.item_name)}</strong>` +
                (item.part_no ? `<span style="color:#475569;"> | ${escapeHtml(item.part_no)}</span>` : '') +
                (item.catalogue_no ? `<span style="color:#475569;"> | ${escapeHtml(item.catalogue_no)}</span>` : '') +
                (item.hsn_no ? `<br><small style="color:#64748b;">HSN: ${escapeHtml(item.hsn_no)}</small>` : '');

            tr.innerHTML = `
                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <div style="font-size: 0.75rem; line-height: 1.1;">${itemText}</div>
                    <input type="hidden" name="items[${rowIndex}][ItemMaster]" value="${item.item_id}">
                    <input type="hidden" name="items[${rowIndex}][MRLEntryChild]" value="${item.mrl_child_id}" class="row-mrl-child-id">
                    <input type="hidden" name="items[${rowIndex}][MRLEntry]" value="${item.mrl_entry_id}">
                    
                    <!-- Backhand calculated hidden fields (SS 2) -->
                    <input type="hidden" name="items[${rowIndex}][TradeDisAmount]" class="row-trade-dis-amount" value="0">
                    <input type="hidden" name="items[${rowIndex}][NetValAfterTrade]" class="row-net-val-after-trade" value="0">
                    <input type="hidden" name="items[${rowIndex}][AdPayDisAmount]" class="row-ad-pay-dis-amount" value="0">
                    <input type="hidden" name="items[${rowIndex}][PackChargesAmount]" class="row-pack-charges-amount" value="0">
                    <input type="hidden" name="items[${rowIndex}][FreightAmount]" class="row-freight-amount" value="0">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.001" min="0.001" name="items[${rowIndex}][Quantity]" class="form-control row-qty" value="${item.quantity}" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required>
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][BasicRate]" class="form-control row-basic-rate" value="0" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required>
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][Amount]" class="form-control row-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][TradeDisPercent]" class="form-control row-trade-dis-pct" value="0" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Trade Discount Amount: ₹ 0.00">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][AdPayDisPercent]" class="form-control row-ad-pay-dis-pct" value="0" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Ad Pay Discount Amount: ₹ 0.00">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][NetValAfterDis]" class="form-control row-net-v-after-dis" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][PackChargesPercent]" class="form-control row-pack-charges-pct" value="0" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Pack Charges Amount: ₹ 0.00">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][FreightPercent]" class="form-control row-freight-pct" value="0" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" title="Freight Amount: ₹ 0.00">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][NetAmount]" class="form-control row-net-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="number" step="1" min="0" name="items[${rowIndex}][GSTRate]" class="form-control row-gst-rate" value="${Math.round(item.default_gst || 0)}" placeholder="0" style="padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px;" required title="GST Amount: ₹ 0.00">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][GSTAmount]" class="form-control row-gst-amount" value="0.00" readonly style="background:#f8fafc; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:600;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][PIAmount]" class="form-control row-pi-amount" value="0.00" readonly style="background:#eef2ff; color:#3730a3; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:700;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top;">
                    <input type="text" name="items[${rowIndex}][NetRate]" class="form-control row-net-rate" value="0.00" readonly style="background:#fffbebf0; color:#b45309; padding: 0.2rem 0.25rem; font-size: 0.75rem; height: 28px; font-weight:700;">
                </td>

                <td style="padding: 0.35rem 0.2rem 0.25rem 0.2rem; vertical-align: top; text-align: center;">
                    <button type="button" class="btn-delete-row" style="background:none; border:none; color:#ef4444; font-size:0.9rem; cursor:pointer;" title="Remove row">🗑️</button>
                </td>
            `;

            itemsBody.appendChild(tr);
            rowIndex++;

            attachRowEvents(tr);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function updateTableVisibility() {
            const hasRows = itemsBody.querySelectorAll('tr.pi-item-row').length > 0;
            noItemsMsg.style.display = hasRows ? 'none' : 'block';
        }

        // Attach input calculation events to a table row
        function attachRowEvents(tr) {
            const qtyInput = tr.querySelector('.row-qty');
            const basicRateInput = tr.querySelector('.row-basic-rate');
            const tradeDisPctInput = tr.querySelector('.row-trade-dis-pct');
            const adPayDisPctInput = tr.querySelector('.row-ad-pay-dis-pct');
            const packChargesPctInput = tr.querySelector('.row-pack-charges-pct');
            const freightPctInput = tr.querySelector('.row-freight-pct');
            const gstRateInput = tr.querySelector('.row-gst-rate');
            const deleteBtn = tr.querySelector('.btn-delete-row');

            const inputsToWatch = [qtyInput, basicRateInput, tradeDisPctInput, adPayDisPctInput, packChargesPctInput, freightPctInput, gstRateInput];
            
            inputsToWatch.forEach(input => {
                if (input) {
                    input.addEventListener('input', () => calculateRow(tr));
                }
            });

            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    const mrlChildInput = tr.querySelector('.row-mrl-child-id');
                    if (mrlChildInput && mrlChildInput.value) {
                        loadedMrlChildIds.delete(String(mrlChildInput.value));
                    }
                    tr.remove();
                    updateTableVisibility();
                    recalculateTotals();
                });
            }

            // Initial row calculation
            calculateRow(tr);
        }

        // Calculate single row values matching SS 2 exact formulas
        function calculateRow(tr) {
            const qty = parseFloat(tr.querySelector('.row-qty').value) || 0;
            const basicRate = parseFloat(tr.querySelector('.row-basic-rate').value) || 0;
            const tradeDisPct = parseFloat(tr.querySelector('.row-trade-dis-pct').value) || 0;
            const adPayDisPct = parseFloat(tr.querySelector('.row-ad-pay-dis-pct').value) || 0;
            const packChargesPct = parseFloat(tr.querySelector('.row-pack-charges-pct').value) || 0;
            const freightPct = parseFloat(tr.querySelector('.row-freight-pct').value) || 0;
            const gstRate = parseFloat(tr.querySelector('.row-gst-rate').value) || 0;

            // SS 2 Formulas:
            // 4. Amount = Quantity * BasicRate
            const amount = qty * basicRate;

            // 6. TradeDisAmount (BackHand) = Amount * (TradeDisPct / 100)
            const tradeDisAmount = amount * (tradeDisPct / 100);

            // 7. NetValAfterTrade (Auto Cal BackHand) = Amount - TradeDisAmount
            const netValAfterTrade = amount - tradeDisAmount;

            // 9. AdPayDisAmount (BackHand) = NetValAfterTrade * (AdPayDisPct / 100)
            const adPayDisAmount = netValAfterTrade * (adPayDisPct / 100);

            // 10. NetValAfterDis (BackHand) = NetValAfterTrade - AdPayDisAmount
            const netValAfterDis = netValAfterTrade - adPayDisAmount;

            // 12. PackChargesAmount (BackHand) = NetValAfterTrade * (PackChargesPct / 100)
            const packChargesAmount = netValAfterTrade * (packChargesPct / 100);

            // 14. FreightAmount (BackHand) = NetValAfterTrade * (FreightPct / 100)
            const freightAmount = netValAfterTrade * (freightPct / 100);

            // 15. NetAmount = NetValAfterDis + PackChargesAmount + FreightAmount
            const netAmount = netValAfterDis + packChargesAmount + freightAmount;

            // 17. GSTAmount = NetAmount * (GSTRate / 100)
            const gstAmount = netAmount * (gstRate / 100);

            // 18. PIAmount = NetAmount + GSTAmount
            const piAmount = netAmount + gstAmount;

            // 19. NetRate = NetAmount / Quantity
            const netRate = qty > 0 ? (netAmount / qty) : 0;

            // Set visible row inputs
            tr.querySelector('.row-amount').value = amount.toFixed(2);
            tr.querySelector('.row-net-v-after-dis').value = netValAfterDis.toFixed(2);
            tr.querySelector('.row-net-amount').value = netAmount.toFixed(2);
            tr.querySelector('.row-gst-amount').value = gstAmount.toFixed(2);
            tr.querySelector('.row-pi-amount').value = piAmount.toFixed(2);
            tr.querySelector('.row-net-rate').value = netRate.toFixed(2);

            // Set hidden backhand inputs (SS 2)
            tr.querySelector('.row-trade-dis-amount').value = tradeDisAmount.toFixed(2);
            tr.querySelector('.row-net-val-after-trade').value = netValAfterTrade.toFixed(2);
            tr.querySelector('.row-ad-pay-dis-amount').value = adPayDisAmount.toFixed(2);
            tr.querySelector('.row-pack-charges-amount').value = packChargesAmount.toFixed(2);
            tr.querySelector('.row-freight-amount').value = freightAmount.toFixed(2);

            // Dynamic Tooltips & Subtext Hints for rupee amounts (SS 2 backhand values)
            const tradeDisPctInput = tr.querySelector('.row-trade-dis-pct');
            const adPayDisPctInput = tr.querySelector('.row-ad-pay-dis-pct');
            const packChargesPctInput = tr.querySelector('.row-pack-charges-pct');
            const freightPctInput = tr.querySelector('.row-freight-pct');
            const gstRateInput = tr.querySelector('.row-gst-rate');

            if (tradeDisPctInput) tradeDisPctInput.title = `Trade Discount Amount: ₹ ${tradeDisAmount.toFixed(2)}`;
            if (adPayDisPctInput) adPayDisPctInput.title = `Advance Pay Discount Amount: ₹ ${adPayDisAmount.toFixed(2)}`;
            if (packChargesPctInput) packChargesPctInput.title = `Pack Charges Amount: ₹ ${packChargesAmount.toFixed(2)}`;
            if (freightPctInput) freightPctInput.title = `Freight Amount: ₹ ${freightAmount.toFixed(2)}`;
            if (gstRateInput) gstRateInput.title = `GST Amount: ₹ ${gstAmount.toFixed(2)}`;

            recalculateTotals();
        }

        function recalculateAllRows() {
            document.querySelectorAll('tr.pi-item-row').forEach(tr => calculateRow(tr));
        }

        // Recalculate summary cards at the bottom matching SS 1
        function recalculateTotals() {
            let totalAdPayDis = 0;
            let totalNetVAfterDis = 0;
            let totalPackCharges = 0;
            let totalFreight = 0;
            let totalNet = 0;
            let totalGST = 0;
            let totalPI = 0;

            document.querySelectorAll('tr.pi-item-row').forEach(tr => {
                totalAdPayDis += parseFloat(tr.querySelector('.row-ad-pay-dis-amount').value) || 0;
                totalNetVAfterDis += parseFloat(tr.querySelector('.row-net-v-after-dis').value) || 0;
                totalPackCharges += parseFloat(tr.querySelector('.row-pack-charges-amount').value) || 0;
                totalFreight += parseFloat(tr.querySelector('.row-freight-amount').value) || 0;
                totalNet += parseFloat(tr.querySelector('.row-net-amount').value) || 0;
                totalGST += parseFloat(tr.querySelector('.row-gst-amount').value) || 0;
                totalPI += parseFloat(tr.querySelector('.row-pi-amount').value) || 0;
            });

            document.getElementById('total-ad-pay-dis').value = totalAdPayDis.toFixed(2);
            document.getElementById('total-net-v-after-dis').value = totalNetVAfterDis.toFixed(2);
            document.getElementById('total-pack-charges').value = totalPackCharges.toFixed(2);
            document.getElementById('total-freight').value = totalFreight.toFixed(2);
            document.getElementById('total-net-amount').value = totalNet.toFixed(2);
            document.getElementById('total-gst-amount').value = totalGST.toFixed(2);
            document.getElementById('total-pi-amount').value = totalPI.toFixed(2);
        }

        // Attach calculation listeners to existing rendered rows
        document.querySelectorAll('tr.pi-item-row').forEach(tr => attachRowEvents(tr));
    });
</script>
@endsection
