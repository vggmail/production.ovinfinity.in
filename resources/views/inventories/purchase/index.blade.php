@extends('layouts.app')

@section('title', 'Purchase List')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Purchase List</h1>
        <p>Manage fabric purchase inventory transactions</p>
    </div>
    <a href="{{ route('inventories.purchase.create') }}" class="btn-circle-add" title="Add New Purchase">
        +
    </a>
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
            <input type="text" id="dt-search" placeholder="Search purchase...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="purchase-table">
            <thead>
                <tr>
                    <th data-column="ID" style="width: 50px;">ID</th>
                    <th data-column="RollNumber">Roll No.</th>
                    <th data-column="EntryDate">Entry Date</th>
                    <th data-column="InvoiceNo">Invoice No</th>
                    <th data-column="RollSize">Roll Size</th>
                    <th data-column="FabricColor">Fabric Color</th>
                    <th data-column="Lamination">Lamination</th>
                    <th data-column="RequiredGramMeter">Required Gram Meter</th>
                    <th data-column="ActualMeter">Actual Meter</th>
                    <th data-column="GrossWeight">Gross Weight</th>
                    <th data-column="CoreWeight">Core Weight</th>
                    <th data-column="NetWeight">Net Weight</th>
                    <th data-column="ActualMeterWeight">Actual Meter Weight</th>
                    <th data-column="Variation">Variation</th>
                    <th data-column="CreatedOn">Created On</th>
                    <th data-column="UpdatedOn">Updated On</th>
                    <th style="width: 140px;">Update | Delete</th>
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
@endsection

@section('scripts')
<script src="{{ asset('js/datatable.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        function formatDate(val) {
            if (!val) return '';
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        }

        const table = new DynamicDataTable('purchase-table', {
            url: "{{ route('inventories.purchase.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            columns: [
                { 
                    name: 'ID', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('inventories.purchase.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val}</a>`;
                    }
                },
                { name: 'RollNumber', sortable: true },
                { name: 'EntryDateFormatted', sortable: true },
                { name: 'InvoiceNo', sortable: true },
                { name: 'RollSizeName', sortable: true },
                { name: 'FabricColorName', sortable: true },
                { name: 'LaminationName', sortable: true },
                { name: 'RequiredGramMeter', sortable: true },
                { name: 'ActualMeter', sortable: true },
                { name: 'GrossWeight', sortable: true },
                { name: 'CoreWeight', sortable: true },
                { name: 'NetWeight', sortable: true },
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
                const editUrl = "{{ route('inventories.purchase.edit', ':id') }}".replace(':id', row.ID);
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit">✏️</a>
                    <span style="opacity: 0.3; margin: 0 0.25rem;">|</span>
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete">🗑️</button>
                `;
            }
        });

        // Global delete function
        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this purchase record?')) {
                fetch("{{ route('inventories.purchase.destroy', ':id') }}".replace(':id', id), {
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
