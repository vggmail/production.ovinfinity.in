@extends('layouts.app')

@section('title', $dispatch->exists ? 'Edit Dispatch' : 'Add New Dispatch')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $dispatch->exists ? 'Edit Dispatch' : 'Add New Dispatch' }}</h1>
    </div>
    <a href="{{ route('inventories.dispatch.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;" title="Close">
        ✕
    </a>
</div>

<div class="card" style="margin-top: 1rem;">
    <form action="{{ $dispatch->exists ? route('inventories.dispatch.update', $dispatch->ID) : route('inventories.dispatch.store') }}" method="POST" id="dispatch-form">
        @csrf
        @if($dispatch->exists)
            @method('PUT')
        @endif

        <!-- Top Header Controls -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="ID">Id</label>
                <input type="text" id="ID" value="{{ $dispatch->ID ?? '' }}" disabled style="background: rgba(0,0,0,0.03);">
            </div>

            <div class="form-group">
                <label for="EntryDate">Entry Date</label>
                <input type="date" name="EntryDate" id="EntryDate" value="{{ old('EntryDate', $dispatch->EntryDate) }}" class="@error('EntryDate') is-invalid @enderror" required>
                @error('EntryDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="PartyName">Party Name</label>
                <select name="PartyName" id="PartyName" class="@error('PartyName') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->ID }}" {{ old('PartyName', $dispatch->PartyName) == $party->ID ? 'selected' : '' }}>
                            {{ $party->PartyName }}
                        </option>
                    @endforeach
                </select>
                @error('PartyName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="InvoiceNumber">Invoice Number</label>
                <input type="text" name="InvoiceNumber" id="InvoiceNumber" value="{{ old('InvoiceNumber', $dispatch->InvoiceNumber) }}" class="@error('InvoiceNumber') is-invalid @enderror" placeholder="Enter invoice number">
                @error('InvoiceNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Entry Type</label>
                <div style="display: flex; align-items: center; gap: 1.25rem; height: 38px;">
                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600;">
                        <input type="radio" name="DispatchType" value="Dispatch" {{ old('DispatchType', $dispatch->DispatchType ?? 'Dispatch') === 'Dispatch' ? 'checked' : '' }}>
                        <span>Dispatch</span>
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600;">
                        <input type="radio" name="DispatchType" value="Transfer" {{ old('DispatchType', $dispatch->DispatchType ?? 'Dispatch') === 'Transfer' ? 'checked' : '' }}>
                        <span>Transfer</span>
                    </label>
                </div>
                @error('DispatchType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Dynamic Items Table matching user mockup styling -->
        <div style="margin-bottom: 1.5rem; overflow-x: auto;">
            <table class="datatable" id="items-table" style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; border: 1px solid #c2e2c4; border-radius: 6px;">
                <thead>
                    <tr style="background-color: #d8ebd9; color: #1e3a1f;">
                        <th style="width: 50px; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Sr No</th>
                        <th style="width: 10%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Source Type</th>
                        <th style="width: 10%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Roll Size</th>
                        <th style="width: 10%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">RGM</th>
                        <th style="width: 10%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Fabric Color</th>
                        <th style="width: 12%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Roll Number</th>
                        <th style="width: 9%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Actual Meter</th>
                        <th style="width: 9%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Gross Weight</th>
                        <th style="width: 9%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Core Weight</th>
                        <th style="width: 9%; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Net Weight</th>
                        <th style="width: 50px; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Action</th>
                    </tr>
                </thead>
                <tbody id="items-table-body" style="background-color: #eaf5eb;">
                    <!-- Dynamic rows inserted here -->
                </tbody>
                <tfoot>
                    <tr style="background-color: #d8ebd9;">
                        <td colspan="11" style="text-align: center; padding: 8px;">
                            <button type="button" id="btn-add-row" class="btn-action-secondary" style="width: 100%; max-width: 400px; padding: 6px 16px; font-weight: 600; cursor: pointer; border: 1px solid #6b8e6d; background: white; border-radius: 4px; color: #1e3a1f;">
                                Add
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Bottom Controls -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="TotalRolls">Total Rolls</label>
                <input type="text" id="TotalRolls" value="{{ old('TotalRolls', $dispatch->TotalRolls ?? 0) }}" readonly style="background: rgba(0,0,0,0.03);">
            </div>
            <div class="form-group">
                <label for="TotalActualMeter">Total Actual Meter</label>
                <input type="text" id="TotalActualMeter" value="0" readonly style="background: rgba(0,0,0,0.03);">
            </div>
            <div class="form-group">
                <label for="TotalGrossWeight">Total Gross Weight</label>
                <input type="text" id="TotalGrossWeight" value="0" readonly style="background: rgba(0,0,0,0.03);">
            </div>
            <div class="form-group">
                <label for="TotalCoreWeight">Total Core Weight</label>
                <input type="text" id="TotalCoreWeight" value="0" readonly style="background: rgba(0,0,0,0.03);">
            </div>
            <div class="form-group">
                <label for="TotalNetWeight">Total Net Weight</label>
                <input type="text" id="TotalNetWeight" value="0" readonly style="background: rgba(0,0,0,0.03);">
            </div>
        </div>

        <div>
            <button type="submit" class="btn-success">
                Save
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableBody = document.getElementById('items-table-body');
        const btnAddRow = document.getElementById('btn-add-row');
        const totalRollsInput = document.getElementById('TotalRolls');
        const totalGrossWeightInput = document.getElementById('TotalGrossWeight');
        const totalCoreWeightInput = document.getElementById('TotalCoreWeight');
        const totalActualMeterInput = document.getElementById('TotalActualMeter');
        const totalNetWeightInput = document.getElementById('TotalNetWeight');

        const initialItems = @json(old('items', $dispatch->children ?? []));
        const optionsApiUrl = "{{ route('inventories.dispatch.options') }}";
        const dispatchId = "{{ $dispatch->exists ? $dispatch->ID : '' }}";

        function round2(val) {
            return Math.round((parseFloat(val) || 0) * 100) / 100;
        }

        function updateSrNumbersAndTotal() {
            const rows = tableBody.querySelectorAll('tr');
            let sumGross = 0, sumCore = 0, sumMeter = 0, sumNet = 0;

            rows.forEach((row, index) => {
                const srCell = row.querySelector('.sr-no');
                if (srCell) {
                    srCell.textContent = index + 1;
                }
                const sourceTypeSelect = row.querySelector('.source-type-select');
                const rollSizeSelect = row.querySelector('.roll-size-select');
                const rgmSelect = row.querySelector('.rgm-select');
                const fabricColorSelect = row.querySelector('.fabric-color-select');
                const rollNumberSelect = row.querySelector('.roll-number-select');

                if (sourceTypeSelect) sourceTypeSelect.name = `items[${index}][SourceType]`;
                if (rollSizeSelect) rollSizeSelect.name = `items[${index}][RollSize]`;
                if (rgmSelect) rgmSelect.name = `items[${index}][RequiredGramMeter]`;
                if (fabricColorSelect) fabricColorSelect.name = `items[${index}][FabricColor]`;
                if (rollNumberSelect) rollNumberSelect.name = `items[${index}][InTransactionID]`;

                sumGross += round2(row.querySelector('.gross-weight-input')?.value);
                sumCore  += round2(row.querySelector('.core-weight-input')?.value);
                sumMeter += round2(row.querySelector('.actual-meter-input')?.value);
                sumNet   += round2(row.querySelector('.net-weight-input')?.value);
            });

            totalRollsInput.value = rows.length;
            totalGrossWeightInput.value = round2(sumGross);
            totalCoreWeightInput.value  = round2(sumCore);
            totalActualMeterInput.value = round2(sumMeter);
            totalNetWeightInput.value   = round2(sumNet);
        }

        async function fetchOptions(step, params, selectElement, selectedValue = null) {
            selectElement.innerHTML = '<option value="">Loading...</option>';
            if (step === 'roll_number' && typeof $.fn.select2 !== 'undefined') {
                $(selectElement).select2({ placeholder: 'Loading...' });
            }

            const requestParams = { step, ...params };
            if (dispatchId) {
                requestParams.dispatch_id = dispatchId;
            }
            const queryStr = new URLSearchParams(requestParams).toString();
            try {
                const response = await fetch(`${optionsApiUrl}?${queryStr}`);
                const data = await response.json();

                let html = '<option value="">Select</option>';

                if (step === 'roll_size') {
                    data.forEach(item => {
                        const sel = selectedValue && String(selectedValue) === String(item.ID) ? 'selected' : '';
                        html += `<option value="${item.ID}" ${sel}>${item.RollSize}</option>`;
                    });
                } else if (step === 'rgm') {
                    data.forEach(val => {
                        const sel = selectedValue && String(selectedValue) === String(val) ? 'selected' : '';
                        html += `<option value="${val}" ${sel}>${val}</option>`;
                    });
                } else if (step === 'fabric_color') {
                    data.forEach(item => {
                        const sel = selectedValue && String(selectedValue) === String(item.ID) ? 'selected' : '';
                        html += `<option value="${item.ID}" ${sel}>${item.FabricColor}</option>`;
                    });
                } else if (step === 'roll_number') {
                    data.forEach(item => {
                        const sel = selectedValue && String(selectedValue) === String(item.ID) ? 'selected' : '';
                        html += `<option value="${item.ID}" data-gross="${item.GrossWeight ?? ''}" data-core="${item.CoreWeight ?? ''}" data-meter="${item.ActualMeter ?? ''}" data-net="${item.NetWeight ?? ''}" ${sel}>${item.RollNumber}</option>`;
                    });
                }

                selectElement.innerHTML = html;
                if (step === 'roll_number' && typeof $.fn.select2 !== 'undefined') {
                    $(selectElement).select2({ placeholder: 'Select Roll Number', width: '100%' });
                }
            } catch (err) {
                console.error(`Failed to load options for step ${step}`, err);
                selectElement.innerHTML = '<option value="">Error loading</option>';
            }
        }

        async function createRow(itemData = {}) {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #c2e2c4';

            const srNo = tableBody.children.length + 1;
            const selectedSourceType = itemData.SourceType || '';
            const selectedRollSize = itemData.RollSize || '';
            const selectedRgm = itemData.RequiredGramMeter || '';
            const selectedFabricColor = itemData.FabricColor || '';
            const selectedRollNumber = itemData.InTransactionID || itemData.RollNumber || '';

            tr.innerHTML = `
                <td class="sr-no" style="text-align: center; padding: 8px;">${srNo}</td>
                <td style="padding: 8px;">
                    <select class="source-type-select" style="width: 100%;" required>
                        <option value="">Select</option>
                        <option value="1" ${String(selectedSourceType) === '1' ? 'selected' : ''}>Production</option>
                        <option value="2" ${String(selectedSourceType) === '2' ? 'selected' : ''}>Purchase</option>
                    </select>
                </td>
                <td style="padding: 8px;">
                    <select class="roll-size-select" style="width: 100%;" required>
                        <option value="">Select</option>
                    </select>
                </td>
                <td style="padding: 8px;">
                    <select class="rgm-select" style="width: 100%;" required>
                        <option value="">Select</option>
                    </select>
                </td>
                <td style="padding: 8px;">
                    <select class="fabric-color-select" style="width: 100%;" required>
                        <option value="">Select</option>
                    </select>
                </td>
                <td style="padding: 8px;">
                    <select class="roll-number-select" style="width: 100%;" required>
                        <option value="">Select</option>
                    </select>
                </td>
                <td style="padding: 8px;">
                    <input type="text" class="actual-meter-input" readonly value="" style="width: 100%; background: rgba(0,0,0,0.03); border: 1px solid #d1d5db; border-radius: 4px; padding: 5px 8px; text-align: right;">
                </td>
                <td style="padding: 8px;">
                    <input type="text" class="gross-weight-input" readonly value="" style="width: 100%; background: rgba(0,0,0,0.03); border: 1px solid #d1d5db; border-radius: 4px; padding: 5px 8px; text-align: right;">
                </td>
                <td style="padding: 8px;">
                    <input type="text" class="core-weight-input" readonly value="" style="width: 100%; background: rgba(0,0,0,0.03); border: 1px solid #d1d5db; border-radius: 4px; padding: 5px 8px; text-align: right;">
                </td>
                <td style="padding: 8px;">
                    <input type="text" class="net-weight-input" readonly value="" style="width: 100%; background: rgba(0,0,0,0.03); border: 1px solid #d1d5db; border-radius: 4px; padding: 5px 8px; text-align: right;">
                </td>
                <td style="text-align: center; padding: 8px;">
                    <button type="button" class="btn-remove-row" style="background: transparent; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Remove Row">🗑️</button>
                </td>
            `;

            const sourceTypeSelect = tr.querySelector('.source-type-select');
            const rollSizeSelect = tr.querySelector('.roll-size-select');
            const rgmSelect = tr.querySelector('.rgm-select');
            const fabricColorSelect = tr.querySelector('.fabric-color-select');
            const rollNumberSelect = tr.querySelector('.roll-number-select');
            const grossWeightInput = tr.querySelector('.gross-weight-input');
            const coreWeightInput = tr.querySelector('.core-weight-input');
            const actualMeterInput = tr.querySelector('.actual-meter-input');
            const netWeightInput = tr.querySelector('.net-weight-input');
            const btnRemove = tr.querySelector('.btn-remove-row');

            function fillWeightFields(selectEl) {
                const selected = selectEl.options[selectEl.selectedIndex];
                if (selected && selected.value) {
                    grossWeightInput.value = selected.dataset.gross || '';
                    coreWeightInput.value  = selected.dataset.core  || '';
                    actualMeterInput.value = selected.dataset.meter || '';
                    netWeightInput.value   = selected.dataset.net   || '';
                } else {
                    grossWeightInput.value = '';
                    coreWeightInput.value  = '';
                    actualMeterInput.value = '';
                    netWeightInput.value   = '';
                }
                updateSrNumbersAndTotal();
            }

            if (typeof $.fn.select2 !== 'undefined') {
                $(rollNumberSelect).select2({ placeholder: 'Select' });
            }

            // 1. Source Type change handler
            sourceTypeSelect.addEventListener('change', async (e) => {
                const sourceType = e.target.value;
                rollSizeSelect.innerHTML = '<option value="">Select</option>';
                rgmSelect.innerHTML = '<option value="">Select</option>';
                fabricColorSelect.innerHTML = '<option value="">Select</option>';
                rollNumberSelect.innerHTML = '<option value="">Select</option>';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollNumberSelect).select2({ placeholder: 'Select' });
                }

                if (sourceType) {
                    await fetchOptions('roll_size', { source_type: sourceType }, rollSizeSelect);
                }
            });

            // 2. Roll Size change handler
            rollSizeSelect.addEventListener('change', async (e) => {
                const sourceType = sourceTypeSelect.value;
                const rollSize = e.target.value;
                rgmSelect.innerHTML = '<option value="">Select</option>';
                fabricColorSelect.innerHTML = '<option value="">Select</option>';
                rollNumberSelect.innerHTML = '<option value="">Select</option>';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollNumberSelect).select2({ placeholder: 'Select' });
                }

                if (sourceType && rollSize) {
                    await fetchOptions('rgm', { source_type: sourceType, roll_size: rollSize }, rgmSelect);
                }
            });

            // 3. RGM change handler
            rgmSelect.addEventListener('change', async (e) => {
                const sourceType = sourceTypeSelect.value;
                const rollSize = rollSizeSelect.value;
                const rgm = e.target.value;
                fabricColorSelect.innerHTML = '<option value="">Select</option>';
                rollNumberSelect.innerHTML = '<option value="">Select</option>';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollNumberSelect).select2({ placeholder: 'Select' });
                }

                if (sourceType && rollSize && rgm) {
                    await fetchOptions('fabric_color', { source_type: sourceType, roll_size: rollSize, rgm }, fabricColorSelect);
                }
            });

            // 4. Fabric Color change handler
            fabricColorSelect.addEventListener('change', async (e) => {
                const sourceType = sourceTypeSelect.value;
                const rollSize = rollSizeSelect.value;
                const rgm = rgmSelect.value;
                const fabricColor = e.target.value;
                rollNumberSelect.innerHTML = '<option value="">Select</option>';
                grossWeightInput.value = '';
                coreWeightInput.value  = '';
                actualMeterInput.value = '';
                netWeightInput.value   = '';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollNumberSelect).select2({ placeholder: 'Select' });
                }

                if (sourceType && rollSize && rgm && fabricColor) {
                    await fetchOptions('roll_number', { source_type: sourceType, roll_size: rollSize, rgm, fabric_color: fabricColor }, rollNumberSelect);
                }
                updateSrNumbersAndTotal();
            });

            // 5. Roll Number change handler — fill weight fields
            rollNumberSelect.addEventListener('change', () => fillWeightFields(rollNumberSelect));
            if (typeof $.fn.select2 !== 'undefined') {
                $(rollNumberSelect).on('select2:select select2:clear', () => fillWeightFields(rollNumberSelect));
            }

            btnRemove.addEventListener('click', () => {
                if (tableBody.children.length > 1) {
                    if (typeof $.fn.select2 !== 'undefined') {
                        $(rollNumberSelect).select2('destroy');
                    }
                    tr.remove();
                    updateSrNumbersAndTotal();
                } else {
                    alert('At least one row entry is required.');
                }
            });

            tableBody.appendChild(tr);

            // Preload options instantly for existing values without network calls on page load
            if (selectedSourceType) {
                if (selectedRollSize) {
                    const rollSizeLabel = (itemData.roll_size_relation && itemData.roll_size_relation.RollSize) ? itemData.roll_size_relation.RollSize : selectedRollSize;
                    rollSizeSelect.innerHTML = `<option value="${selectedRollSize}" selected>${rollSizeLabel}</option>`;
                }
                if (selectedRgm) {
                    rgmSelect.innerHTML = `<option value="${selectedRgm}" selected>${selectedRgm}</option>`;
                }
                if (selectedFabricColor) {
                    const colorLabel = (itemData.fabric_color_relation && itemData.fabric_color_relation.FabricColor) ? itemData.fabric_color_relation.FabricColor : selectedFabricColor;
                    fabricColorSelect.innerHTML = `<option value="${selectedFabricColor}" selected>${colorLabel}</option>`;
                }
                if (selectedRollNumber) {
                    const rollLabel = (itemData.transaction_relation && itemData.transaction_relation.RollNumber) ? itemData.transaction_relation.RollNumber : selectedRollNumber;
                    const tr_data = itemData.transaction_relation || {};
                    rollNumberSelect.innerHTML = `<option value="${selectedRollNumber}" data-gross="${tr_data.GrossWeight ?? ''}" data-core="${tr_data.CoreWeight ?? ''}" data-meter="${tr_data.ActualMeter ?? ''}" data-net="${tr_data.NetWeight ?? ''}" selected>${rollLabel}</option>`;
                    grossWeightInput.value = tr_data.GrossWeight ?? '';
                    coreWeightInput.value  = tr_data.CoreWeight  ?? '';
                    actualMeterInput.value = tr_data.ActualMeter ?? '';
                    netWeightInput.value   = tr_data.NetWeight   ?? '';
                    if (typeof $.fn.select2 !== 'undefined') {
                        $(rollNumberSelect).select2({ placeholder: 'Select Roll Number' });
                    }
                }
            }

            // Update totals AFTER weight fields have been populated
            updateSrNumbersAndTotal();

            // On-demand option loader when user clicks/focuses a dropdown
            async function loadStepOnDemand(step) {
                const sourceType = sourceTypeSelect.value;
                const rollSize = rollSizeSelect.value;
                const rgm = rgmSelect.value;
                const fabricColor = fabricColorSelect.value;

                if (step === 'roll_size' && sourceType && rollSizeSelect.options.length <= 1) {
                    await fetchOptions('roll_size', { source_type: sourceType }, rollSizeSelect, rollSizeSelect.value);
                } else if (step === 'rgm' && sourceType && rollSize && rgmSelect.options.length <= 1) {
                    await fetchOptions('rgm', { source_type: sourceType, roll_size: rollSize }, rgmSelect, rgmSelect.value);
                } else if (step === 'fabric_color' && sourceType && rollSize && rgm && fabricColorSelect.options.length <= 1) {
                    await fetchOptions('fabric_color', { source_type: sourceType, roll_size: rollSize, rgm }, fabricColorSelect, fabricColorSelect.value);
                } else if (step === 'roll_number' && sourceType && rollSize && rgm && fabricColor && rollNumberSelect.options.length <= 1) {
                    await fetchOptions('roll_number', { source_type: sourceType, roll_size: rollSize, rgm, fabric_color: fabricColor }, rollNumberSelect, rollNumberSelect.value);
                }
            }

            rollSizeSelect.addEventListener('focus', () => loadStepOnDemand('roll_size'));
            rgmSelect.addEventListener('focus', () => loadStepOnDemand('rgm'));
            fabricColorSelect.addEventListener('focus', () => loadStepOnDemand('fabric_color'));
            if (typeof $.fn.select2 !== 'undefined') {
                $(rollNumberSelect).on('select2:open', () => loadStepOnDemand('roll_number'));
            } else {
                rollNumberSelect.addEventListener('focus', () => loadStepOnDemand('roll_number'));
            }
        }

        btnAddRow.addEventListener('click', () => {
            createRow();
        });

        // Initialize table rows
        if (initialItems && initialItems.length > 0) {
            initialItems.forEach(item => createRow(item));
        } else {
            createRow(); // default 1 row
        }
    });
</script>
@endsection
