@extends('layouts.app')

@section('title', 'Repair Material Issue')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Repair Material Issue List</h1>
        <p>Manage materials sent out for vendor repair and track repair status</p>
    </div>
    <a href="{{ route('repair.issue.create') }}" class="btn-circle-add" title="Add New Repair Material Issue">
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
            <input type="text" id="dt-search" placeholder="Search by repair no, date, supplier, status or remarks...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="repair-issue-table">
            <thead>
                <tr>
                    <th data-column="RepairNo" style="width: 150px;">Repair No</th>
                    <th data-column="RepairDate" style="width: 110px;">Date</th>
                    <th style="min-width: 170px;">Supplier</th>
                    <th data-column="TotalItems" style="width: 100px;">Total Items</th>
                    <th data-column="TotalQuantity" style="width: 110px;">Total Qty</th>
                    <th data-column="TotalAmount" style="width: 120px;">Total Value</th>
                    <th data-column="Status" style="width: 110px;">Status</th>
                    <th style="width: 110px;">Actions</th>
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
            if (!val || val === '-') return '-';
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = String(d.getFullYear()).slice(-2);
            return `${day}/${month}/${year}`;
        }

        const table = new DynamicDataTable('repair-issue-table', {
            url: "{{ route('repair.issue.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            columns: [
                { 
                    name: 'RepairNo', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('repair.issue.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val || row.ID}</a>`;
                    }
                },
                { 
                    name: 'RepairDate', 
                    sortable: true,
                    render: (val) => formatDate(val)
                },
                { 
                    name: 'SupplierName', 
                    sortable: false,
                    render: (val) => val || '<span class="text-muted">-</span>'
                },
                { name: 'TotalItems', sortable: true },
                { 
                    name: 'TotalQuantity', 
                    sortable: true, 
                    render: (val) => val ? parseFloat(val).toLocaleString('en-IN') : 0 
                },
                { 
                    name: 'TotalAmount', 
                    sortable: true, 
                    render: (val) => val ? '₹ ' + parseFloat(val).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₹ 0.00' 
                },
                { 
                    name: 'Status', 
                    sortable: true,
                    render: (val) => {
                        if (val === 'Complete') {
                            return `<span class="badge" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Complete</span>`;
                        }
                        return `<span class="badge" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pending</span>`;
                    }
                }
            ],
            actions: (row) => {
                const editUrl = "{{ route('repair.issue.edit', ':id') }}".replace(':id', row.ID);
                // User requirement: inline print option hide rakhna
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit">✏️</a>
                    <span style="opacity: 0.3; margin: 0 0.25rem;">|</span>
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete">🗑️</button>
                `;
            }
        });

        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this Repair Material Issue?')) {
                fetch("{{ route('repair.issue.destroy', ':id') }}".replace(':id', id), {
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
                        alert(response.message || 'Error deleting record.');
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
