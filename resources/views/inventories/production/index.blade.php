@extends('layouts.app')

@section('title', 'Production List')

@section('content')
<style>
    /* Custom Select2 Overrides for modern look */
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 4px 8px;
        border: 1px solid var(--card-border);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.9);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        color: var(--text-primary);
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-dropdown {
        border: 1px solid var(--card-border);
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
</style>

<div class="content-header">
    <div class="content-title">
        <h1>Production List</h1>
        <p>Manage fabric production inventory transactions</p>
    </div>
    <a href="{{ route('inventories.production.create') }}" class="btn-circle-add" title="Add New Production">
        +
    </a>
</div>

<!-- Header Filter Section matching user sequence: Roll Size -> Required Gram Meter -> Fabric Color -> Roll Number -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
        <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0; color: var(--text-primary);">Filter Header Section</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
        <!-- 1. Roll Size -->
        <div class="form-group">
            <label for="filter_roll_size">Roll Size</label>
            <select id="filter_roll_size" class="select2-filter">
                <option value="">All Roll Sizes</option>
                @foreach($rollSizes as $rs)
                    <option value="{{ $rs->ID }}">{{ $rs->RollSize }}</option>
                @endforeach
            </select>
        </div>

        <!-- 2. Required Gram Meter -->
        <div class="form-group">
            <label for="filter_required_gram_meter">Required Gram Meter</label>
            <select id="filter_required_gram_meter" class="select2-filter">
                <option value="">All RGM Options</option>
                @foreach($rgmOptions as $rgm)
                    <option value="{{ $rgm }}">{{ $rgm }}</option>
                @endforeach
            </select>
        </div>

        <!-- 3. Fabric Color -->
        <div class="form-group">
            <label for="filter_fabric_color">Fabric Color</label>
            <select id="filter_fabric_color" class="select2-filter">
                <option value="">All Fabric Colors</option>
                @foreach($fabricColors as $fc)
                    <option value="{{ $fc->ID }}">{{ $fc->FabricColor }}</option>
                @endforeach
            </select>
        </div>


        <!-- Filter & Clear Buttons -->
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="button" id="btn-apply-filter" class="btn-action" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">
                Filter
            </button>
            <button type="button" id="btn-clear-filter" class="btn-action-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">
                Clear
            </button>
        </div>
    </div>
</div>

<div class="card datatable-wrapper">
    <div class="datatable-controls">
        <div class="datatable-length">
            <span>Show</span>
            <select id="dt-length">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>entries</span>
        </div>
        <div class="datatable-search">
            <input type="text" id="dt-search" placeholder="Search production...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="production-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="select-all-rolls" style="cursor: pointer;" title="Select/Deselect All">
                    </th>
                    <th data-column="ID" style="width: 50px;">ID</th>
                    <th data-column="RollNumber">Roll No.</th>
                    <th data-column="EntryDate">Pro_Dt</th>
                    <th data-column="RollSize">Size</th>
                    <th data-column="RequiredGramMeter">RGMW</th>
                    <th data-column="ActualMeter">Meter</th>
                    <th data-column="GrossWeight">Gr Wt</th>
                    <th data-column="CoreWeight">Cr Wt</th>
                    <th data-column="NetWeight">Net Wt</th>
                    <th data-column="FabricColor">Fab_Type</th>
                    <th data-column="LoomNumber">Loom No.</th>
                    <th data-column="ClosingMeter">Cl Mtr</th>
                    <th data-column="ActualMeterWeight">AWM</th>
                    <th data-column="Variation">Variation</th>
                    <th data-column="CreatedOn">C_Date</th>
                    <th data-column="UpdatedOn">U_Date</th>
                    <th style="width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <div class="datatable-footer">
        <div class="datatable-info">Showing 0 to 0 of 0 entries</div>
        <div class="pagination-controls"></div>
    </div>
</div>

<!-- Bottom Section: Transfer & Dispatch AJAX Action -->
<div class="card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0; color: var(--text-primary);">Transfer & Dispatch Section</h3>
        <span id="selected-count-badge" class="badge badge-inactive" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
            0 rolls selected
        </span>
    </div>
    <form id="action-form">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; align-items: end;">
            <!-- Action Radio Buttons -->
            <div class="form-group">
                <label>Action Type</label>
                <div style="display: flex; gap: 1.5rem; margin-top: 0.4rem;">
                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600; color: var(--text-primary);">
                        <input type="radio" name="action_type" value="Transfer" checked style="cursor: pointer; width: auto;"> Transfer
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600; color: var(--text-primary);">
                        <input type="radio" name="action_type" value="Dispatch" style="cursor: pointer; width: auto;"> Dispatch
                    </label>
                </div>
            </div>

            <!-- Entry Date -->
            <div class="form-group">
                <label for="action_entry_date" class="required">Entry Date</label>
                <input type="date" id="action_entry_date" name="EntryDate" value="{{ date('Y-m-d') }}" required>
            </div>

            <!-- Party Name -->
            <div class="form-group">
                <label for="action_party_id" class="required">Party Name</label>
                <select id="action_party_id" name="PartyName" required>
                    <option value="">Select Party</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->ID }}">{{ $party->PartyName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Invoice Number (Dispatch Only) -->
            <div class="form-group" id="invoice-group" style="display: none;">
                <label for="action_invoice_number">Invoice Number</label>
                <input type="text" id="action_invoice_number" name="InvoiceNumber" placeholder="Enter invoice number...">
            </div>

            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" id="btn-submit-action" class="btn-action" style="padding: 0.65rem 1.5rem; justify-content: center;">
                    Submit Entry
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Success / Error Result Modal Popup -->
<div id="result-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="card" style="width: 100%; max-width: 480px; text-align: center; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); background: #ffffff;">
        <div id="modal-icon-container" style="font-size: 3.5rem; margin-bottom: 0.75rem;"></div>
        <h3 id="modal-title" style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-primary);"></h3>
        <p id="modal-message" style="color: var(--text-secondary); margin-bottom: 1.25rem; font-size: 0.95rem; line-height: 1.5;"></p>

        <!-- Direct Jump Link Container -->
        <div id="modal-link-container" style="display: none; margin-bottom: 1.25rem; padding: 0.75rem; background: rgba(79, 70, 229, 0.06); border-radius: 10px; border: 1px solid rgba(79, 70, 229, 0.15);">
            <p style="margin-bottom: 0.3rem; font-size: 0.85rem; color: var(--text-secondary);">Direct Link to Created Entry:</p>
            <a id="modal-edit-link" href="#" class="table-id-link" style="font-weight: 700; font-size: 1rem; text-decoration: underline;" target="_blank">
                Open Created Entry ➔
            </a>
        </div>

        <!-- Countdown Timer Text -->
        <div id="modal-countdown-container" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem; font-weight: 500;">
            Closing in <span id="modal-countdown" style="font-weight: 700; color: var(--accent-primary); font-size: 1rem;">10</span> seconds...
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <button type="button" id="btn-modal-close" class="btn-action-secondary" style="padding: 0.6rem 1.25rem;">
                Close Now
            </button>
            <a id="btn-modal-jump" href="#" class="btn-action" style="padding: 0.6rem 1.25rem; text-decoration: none; color: #fff; display: none;">
                Jump to Edit Page ➔
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/datatable.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectedRolls = new Map();
        const optionsUrl = "{{ route('inventories.production.options') }}";

        const filterRollSize = document.getElementById('filter_roll_size');
        const filterRgm = document.getElementById('filter_required_gram_meter');
        const filterFabricColor = document.getElementById('filter_fabric_color');

        // Initialize Select2 on all filter & party dropdowns
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filter_roll_size').select2({ placeholder: 'All Roll Sizes', allowClear: true, width: '100%' });
            $('#filter_required_gram_meter').select2({ placeholder: 'All RGM Options', allowClear: true, width: '100%' });
            $('#filter_fabric_color').select2({ placeholder: 'All Fabric Colors', allowClear: true, width: '100%' });
            $('#action_party_id').select2({ placeholder: 'Select Party', allowClear: true, width: '100%' });
        }

        // Modal Controller logic
        let countdownTimer = null;

        function showResultModal(isSuccess, title, message, editUrl = null) {
            const modal = document.getElementById('result-modal');
            const iconContainer = document.getElementById('modal-icon-container');
            const titleElem = document.getElementById('modal-title');
            const msgElem = document.getElementById('modal-message');
            const linkContainer = document.getElementById('modal-link-container');
            const editLink = document.getElementById('modal-edit-link');
            const jumpBtn = document.getElementById('btn-modal-jump');
            const countdownSpan = document.getElementById('modal-countdown');

            if (countdownTimer) clearInterval(countdownTimer);

            titleElem.textContent = title;
            msgElem.textContent = message;

            if (isSuccess) {
                iconContainer.textContent = '🎉';
                if (editUrl) {
                    linkContainer.style.display = 'block';
                    editLink.href = editUrl;
                    editLink.textContent = `Open Created Record ➔`;
                    jumpBtn.style.display = 'inline-flex';
                    jumpBtn.href = editUrl;
                } else {
                    linkContainer.style.display = 'none';
                    jumpBtn.style.display = 'none';
                }
            } else {
                iconContainer.textContent = '⚠️';
                linkContainer.style.display = 'none';
                jumpBtn.style.display = 'none';
            }

            let secondsLeft = 10;
            countdownSpan.textContent = secondsLeft;
            modal.style.display = 'flex';

            countdownTimer = setInterval(() => {
                secondsLeft--;
                countdownSpan.textContent = secondsLeft;
                if (secondsLeft <= 0) {
                    clearInterval(countdownTimer);
                    closeResultModal();
                }
            }, 1000);
        }

        function closeResultModal() {
            if (countdownTimer) clearInterval(countdownTimer);
            const modal = document.getElementById('result-modal');
            modal.style.display = 'none';
        }

        document.getElementById('btn-modal-close').addEventListener('click', closeResultModal);

        function formatDate(val) {
            if (!val) return '';
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        }

        function updateSelectionBadge() {
            const count = selectedRolls.size;
            const badge = document.getElementById('selected-count-badge');
            badge.textContent = `${count} roll${count === 1 ? '' : 's'} selected`;
            badge.className = count > 0 ? 'badge badge-active' : 'badge badge-inactive';
        }

        function syncSelectAllCheckbox() {
            const selectAll = document.getElementById('select-all-rolls');
            const rowCheckboxes = document.querySelectorAll('.roll-checkbox');
            if (rowCheckboxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }
            let checkedCount = 0;
            rowCheckboxes.forEach(cb => { if (cb.checked) checkedCount++; });
            if (checkedCount === rowCheckboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else if (checkedCount > 0) {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }

        let appliedFilters = {
            roll_size: '',
            required_gram_meter: '',
            fabric_color: ''
        };

        // Initialize DataTable
        const table = new DynamicDataTable('production-table', {
            url: "{{ route('inventories.production.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            getParams: () => {
                return appliedFilters;
            },
            columns: [
                {
                    name: 'select',
                    sortable: false,
                    render: (val, row) => {
                        const isChecked = selectedRolls.has(row.ID) ? 'checked' : '';
                        return `<div style="text-align: center;"><input type="checkbox" class="roll-checkbox" data-id="${row.ID}" data-roll-size="${row.RollSize || ''}" data-rgm="${row.RequiredGramMeter || ''}" data-color="${row.FabricColor || ''}" ${isChecked} style="cursor: pointer;"></div>`;
                    }
                },
                { 
                    name: 'ID', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('inventories.production.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val}</a>`;
                    }
                },
                { name: 'RollNumber', sortable: true },
                { name: 'EntryDateFormatted', sortable: true },
                { name: 'RollSizeName', sortable: true },
                { name: 'RequiredGramMeter', sortable: true },
                { name: 'ActualMeter', sortable: true },
                { name: 'GrossWeight', sortable: true },
                { name: 'CoreWeight', sortable: true },
                { name: 'NetWeight', sortable: true },
                { name: 'FabricColorName', sortable: true },
                { name: 'LoomNumberValue', sortable: true },
                { name: 'ClosingMeter', sortable: true },
                { name: 'ActualMeterWeight', sortable: true },
                { name: 'Variation', sortable: true },
                { 
                    name: 'CreatedOn', 
                    sortable: true,
                    render: (val) => formatDate(val)
                },
                { 
                    name: 'UpdatedOn', 
                    sortable: true,
                    render: (val) => formatDate(val)
                }
            ],
            actions: (row) => {
                const editUrl = "{{ route('inventories.production.edit', ':id') }}".replace(':id', row.ID);
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit">✏️</a>
                    <span style="opacity: 0.3; margin: 0 0.25rem;">|</span>
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete">🗑️</button>
                `;
            }
        });

        // Sync header checkbox state after rendering table data
        const origRender = table.render.bind(table);
        table.render = function(response) {
            origRender(response);
            syncSelectAllCheckbox();
        };

        // Header Filter Buttons (Filter executes ONLY when clicking the Filter button)
        document.getElementById('btn-apply-filter').addEventListener('click', () => {
            appliedFilters = {
                roll_size: $('#filter_roll_size').val() || '',
                required_gram_meter: $('#filter_required_gram_meter').val() || '',
                fabric_color: $('#filter_fabric_color').val() || ''
            };
            table.state.page = 1;
            table.fetch();
        });

        document.getElementById('btn-clear-filter').addEventListener('click', () => {
            appliedFilters = {
                roll_size: '',
                required_gram_meter: '',
                fabric_color: ''
            };

            if (typeof $.fn.select2 !== 'undefined') {
                $('#filter_roll_size').val(null).trigger('change');
                $('#filter_required_gram_meter').val(null).trigger('change');
                $('#filter_fabric_color').val(null).trigger('change');
            } else {
                filterRollSize.value = '';
                filterRgm.value = '';
                filterFabricColor.value = '';
            }

            table.state.page = 1;
            table.fetch();
        });

        // Master Select All Checkbox Handler
        document.getElementById('select-all-rolls').addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            const rowCheckboxes = document.querySelectorAll('.roll-checkbox');
            rowCheckboxes.forEach(cb => {
                cb.checked = isChecked;
                const id = parseInt(cb.dataset.id, 10);
                if (isChecked) {
                    selectedRolls.set(id, {
                        id: id,
                        rollSize: cb.dataset.rollSize,
                        rgm: cb.dataset.rgm,
                        color: cb.dataset.color
                    });
                } else {
                    selectedRolls.delete(id);
                }
            });
            updateSelectionBadge();
        });

        // Individual Row Checkbox Handler
        document.querySelector('#production-table tbody').addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('roll-checkbox')) {
                const cb = e.target;
                const id = parseInt(cb.dataset.id, 10);
                if (cb.checked) {
                    selectedRolls.set(id, {
                        id: id,
                        rollSize: cb.dataset.rollSize,
                        rgm: cb.dataset.rgm,
                        color: cb.dataset.color
                    });
                } else {
                    selectedRolls.delete(id);
                }
                syncSelectAllCheckbox();
                updateSelectionBadge();
            }
        });

        // Radio Action Type Toggle (Transfer vs Dispatch)
        const actionRadios = document.getElementsByName('action_type');
        const invoiceGroup = document.getElementById('invoice-group');
        actionRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (document.querySelector('input[name="action_type"]:checked').value === 'Dispatch') {
                    invoiceGroup.style.display = 'flex';
                } else {
                    invoiceGroup.style.display = 'none';
                }
            });
        });

        // Form Submit Handler for Transfer / Dispatch
        document.getElementById('action-form').addEventListener('submit', (e) => {
            e.preventDefault();

            if (selectedRolls.size === 0) {
                showResultModal(false, 'No Rolls Selected', 'Please select at least one production roll checkbox from the list.');
                return;
            }

            const actionType = document.querySelector('input[name="action_type"]:checked').value;
            const entryDate = document.getElementById('action_entry_date').value;
            const partyName = document.getElementById('action_party_id').value;
            const invoiceNumber = document.getElementById('action_invoice_number').value;

            if (!entryDate) {
                showResultModal(false, 'Missing Entry Date', 'Please select an Entry Date.');
                return;
            }

            if (!partyName) {
                showResultModal(false, 'Missing Party Name', 'Please select a Party Name.');
                return;
            }

            const itemsArray = Array.from(selectedRolls.values()).map(item => {
                return {
                    SourceType: 1,
                    RollSize: item.rollSize,
                    RequiredGramMeter: item.rgm,
                    FabricColor: item.color,
                    InTransactionID: item.id
                };
            });

            const targetUrl = "{{ route('inventories.dispatch.store') }}";

            const payload = {
                EntryDate: entryDate,
                PartyName: partyName,
                DispatchType: actionType,
                InvoiceNumber: invoiceNumber || null,
                items: itemsArray
            };

            const submitBtn = document.getElementById('btn-submit-action');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(resObj => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Entry';

                if (resObj.status >= 200 && resObj.status < 300 && resObj.body.success) {
                    const count = selectedRolls.size;
                    selectedRolls.clear();
                    updateSelectionBadge();
                    document.getElementById('action_invoice_number').value = '';
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#action_party_id').val(null).trigger('change');
                    } else {
                        document.getElementById('action_party_id').value = '';
                    }
                    table.fetch();

                    const editUrl = resObj.body.edit_url;
                    showResultModal(true, `${actionType} Created Successfully!`, `${actionType} record ID #${resObj.body.id} has been created for ${count} roll(s).`, editUrl);
                } else {
                    const msg = resObj.body.message || (resObj.body.errors ? Object.values(resObj.body.errors).flat().join('\n') : 'Submission failed.');
                    showResultModal(false, `${actionType} Submission Failed`, msg);
                }
            })
            .catch(err => {
                console.error('Action submit error:', err);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Entry';
                showResultModal(false, `${actionType} Error`, `An unexpected error occurred while submitting ${actionType}.`);
            });
        });

        // Global delete function
        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this production record?')) {
                fetch("{{ route('inventories.production.destroy', ':id') }}".replace(':id', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        selectedRolls.delete(id);
                        updateSelectionBadge();
                        table.fetch();
                    } else {
                        alert('Failed to delete the record.');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    alert('An error occurred while deleting.');
                });
            }
        };
    });
</script>
@endsection
