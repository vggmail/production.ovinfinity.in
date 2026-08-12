@extends('layouts.app')

@section('title', $transfer->exists ? 'Edit Transfer' : 'Add New Transfer')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $transfer->exists ? 'Edit Transfer' : 'Add New Transfer' }}</h1>
    </div>
    <a href="{{ route('inventories.transfer.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;" title="Close">
        ✕
    </a>
</div>

<div class="card" style="margin-top: 1rem;">
    <form action="{{ $transfer->exists ? route('inventories.transfer.update', $transfer->ID) : route('inventories.transfer.store') }}" method="POST" id="transfer-form">
        @csrf
        @if($transfer->exists)
            @method('PUT')
        @endif

        <!-- Top Header Controls -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="ID">Id</label>
                <input type="text" id="ID" value="{{ $transfer->ID ?? '' }}" disabled style="background: rgba(0,0,0,0.03);">
            </div>

            <div class="form-group">
                <label for="EntryDate">Entry Date</label>
                <input type="date" name="EntryDate" id="EntryDate" value="{{ old('EntryDate', $transfer->EntryDate) }}" class="@error('EntryDate') is-invalid @enderror" required>
                @error('EntryDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="PartyName">Party Name</label>
                <select name="PartyName" id="PartyName" class="@error('PartyName') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->ID }}" {{ old('PartyName', $transfer->PartyName) == $party->ID ? 'selected' : '' }}>
                            {{ $party->PartyName }}
                        </option>
                    @endforeach
                </select>
                @error('PartyName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Dynamic Items Table matching user mockup styling -->
        <div style="margin-bottom: 1.5rem; overflow-x: auto;">
            <table class="datatable" id="items-table" style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #c2e2c4; border-radius: 6px;">
                <thead>
                    <tr style="background-color: #d8ebd9; color: #1e3a1f;">
                        <th style="width: 80px; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Sr No</th>
                        <th style="text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Source Type</th>
                        <th style="text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Roll Number</th>
                        <th style="width: 80px; text-align: center; padding: 10px; border-bottom: 1px solid #c2e2c4; font-weight: 700;">Action</th>
                    </tr>
                </thead>
                <tbody id="items-table-body" style="background-color: #eaf5eb;">
                    <!-- Dynamic rows inserted here -->
                </tbody>
                <tfoot>
                    <tr style="background-color: #d8ebd9;">
                        <td colspan="4" style="text-align: center; padding: 8px;">
                            <button type="button" id="btn-add-row" class="btn-action-secondary" style="width: 100%; max-width: 400px; padding: 6px 16px; font-weight: 600; cursor: pointer; border: 1px solid #6b8e6d; background: white; border-radius: 4px; color: #1e3a1f;">
                                Add
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Bottom Controls -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group" style="max-width: 250px;">
                <label for="TotalRolls">Total Rolls</label>
                <input type="text" id="TotalRolls" value="{{ old('TotalRolls', $transfer->TotalRolls ?? 0) }}" readonly style="background: rgba(0,0,0,0.03);">
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

        const initialItems = @json(old('items', $transfer->children ?? []));
        const rollsApiUrl = "{{ route('inventories.transfer.getRolls') }}";
        const transferId = "{{ $transfer->exists ? $transfer->ID : '' }}";

        let rowCount = 0;

        function updateSrNumbersAndTotal() {
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                const srCell = row.querySelector('.sr-no');
                if (srCell) {
                    srCell.textContent = index + 1;
                }
                // Update input name indices
                const sourceTypeSelect = row.querySelector('.source-type-select');
                const rollNumberSelect = row.querySelector('.roll-number-select');
                if (sourceTypeSelect) {
                    sourceTypeSelect.name = `items[${index}][SourceType]`;
                }
                if (rollNumberSelect) {
                    rollNumberSelect.name = `items[${index}][RollNumber]`;
                }
            });
            totalRollsInput.value = rows.length;
        }

        async function fetchRollNumbers(sourceType, rollSelectElement, selectedRollNumber = null) {
            if (!sourceType) {
                rollSelectElement.innerHTML = '<option value="">Select</option>';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollSelectElement).select2({ placeholder: 'Select Roll Number' });
                }
                return;
            }

            rollSelectElement.innerHTML = '<option value="">Loading...</option>';
            if (typeof $.fn.select2 !== 'undefined') {
                $(rollSelectElement).select2({ placeholder: 'Loading...' });
            }

            try {
                let url = `${rollsApiUrl}?source_type=${sourceType}`;
                if (transferId) {
                    url += `&transfer_id=${transferId}`;
                }
                const response = await fetch(url);
                const rolls = await response.json();

                let html = '<option value="">Select</option>';
                rolls.forEach(roll => {
                    const isSelected = selectedRollNumber && String(selectedRollNumber) === String(roll) ? 'selected' : '';
                    html += `<option value="${roll}" ${isSelected}>${roll}</option>`;
                });
                
                // If selectedRollNumber exists but not in response list (e.g. legacy/custom), append it
                if (selectedRollNumber && !rolls.includes(Number(selectedRollNumber)) && !rolls.includes(String(selectedRollNumber))) {
                    html += `<option value="${selectedRollNumber}" selected>${selectedRollNumber}</option>`;
                }

                rollSelectElement.innerHTML = html;
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollSelectElement).select2({ placeholder: 'Select Roll Number' });
                }
            } catch (err) {
                console.error('Failed to load roll numbers', err);
                rollSelectElement.innerHTML = '<option value="">Error loading</option>';
                if (typeof $.fn.select2 !== 'undefined') {
                    $(rollSelectElement).select2({ placeholder: 'Error loading' });
                }
            }
        }

        function createRow(itemData = {}) {
            rowCount++;
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #c2e2c4';

            const srNo = itemData.srNo || (tableBody.children.length + 1);
            const selectedSourceType = itemData.SourceType || '';
            const selectedRollNumber = itemData.RollNumber || '';

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
                    <select class="roll-number-select" style="width: 100%;" required>
                        <option value="">Select</option>
                    </select>
                </td>
                <td style="text-align: center; padding: 8px;">
                    <button type="button" class="btn-remove-row" style="background: transparent; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Remove Row">🗑️</button>
                </td>
            `;

            const sourceTypeSelect = tr.querySelector('.source-type-select');
            const rollNumberSelect = tr.querySelector('.roll-number-select');
            const btnRemove = tr.querySelector('.btn-remove-row');

            if (typeof $.fn.select2 !== 'undefined') {
                $(rollNumberSelect).select2({ placeholder: 'Select' });
            }

            sourceTypeSelect.addEventListener('change', (e) => {
                fetchRollNumbers(e.target.value, rollNumberSelect);
            });

            btnRemove.addEventListener('click', () => {
                if (tableBody.children.length > 1) {
                    if (typeof $.fn.select2 !== 'undefined') {
                        $(rollNumberSelect).select2('destroy');
                    }
                    tr.remove();
                    updateSrNumbersAndTotal();
                } else {
                    alert('At least one roll entry is required.');
                }
            });

            tableBody.appendChild(tr);
            updateSrNumbersAndTotal();

            if (selectedSourceType) {
                fetchRollNumbers(selectedSourceType, rollNumberSelect, selectedRollNumber);
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
