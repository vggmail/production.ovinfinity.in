@extends('layouts.app')

@section('title', 'Vendor Quotation')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Vendor Quotation List</h1>
        <p>Manage and generate quotations for suppliers based on Material Requisition Lists (MRL)</p>
    </div>
    <a href="{{ route('store.quotation.create') }}" class="btn-circle-add" title="Create New Quotation">
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
            <input type="text" id="dt-search" placeholder="Search quotation number or supplier...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="quotation-table">
            <thead>
                <tr>
                    <th data-column="ID" style="width: 60px;">ID</th>
                    <th data-column="QuotationNumber">Quotation No</th>
                    <th data-column="QuotationDate">Quotation Date</th>
                    <th>Supplier</th>
                    <th>MRL Date Range</th>
                    <th data-column="TotalItems">Total Items</th>
                    <th data-column="TotalQuantity">Total Qty</th>
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

        const table = new DynamicDataTable('quotation-table', {
            url: "{{ route('store.quotation.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            columns: [
                { 
                    name: 'ID', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('store.quotation.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val}</a>`;
                    }
                },
                { 
                    name: 'QuotationNumber', 
                    sortable: true,
                    render: (val, row) => {
                        const printUrl = "{{ route('store.quotation.print', ':id') }}".replace(':id', row.ID);
                        return `<a href="${printUrl}" target="_blank" style="font-weight:700; color:#1e40af; text-decoration:none;" title="View/Print Quotation PDF">${val}</a>`;
                    }
                },
                { 
                    name: 'QuotationDate', 
                    sortable: true,
                    render: (val) => formatDate(val)
                },
                { 
                    name: 'SupplierName', 
                    sortable: false,
                    render: (val) => `<span style="font-weight: 600; color: #1e293b;">${val}</span>`
                },
                { 
                    name: 'FromDate', 
                    sortable: false,
                    render: (val, row) => {
                        if (row.FromDate && row.ToDate) {
                            return `${formatDate(row.FromDate)} to ${formatDate(row.ToDate)}`;
                        }
                        return '-';
                    }
                },
                { name: 'TotalItems', sortable: true },
                { name: 'TotalQuantity', sortable: true },
            ],
            actions: (row) => {
                const editUrl = "{{ route('store.quotation.edit', ':id') }}".replace(':id', row.ID);
                const printUrl = "{{ route('store.quotation.print', ':id') }}".replace(':id', row.ID);
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit Quotation" style="font-size: 1rem;">✏️</a>
                    <a href="${printUrl}" target="_blank" class="btn-print-badge" title="Print / Download PDF" style="background-color: #2563eb; color: #ffffff; padding: 0.3rem 0.65rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; margin: 0 0.2rem; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                        <span>🖨️</span> <span>PDF</span>
                    </a>
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete" style="font-size: 1rem;">🗑️</button>
                `;
            }
        });

        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this Quotation record?')) {
                fetch("{{ route('store.quotation.destroy', ':id') }}".replace(':id', id), {
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
