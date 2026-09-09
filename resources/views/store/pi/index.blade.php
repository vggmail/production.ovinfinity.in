@extends('layouts.app')

@section('title', 'Proforma Invoice (PI)')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Proforma Invoice (PI) List</h1>
        <p>Manage and generate Proforma Invoices based on Material Requisition Lists (MRL)</p>
    </div>
    <a href="{{ route('store.pi.create') }}" class="btn-circle-add" title="Add New PI">
        +
    </a>
</div>

<div class="card datatable-wrapper">
    <div class="datatable-controls">
        <div class="datatable-length">
            <span>Show</span>
            <select id="dt-length">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
            <span>entries</span>
        </div>
        <div class="datatable-search">
            <input type="text" id="dt-search" placeholder="Search invoice entry no, PI no, or supplier...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="pi-table">
            <thead>
                <tr>
                    <th data-column="ID" style="width: 60px;">ID</th>
                    <th data-column="InvoiceEntryNo">Invoice Entry No</th>
                    <th data-column="PIDate">Date</th>
                    <th>Supplier</th>
                    <th data-column="PINumber">PI No</th>
                    <th data-column="TotalItems">Total Items</th>
                    <th data-column="TotalQuantity">Total Qty</th>
                    <th data-column="TotalPIAmount">Total PI Amount</th>
                    <th style="width: 170px;">Actions</th>
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
            if (!val) return '-';
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function formatCurrency(val) {
            return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        const table = new DynamicDataTable('pi-table', {
            url: "{{ route('store.pi.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            columns: [
                { 
                    name: 'ID', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('store.pi.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val}</a>`;
                    }
                },
                { 
                    name: 'InvoiceEntryNo', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('store.pi.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" style="font-weight:600; color:#3b82f6; text-decoration:none;">${val}</a>`;
                    }
                },
                { 
                    name: 'PIDate', 
                    sortable: true,
                    render: (val) => formatDate(val)
                },
                { 
                    name: 'SupplierName', 
                    sortable: false,
                    render: (val) => `<span style="font-weight: 600; color: #1e293b;">${val}</span>`
                },
                { 
                    name: 'PINumber', 
                    sortable: true,
                    render: (val, row) => {
                        const printUrl = "{{ route('store.pi.print', ':id') }}".replace(':id', row.ID);
                        return `<a href="javascript:void(0);" style="font-weight:700; color:#1e40af; text-decoration:none;" title="View/Print PI">${val}</a>`;
                    }
                },
                { name: 'TotalItems', sortable: true },
                { name: 'TotalQuantity', sortable: true },
                { 
                    name: 'TotalPIAmount', 
                    sortable: true,
                    render: (val) => `<strong>₹ ${formatCurrency(val)}</strong>`
                },
            ],
            actions: (row) => {
                const editUrl = "{{ route('store.pi.edit', ':id') }}".replace(':id', row.ID);
                const printUrl = "{{ route('store.pi.print', ':id') }}".replace(':id', row.ID);
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit PI" style="font-size: 1rem;">✏️</a>
                    <!-- <a href="${printUrl}" target="_blank" class="btn-print-badge" title="Print / Download PDF" style="background-color: #2563eb; color: #ffffff; padding: 0.3rem 0.65rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; margin: 0 0.2rem; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                        <span>🖨️</span> <span>PDF</span>
                    </a> -->
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete" style="font-size: 1rem;">🗑️</button>
                `;
            }
        });

        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this PI record?')) {
                fetch("{{ route('store.pi.destroy', ':id') }}".replace(':id', id), {
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
